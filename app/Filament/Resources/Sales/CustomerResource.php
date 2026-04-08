<?php

namespace App\Filament\Resources\Sales;

use App\Filament\Resources\Sales\CustomerResource\Pages;
use App\Filament\Resources\Sales\CustomerResource\RelationManagers\LedgerEntriesRelationManager;
use App\Models\Customer;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Customer Details')
                ->columns(2)
                ->schema([
                    FileUpload::make('photo')
                        ->label('Profile Photo')
                        ->image()
                        ->imageEditor()
                        ->disk('public')
                        ->directory('avatars/customers')
                        ->maxSize(4096)
                        ->columnSpanFull()
                        ->nullable(),

                    TextInput::make('name')
                        ->label('Customer Name')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    TextInput::make('company_name')
                        ->label('Company / Business Name')
                        ->maxLength(255),

                    TextInput::make('contact_person')
                        ->label('Contact Person')
                        ->maxLength(255),

                    TextInput::make('phone')
                        ->label('Phone')
                        ->tel()
                        ->maxLength(50),

                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->maxLength(255),

                    Textarea::make('address')
                        ->label('Address')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),

            Section::make('Credit & Payment Terms')
                ->columns(2)
                ->schema([
                    Select::make('payment_terms')
                        ->label('Payment Terms')
                        ->options([
                            'cod'     => 'COD (Cash on Delivery)',
                            'advance' => 'Advance Payment',
                            'credit'  => 'Credit',
                        ])
                        ->default('cod')
                        ->required(),

                    TextInput::make('credit_limit')
                        ->label('Credit Limit (৳)')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->prefix('৳')
                        ->helperText('Maximum credit allowed. 0 = no credit.'),

                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),
                ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(4)
                ->schema([
                    ImageEntry::make('photo')
                        ->label('')
                        ->disk('public')
                        ->circular()
                        ->size(96)
                        ->defaultImageUrl(fn () => 'https://ui-avatars.com/api/?name=Customer&background=random&size=96')
                        ->columnSpan(1),

                    Section::make()
                        ->columnSpan(3)
                        ->schema([
                            TextEntry::make('name')
                                ->label('Customer Name')
                                ->size(\Filament\Support\Enums\TextSize::Large)
                                ->weight(\Filament\Support\Enums\FontWeight::Bold),

                            TextEntry::make('company_name')
                                ->label('Company')
                                ->placeholder('—'),

                            Grid::make(3)->schema([
                                TextEntry::make('phone')->label('Phone')->placeholder('—'),
                                TextEntry::make('email')->label('Email')->placeholder('—'),
                                TextEntry::make('payment_terms')
                                    ->label('Terms')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'cod'     => 'gray',
                                        'advance' => 'warning',
                                        'credit'  => 'info',
                                        default   => 'gray',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'cod'     => 'COD',
                                        'advance' => 'Advance',
                                        'credit'  => 'Credit',
                                        default   => strtoupper($state),
                                    }),
                            ]),
                        ])
                        ->extraAttributes(['style' => 'background:transparent;border:none;box-shadow:none;padding:0;']),
                ]),

            Section::make('Credit Information')
                ->columns(3)
                ->schema([
                    TextEntry::make('credit_limit')
                        ->label('Credit Limit')
                        ->formatStateUsing(fn ($state) => '৳ ' . number_format((float) $state, 2)),

                    TextEntry::make('credit_balance')
                        ->label('Balance Due')
                        ->formatStateUsing(fn ($state) => '৳ ' . number_format((float) $state, 2))
                        ->color(fn ($state) => (float) $state > 0 ? 'danger' : 'success'),

                    TextEntry::make('address')->label('Address')->placeholder('—'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('photo')
                    ->label('')
                    ->disk('public')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name ?? 'C') . '&background=random&size=40')
                    ->size(40),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('company_name')
                    ->label('Company')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable(),

                TextColumn::make('payment_terms')
                    ->label('Terms')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'cod'     => 'gray',
                        'advance' => 'warning',
                        'credit'  => 'info',
                        default   => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'cod'     => 'COD',
                        'advance' => 'Advance',
                        'credit'  => 'Credit',
                        default   => strtoupper($state),
                    }),

                TextColumn::make('credit_limit')
                    ->label('Credit Limit')
                    ->formatStateUsing(fn ($state) => '৳ ' . number_format((float) $state, 2))
                    ->alignRight()
                    ->sortable(),

                TextColumn::make('credit_balance')
                    ->label('Balance Due')
                    ->formatStateUsing(fn ($state) => '৳ ' . number_format((float) $state, 2))
                    ->alignRight()
                    ->color(fn ($state) => (float) $state > 0 ? 'danger' : 'success')
                    ->sortable(),

                TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'danger')
                    ->formatStateUsing(fn ($state) => $state ? 'Active' : 'Inactive'),
            ])
            ->filters([
                SelectFilter::make('payment_terms')
                    ->options(['cod' => 'COD', 'advance' => 'Advance', 'credit' => 'Credit']),
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([1 => 'Active', 0 => 'Inactive']),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            LedgerEntriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'view'   => Pages\ViewCustomer::route('/{record}'),
            'edit'   => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::active()->count() ?: null;
    }
}
