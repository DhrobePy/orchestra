<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Lightweight Google Drive uploader using Service Account credentials.
 * No external packages required — uses PHP OpenSSL + Laravel HTTP client.
 */
class GoogleDriveService
{
    private array  $credentials;
    private string $accessToken = '';
    private int    $tokenExpiry = 0;

    public function __construct(array $credentials)
    {
        $this->credentials = $credentials;
    }

    /** Instantiate from the raw JSON string stored in backup config. */
    public static function fromJson(string $json): self
    {
        return new self(json_decode($json, true));
    }

    /** Test credentials by fetching an access token. */
    public function testConnection(): bool
    {
        try {
            $this->getAccessToken();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Upload a local file to Google Drive.
     *
     * @param  string  $localPath  Absolute path to the file.
     * @param  string  $folderId   Target Drive folder ID.
     * @param  string  $filename   Name to use in Drive.
     * @return string  The created file's Drive ID.
     */
    public function uploadFile(string $localPath, string $folderId, string $filename): string
    {
        $token   = $this->getAccessToken();
        $content = file_get_contents($localPath);
        $size    = strlen($content);

        // ── Metadata ───────────────────────────────────────────────────────
        $metadata = json_encode([
            'name'    => $filename,
            'parents' => [$folderId],
        ]);

        // ── Multipart upload ───────────────────────────────────────────────
        $boundary = 'orchestra_backup_' . uniqid();
        $body = "--{$boundary}\r\n"
            . "Content-Type: application/json; charset=UTF-8\r\n\r\n"
            . $metadata . "\r\n"
            . "--{$boundary}\r\n"
            . "Content-Type: application/zip\r\n\r\n"
            . $content . "\r\n"
            . "--{$boundary}--";

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
            'Content-Type'  => "multipart/related; boundary={$boundary}",
        ])->withBody($body, "multipart/related; boundary={$boundary}")
          ->post('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart');

        if (! $response->successful()) {
            throw new \RuntimeException('Drive upload failed: ' . $response->body());
        }

        return $response->json('id');
    }

    /**
     * Delete files in a folder that are older than $retentionDays days.
     */
    public function pruneOldBackups(string $folderId, int $retentionDays): int
    {
        $token    = $this->getAccessToken();
        $cutoff   = now()->subDays($retentionDays)->format('c');
        $deleted  = 0;

        $listResp = Http::withToken($token)
            ->get('https://www.googleapis.com/drive/v3/files', [
                'q'      => "'{$folderId}' in parents and createdTime < '{$cutoff}' and trashed = false",
                'fields' => 'files(id,name)',
            ]);

        foreach ($listResp->json('files', []) as $file) {
            Http::withToken($token)->delete("https://www.googleapis.com/drive/v3/files/{$file['id']}");
            $deleted++;
        }

        return $deleted;
    }

    // ── OAuth2 / JWT ────────────────────────────────────────────────────────

    private function getAccessToken(): string
    {
        if ($this->accessToken && time() < $this->tokenExpiry - 30) {
            return $this->accessToken;
        }

        $jwt  = $this->buildJwt();
        $resp = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt,
        ]);

        if (! $resp->successful()) {
            throw new \RuntimeException('Google OAuth failed: ' . $resp->body());
        }

        $this->accessToken = $resp->json('access_token');
        $this->tokenExpiry = time() + (int) $resp->json('expires_in', 3600);

        return $this->accessToken;
    }

    private function buildJwt(): string
    {
        $now = time();

        $header  = $this->base64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $payload = $this->base64url(json_encode([
            'iss'   => $this->credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/drive.file',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'iat'   => $now,
            'exp'   => $now + 3600,
        ]));

        $input = "{$header}.{$payload}";
        openssl_sign($input, $sig, $this->credentials['private_key'], 'SHA256');

        return "{$input}." . $this->base64url($sig);
    }

    private function base64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
