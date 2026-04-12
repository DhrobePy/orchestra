<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationChannel extends Model
{
    protected $fillable = ['name', 'driver', 'config', 'description', 'is_active'];

    protected $casts = [
        'config'    => 'array',
        'is_active' => 'boolean',
    ];

    // ── Driver labels ──────────────────────────────────────────────────────────

    public static function driverOptions(): array
    {
        return [
            'telegram'  => 'Telegram Bot',
            'whatsapp'  => 'WhatsApp (Meta Cloud API)',
            'webhook'   => 'Generic Webhook',
        ];
    }

    public function getDriverLabelAttribute(): string
    {
        return static::driverOptions()[$this->driver] ?? $this->driver;
    }

    // ── Config field labels per driver ─────────────────────────────────────────

    public static function configFields(string $driver): array
    {
        return match ($driver) {
            'telegram' => [
                'bot_token'       => 'Bot Token',
                'default_chat_id' => 'Default Chat ID (group: negative number)',
            ],
            'whatsapp' => [
                'access_token'     => 'Access Token (Bearer)',
                'phone_number_id'  => 'Phone Number ID',
                'api_version'      => 'API Version (e.g. v19.0)',
            ],
            'webhook' => [
                'endpoint'  => 'Endpoint URL',
                'api_key'   => 'API Key / Bearer Token',
            ],
            default => [],
        };
    }

    // ── Relations ──────────────────────────────────────────────────────────────

    public function rules(): HasMany
    {
        return $this->hasMany(NotificationEventRule::class, 'channel_id');
    }
}
