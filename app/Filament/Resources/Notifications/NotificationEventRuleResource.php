<?php

namespace App\Filament\Resources\Notifications;

use App\Filament\Resources\Notifications\NotificationEventRuleResource\Pages;
use App\Models\NotificationChannel;
use App\Models\NotificationEventRule;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;

class NotificationEventRuleResource extends Resource
{
    protected static ?string $model = NotificationEventRule::class;
    protected static string|\BackedEnum|null $navigationIcon = null;
    protected static ?string $navigationLabel = 'Notification Rules';
    protected static string|\UnitEnum|null $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 31;

    // ── Form ──────────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Rule Configuration')->schema([
                Grid::make(2)->schema([
                    Select::make('event_key')
                        ->label('Event')
                        ->options(NotificationEventRule::eventOptions())
                        ->required()
                        ->searchable(),

                    Select::make('channel_id')
                        ->label('Send via Channel')
                        ->options(
                            NotificationChannel::where('is_active', true)
                                ->pluck('name', 'id')
                        )
                        ->required()
                        ->searchable(),
                ]),

                Grid::make(2)->schema([
                    Select::make('recipient_mode')
                        ->label('Recipient Mode')
                        ->options(NotificationEventRule::recipientModeOptions())
                        ->default('channel_default')
                        ->live()
                        ->required(),

                    Select::make('recipient_identifier')
                        ->label('Role / User')
                        ->visible(fn ($get) => in_array($get('recipient_mode'), ['role', 'user']))
                        ->options(function ($get) {
                            if ($get('recipient_mode') === 'role') {
                                return Role::pluck('name', 'name')->toArray();
                            }
                            return User::orderBy('name')->pluck('name', 'id')->toArray();
                        })
                        ->searchable()
                        ->nullable(),
                ]),

                Textarea::make('message_template')
                    ->label('Custom Message Template')
                    ->rows(4)
                    ->placeholder('Leave blank to use the built-in default template.')
                    ->helperText('Available variables: {order_number}, {customer}, {total}, {status}, {supplier}, {amount}, {created_by}, {qty}')
                    ->nullable(),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('event_key')
                    ->label('Event')
                    ->formatStateUsing(fn (string $state) => NotificationEventRule::eventOptions()[$state] ?? $state)
                    ->searchable(),

                TextColumn::make('channel.name')
                    ->label('Channel')
                    ->badge()
                    ->color('info'),

                TextColumn::make('channel.driver')
                    ->label('Platform')
                    ->badge()
                    ->color(fn (?string $state) => match ($state) {
                        'telegram'  => 'info',
                        'whatsapp'  => 'success',
                        default     => 'gray',
                    }),

                TextColumn::make('recipient_mode')
                    ->label('Recipient')
                    ->formatStateUsing(fn (string $state) =>
                        NotificationEventRule::recipientModeOptions()[$state] ?? $state
                    ),

                TextColumn::make('recipient_identifier')
                    ->label('Role / User')
                    ->placeholder('—'),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('event_key')
                    ->label('Event')
                    ->options(NotificationEventRule::eventOptions()),

                TernaryFilter::make('is_active')->label('Active'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('event_key');
    }

    // ── Pages ─────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListNotificationEventRules::route('/'),
            'create' => Pages\CreateNotificationEventRule::route('/create'),
            'edit'   => Pages\EditNotificationEventRule::route('/{record}/edit'),
        ];
    }
}
