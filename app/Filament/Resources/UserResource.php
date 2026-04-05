<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\RoleConfiguration;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon  = 'heroicon-o-users';
    protected static string|\UnitEnum|null   $navigationGroup = 'Settings';
    protected static ?string                 $navigationLabel = 'Users';
    protected static ?int                    $navigationSort  = 5;

    // ── Access control ─────────────────────────────────────────────────────────

    public static function canViewAny(): bool
    {
        return static::adminCheck();
    }

    public static function canCreate(): bool
    {
        return static::adminCheck();
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return static::adminCheck();
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return static::adminCheck();
    }

    private static function adminCheck(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }
        if ($user->hasRole('super_admin')) {
            return true;
        }
        try {
            $roleId = $user->roles->first()?->id;
            if ($roleId) {
                $config = RoleConfiguration::where('role_id', $roleId)->first();
                return (bool) $config?->bypass_all_restrictions;
            }
        } catch (\Throwable) {
            return true; // fail-open during initial setup
        }
        return false;
    }

    // ── Form ───────────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([

                Section::make('Account Details')->schema([
                    TextInput::make('name')
                        ->label('Full Name')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('email')
                        ->label('Email Address')
                        ->email()
                        ->required()
                        ->unique(User::class, 'email', ignoreRecord: true)
                        ->maxLength(255),
                ])->columnSpan(1),

                Section::make('Security & Roles')->schema([
                    TextInput::make('password')
                        ->label('Password')
                        ->password()
                        ->revealable()
                        ->dehydrateStateUsing(fn (?string $state) => filled($state) ? Hash::make($state) : null)
                        ->dehydrated(fn (?string $state) => filled($state))
                        ->required(fn (string $operation) => $operation === 'create')
                        ->minLength(8)
                        ->hint(fn (string $operation) => $operation === 'edit' ? 'Leave blank to keep current password' : null),

                    Select::make('roles')
                        ->label('Roles')
                        ->multiple()
                        ->relationship('roles', 'name')
                        ->options(fn () => Role::pluck('name', 'id'))
                        ->preload()
                        ->searchable(),
                ])->columnSpan(1),

            ]),
        ]);
    }

    // ── Table ──────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->separator(',')
                    ->color('primary'),

                TextColumn::make('email_verified_at')
                    ->label('Verified')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->placeholder('Not verified'),

                TextColumn::make('created_at')
                    ->label('Joined')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('email_verified_at')
                    ->label('Email Verified')
                    ->nullable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    // ── Pages ──────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
