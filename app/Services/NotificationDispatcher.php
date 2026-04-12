<?php

namespace App\Services;

use App\Models\NotificationChannel;
use App\Models\NotificationEventRule;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class NotificationDispatcher
{
    /**
     * Dispatch an event to all active rules matching the given event key.
     *
     * @param string $eventKey  e.g. 'order.created'
     * @param array  $vars      Template variables, e.g. ['order_number'=>'SO-001']
     */
    public static function fire(string $eventKey, array $vars = []): void
    {
        $rules = NotificationEventRule::with('channel')
            ->where('event_key', $eventKey)
            ->where('is_active', true)
            ->whereHas('channel', fn ($q) => $q->where('is_active', true))
            ->get();

        foreach ($rules as $rule) {
            try {
                $message = static::buildMessage($rule, $eventKey, $vars);
                $recipients = static::resolveRecipients($rule);

                foreach ($recipients as $chatId) {
                    match ($rule->channel->driver) {
                        'telegram' => static::sendTelegram($rule->channel, $chatId, $message),
                        'whatsapp' => static::sendWhatsApp($rule->channel, $chatId, $message),
                        'webhook'  => static::sendWebhook($rule->channel, $chatId, $message, $eventKey, $vars),
                        default    => null,
                    };
                }
            } catch (\Throwable $e) {
                Log::error("NotificationDispatcher: rule #{$rule->id} failed — {$e->getMessage()}");
            }
        }
    }

    // ── Message builder ────────────────────────────────────────────────────────

    protected static function buildMessage(NotificationEventRule $rule, string $eventKey, array $vars): string
    {
        $template = $rule->message_template
            ?: NotificationEventRule::defaultTemplate($eventKey);

        foreach ($vars as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }
        return $template;
    }

    // ── Recipient resolution ───────────────────────────────────────────────────

    protected static function resolveRecipients(NotificationEventRule $rule): array
    {
        return match ($rule->recipient_mode) {
            'channel_default' => [
                $rule->channel->config['default_chat_id']
                ?? $rule->channel->config['phone_number_id']
                ?? null,
            ],
            'role' => static::chatIdsForRole($rule->recipient_identifier, $rule->channel->driver),
            'user' => [static::chatIdForUser((int) $rule->recipient_identifier, $rule->channel->driver)],
            default => [],
        };
    }

    protected static function chatIdsForRole(string $roleName, string $driver): array
    {
        $field = $driver === 'telegram' ? 'telegram_chat_id' : 'whatsapp_number';
        return User::role($roleName)
            ->whereNotNull($field)
            ->pluck($field)
            ->toArray();
    }

    protected static function chatIdForUser(int $userId, string $driver): ?string
    {
        $field = $driver === 'telegram' ? 'telegram_chat_id' : 'whatsapp_number';
        return User::find($userId)?->$field;
    }

    // ── Telegram sender ────────────────────────────────────────────────────────

    protected static function sendTelegram(NotificationChannel $channel, ?string $chatId, string $text): void
    {
        if (!$chatId) {
            return;
        }

        $token = $channel->config['bot_token'] ?? null;
        if (!$token) {
            Log::warning("Telegram channel #{$channel->id} has no bot_token");
            return;
        }

        \Illuminate\Support\Facades\Http::timeout(10)
            ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id'    => $chatId,
                'text'       => $text,
                'parse_mode' => 'Markdown',
            ]);
    }

    // ── WhatsApp sender (Meta Cloud API) ───────────────────────────────────────

    protected static function sendWhatsApp(NotificationChannel $channel, ?string $to, string $text): void
    {
        if (!$to) {
            return;
        }

        $accessToken    = $channel->config['access_token']    ?? null;
        $phoneNumberId  = $channel->config['phone_number_id'] ?? null;
        $apiVersion     = $channel->config['api_version']     ?? 'v19.0';

        if (!$accessToken || !$phoneNumberId) {
            Log::warning("WhatsApp channel #{$channel->id} missing credentials");
            return;
        }

        \Illuminate\Support\Facades\Http::timeout(10)
            ->withToken($accessToken)
            ->post("https://graph.facebook.com/{$apiVersion}/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to'                => $to,
                'type'              => 'text',
                'text'              => ['body' => $text],
            ]);
    }

    // ── Generic webhook sender ─────────────────────────────────────────────────

    protected static function sendWebhook(
        NotificationChannel $channel,
        ?string $recipient,
        string $text,
        string $eventKey,
        array $vars
    ): void {
        $endpoint = $channel->config['endpoint'] ?? null;
        if (!$endpoint) {
            return;
        }

        $request = \Illuminate\Support\Facades\Http::timeout(10);

        $apiKey = $channel->config['api_key'] ?? null;
        if ($apiKey) {
            $request = $request->withToken($apiKey);
        }

        $request->post($endpoint, [
            'event'     => $eventKey,
            'recipient' => $recipient,
            'message'   => $text,
            'data'      => $vars,
        ]);
    }

    // ── Telegram test connection ───────────────────────────────────────────────

    public static function testTelegram(string $botToken): array
    {
        $response = \Illuminate\Support\Facades\Http::timeout(8)
            ->get("https://api.telegram.org/bot{$botToken}/getMe");

        if ($response->successful() && $response->json('ok')) {
            $bot = $response->json('result');
            return ['ok' => true, 'bot_name' => $bot['first_name'], 'username' => $bot['username']];
        }

        return ['ok' => false, 'error' => $response->json('description', 'Connection failed')];
    }
}
