<?php

namespace App\Filament\Pages;

use App\Jobs\DatabaseBackupJob;
use App\Models\BackupConfiguration;
use App\Services\GoogleDriveService;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class BackupSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon  = null;
    protected static string|UnitEnum|null    $navigationGroup = 'Settings';
    protected static ?string                 $navigationLabel = 'Database Backup';
    protected static ?int                    $navigationSort  = 50;
    protected string                         $view            = 'filament.pages.backup-settings';

    public array $data = [];

    public function mount(): void
    {
        $cfg = BackupConfiguration::get();

        $this->form->fill([
            'enabled'             => $cfg->enabled,
            'frequency'           => $cfg->frequency,
            'run_at'              => $cfg->run_at,
            'day_of_week'         => $cfg->day_of_week,
            'cron_expression'     => $cfg->cron_expression,
            'tables'              => $cfg->tables ?? ['all'],
            'google_credentials'  => $cfg->google_credentials ? '••••••••' : '',
            'google_folder_id'    => $cfg->google_folder_id,
            'google_folder_name'  => $cfg->google_folder_name,
            'retention_days'      => $cfg->retention_days ?? 30,
        ]);
    }

    public function form(Schema $form): Schema
    {
        $allTables = $this->getAllTables();

        return $form
            ->statePath('data')
            ->schema([
                Section::make('Enable & Schedule')
                    ->columns(2)
                    ->schema([
                        Toggle::make('enabled')
                            ->label('Enable Scheduled Backups')
                            ->live()
                            ->columnSpanFull(),

                        Select::make('frequency')
                            ->label('Backup Frequency')
                            ->options([
                                'hourly'  => 'Every Hour',
                                'daily'   => 'Daily',
                                'weekly'  => 'Weekly',
                                'custom'  => 'Custom Cron',
                            ])
                            ->default('daily')
                            ->live()
                            ->required()
                            ->visible(fn ($get) => $get('enabled')),

                        TextInput::make('run_at')
                            ->label('Run at (HH:MM)')
                            ->placeholder('02:00')
                            ->visible(fn ($get) => $get('enabled') && in_array($get('frequency'), ['daily', 'weekly'])),

                        Select::make('day_of_week')
                            ->label('Day of Week')
                            ->options([
                                '0' => 'Sunday',    '1' => 'Monday',  '2' => 'Tuesday',
                                '3' => 'Wednesday', '4' => 'Thursday','5' => 'Friday',
                                '6' => 'Saturday',
                            ])
                            ->visible(fn ($get) => $get('enabled') && $get('frequency') === 'weekly'),

                        TextInput::make('cron_expression')
                            ->label('Cron Expression')
                            ->placeholder('0 2 * * *')
                            ->helperText('Format: minute hour day month weekday')
                            ->visible(fn ($get) => $get('enabled') && $get('frequency') === 'custom'),
                    ]),

                Section::make('Google Drive Connection')
                    ->schema([
                        Placeholder::make('gdrive_instructions')
                            ->label('')
                            ->content(<<<'TXT'
                                To connect Google Drive:
                                1. Go to Google Cloud Console → IAM & Admin → Service Accounts
                                2. Create a service account, give it no roles
                                3. Create a JSON key and download it
                                4. In Google Drive, share your backup folder with the service account email
                                5. Paste the entire JSON key content below
                                TXT),

                        Textarea::make('google_credentials')
                            ->label('Service Account JSON')
                            ->rows(6)
                            ->placeholder('Paste the full service account JSON key here…')
                            ->helperText('Stored encrypted. Paste the full JSON each time you update credentials.'),

                        TextInput::make('google_folder_id')
                            ->label('Drive Folder ID')
                            ->placeholder('e.g. 1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs')
                            ->helperText('Copy the ID from the folder URL: drive.google.com/drive/folders/{ID}')
                            ->maxLength(100),

                        TextInput::make('google_folder_name')
                            ->label('Folder Name (label only)')
                            ->placeholder('Orchestra Backups')
                            ->maxLength(100)
                            ->helperText('Just for your reference, not used in API calls.'),
                    ]),

                Section::make('What to Back Up')
                    ->schema([
                        CheckboxList::make('tables')
                            ->label('Tables')
                            ->options(array_merge(
                                ['all' => '✅  All Tables (recommended)'],
                                array_combine($allTables, $allTables)
                            ))
                            ->default(['all'])
                            ->columns(3)
                            ->helperText('Select "All Tables" or choose specific ones. Framework tables (cache, jobs, sessions) are excluded when using All.'),
                    ]),

                Section::make('Retention')
                    ->columns(2)
                    ->schema([
                        TextInput::make('retention_days')
                            ->label('Keep Backups For (days)')
                            ->numeric()
                            ->default(30)
                            ->minValue(1)
                            ->maxValue(365)
                            ->suffix('days'),

                        Placeholder::make('retention_note')
                            ->label('')
                            ->content('Backups older than the retention period will be automatically deleted from Google Drive after each new backup.'),
                    ]),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $cfg  = BackupConfiguration::get();

        $update = [
            'enabled'           => $data['enabled'] ?? false,
            'frequency'         => $data['frequency'],
            'run_at'            => $data['run_at'] ?? '02:00',
            'day_of_week'       => $data['day_of_week'] ?? null,
            'cron_expression'   => $data['cron_expression'] ?? null,
            'tables'            => $data['tables'] ?? ['all'],
            'google_folder_id'  => $data['google_folder_id'] ?? null,
            'google_folder_name'=> $data['google_folder_name'] ?? null,
            'retention_days'    => (int) ($data['retention_days'] ?? 30),
        ];

        // Only update credentials if a real JSON was pasted (not the masked placeholder)
        $cred = trim($data['google_credentials'] ?? '');
        if ($cred && ! str_starts_with($cred, '•')) {
            $update['google_credentials'] = $cred;
        }

        $cfg->update($update);

        Notification::make()->title('Backup settings saved.')->success()->send();
    }

    public function testConnection(): void
    {
        $cfg = BackupConfiguration::get();

        if (! $cfg->google_credentials) {
            Notification::make()->title('No credentials saved yet.')->warning()->send();
            return;
        }

        try {
            $ok = GoogleDriveService::fromJson($cfg->google_credentials)->testConnection();
            if ($ok) {
                Notification::make()->title('Google Drive connected successfully!')->success()->send();
            } else {
                Notification::make()->title('Connection failed')->body('Could not obtain an access token.')->danger()->send();
            }
        } catch (\Throwable $e) {
            Notification::make()->title('Connection error')->body($e->getMessage())->danger()->send();
        }
    }

    public function runNow(): void
    {
        $cfg = BackupConfiguration::get();

        if (! $cfg->google_credentials || ! $cfg->google_folder_id) {
            Notification::make()
                ->title('Not configured')
                ->body('Please save Google Drive credentials and folder ID first.')
                ->warning()->send();
            return;
        }

        DatabaseBackupJob::dispatch();
        Notification::make()->title('Backup job queued')->body('The backup will run in the background.')->success()->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Settings')
                ->icon('heroicon-o-check')
                ->action('save'),

            Action::make('testConnection')
                ->label('Test Drive Connection')
                ->icon('heroicon-o-signal')
                ->color('info')
                ->action('testConnection'),

            Action::make('runNow')
                ->label('Run Backup Now')
                ->icon('heroicon-o-cloud-arrow-up')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Run backup now?')
                ->modalDescription('This will dump the database and upload it to Google Drive in the background.')
                ->action('runNow'),
        ];
    }

    private function getAllTables(): array
    {
        $excluded = ['cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs',
                     'sessions', 'personal_access_tokens', 'telescope_entries',
                     'telescope_entries_tags', 'telescope_monitoring', 'password_reset_tokens'];

        return collect(DB::select('SHOW TABLES'))
            ->map(fn ($r) => array_values((array) $r)[0])
            ->reject(fn ($t) => in_array($t, $excluded))
            ->sort()
            ->values()
            ->toArray();
    }
}
