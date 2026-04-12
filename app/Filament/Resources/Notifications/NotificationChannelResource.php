<?php

namespace App\Filament\Resources\Notifications;

use App\Filament\Resources\Notifications\NotificationChannelResource\Pages;
use App\Models\NotificationChannel;
use App\Services\NotificationDispatcher;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class NotificationChannelResource extends Resource
{
    protected static ?string $model = NotificationChannel::class;
    protected static string|\BackedEnum|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Notification Channels';
    protected static string|\UnitEnum|null $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 30;

    // ── Form ──────────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Channel Details')->schema([
                Grid::make(2)->schema([
                    TextInput::make('name')
                        ->label('Channel Name')
                        ->placeholder('e.g. Sales Telegram Group')
                        ->required()
                        ->maxLength(100),

                    Select::make('driver')
                        ->label('Driver / Platform')
                        ->options(NotificationChannel::driverOptions())
                        ->required()
                        ->live(),
                ]),

                Textarea::make('description')
                    ->label('Description')
                    ->rows(2)
                    ->nullable(),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]),

            Section::make('Configuration')
                ->description('Fill in the credentials for the selected driver.')
                ->schema([
                    // Telegram fields
                    TextInput::make('config.bot_token')
                        ->label('Bot Token')
                        ->placeholder('1234567890:AAxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx')
                        ->password()
                        ->revealable()
                        ->visible(fn ($get) => $get('driver') === 'telegram'),

                    TextInput::make('config.default_chat_id')
                        ->label('Default Chat ID')
                        ->placeholder('Negative for groups, e.g. -1001234567890')
                        ->helperText('Group chat IDs are negative numbers. Get it via @getidsbot.')
                        ->visible(fn ($get) => $get('driver') === 'telegram'),

                    // WhatsApp fields
                    TextInput::make('config.access_token')
                        ->label('Access Token')
                        ->password()
                        ->revealable()
                        ->visible(fn ($get) => $get('driver') === 'whatsapp'),

                    TextInput::make('config.phone_number_id')
                        ->label('Phone Number ID')
                        ->placeholder('From Meta Developer Console')
                        ->visible(fn ($get) => $get('driver') === 'whatsapp'),

                    TextInput::make('config.api_version')
                        ->label('API Version')
                        ->placeholder('v19.0')
                        ->default('v19.0')
                        ->visible(fn ($get) => $get('driver') === 'whatsapp'),

                    // Webhook fields
                    TextInput::make('config.endpoint')
                        ->label('Endpoint URL')
                        ->url()
                        ->visible(fn ($get) => $get('driver') === 'webhook'),

                    TextInput::make('config.api_key')
                        ->label('API Key / Bearer Token')
                        ->password()
                        ->revealable()
                        ->visible(fn ($get) => $get('driver') === 'webhook'),
                ]),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Channel')
                    ->weight('bold')
                    ->searchable(),

                TextColumn::make('driver')
                    ->label('Platform')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'telegram'  => 'info',
                        'whatsapp'  => 'success',
                        'webhook'   => 'gray',
                        default     => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => NotificationChannel::driverOptions()[$state] ?? $state),

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(60)
                    ->placeholder('—'),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                TextColumn::make('rules_count')
                    ->label('Rules')
                    ->counts('rules')
                    ->badge()
                    ->color('primary'),
            ])
            ->actions([
                Action::make('test')
                    ->label('Test')
                    ->icon('heroicon-o-signal')
                    ->color('info')
                    ->visible(fn (NotificationChannel $record) => $record->driver === 'telegram')
                    ->action(function (NotificationChannel $record) {
                        $result = NotificationDispatcher::testTelegram(
                            $record->config['bot_token'] ?? ''
                        );
                        if ($result['ok']) {
                            Notification::make()
                                ->title('Telegram connection OK')
                                ->body("Bot: {$result['bot_name']} (@{$result['username']})")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Telegram connection failed')
                                ->body($result['error'])
                                ->danger()
                                ->send();
                        }
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    // ── Pages ─────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListNotificationChannels::route('/'),
            'create' => Pages\CreateNotificationChannel::route('/create'),
            'edit'   => Pages\EditNotificationChannel::route('/{record}/edit'),
        ];
    }
}
