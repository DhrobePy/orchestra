<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupConfiguration extends Model
{
    protected $table = 'backup_configurations';

    protected $fillable = [
        'enabled', 'frequency', 'run_at', 'day_of_week',
        'cron_expression', 'tables', 'google_credentials',
        'google_folder_id', 'google_folder_name', 'retention_days',
        'last_backup_at', 'last_backup_status', 'last_backup_message',
    ];

    protected $casts = [
        'enabled'        => 'boolean',
        'tables'         => 'array',
        'last_backup_at' => 'datetime',
        'retention_days' => 'integer',
    ];

    /** Always returns the single config row (creates one if missing). */
    public static function get(): self
    {
        return static::firstOrCreate(
            ['id' => 1],
            ['tables' => ['all'], 'frequency' => 'daily', 'run_at' => '02:00']
        );
    }

    /** Build a cron expression from the stored schedule settings. */
    public function toCronExpression(): string
    {
        if ($this->frequency === 'custom' && $this->cron_expression) {
            return $this->cron_expression;
        }

        [$h, $m] = explode(':', $this->run_at ?? '02:00') + [0, '02', '00'];

        return match ($this->frequency) {
            'hourly' => '0 * * * *',
            'daily'  => "{$m} {$h} * * *",
            'weekly' => "{$m} {$h} * * " . ($this->day_of_week ?? '0'),
            default  => '0 2 * * *',
        };
    }
}
