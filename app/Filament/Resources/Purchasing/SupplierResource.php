<?php

namespace App\Filament\Resources\Purchasing;

use App\Filament\Resources\Purchasing\SupplierResource\Pages;
use App\Models\Supplier;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Purchasing';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'company_name';

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::active()->count() ?: null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Company Details')
                ->columns(2)
                ->schema([
                    FileUpload::make('photo')
                        ->label('Profile Photo')
                        ->image()
                        ->imageEditor()
                        ->disk('public')
                        ->directory('avatars/suppliers')
                        ->maxSize(4096)
                        ->columnSpanFull()
                        ->nullable(),

                    TextInput::make('company_name')
                        ->label('Company Name')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    TextInput::make('supplier_code')
                        ->label('Supplier Code')
                        ->disabled()
                        ->placeholder('Auto-generated')
                        ->dehydrated(false),

                    Select::make('supplier_type')
                        ->label('Supplier Type')
                        ->options([
                            'local'         => 'Local',
                            'international' => 'International',
                        ])
                        ->default('local')
                        ->required(),

                    Select::make('status')
                        ->label('Status')
                        ->options([
                            'active'   => 'Active',
                            'inactive' => 'Inactive',
                            'blocked'  => 'Blocked',
                        ])
                        ->default('active')
                        ->required(),
                ]),

            Section::make('Contact Information')
                ->columns(2)
                ->schema([
                    TextInput::make('contact_person')
                        ->label('Contact Person')
                        ->maxLength(255),

                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->maxLength(255),

                    TextInput::make('phone')
                        ->label('Phone')
                        ->tel()
                        ->maxLength(50),

                    TextInput::make('mobile')
                        ->label('Mobile')
                        ->tel()
                        ->maxLength(50),
                ]),

            Section::make('Address')
                ->columns(2)
                ->schema([
                    Textarea::make('address')
                        ->label('Address')
                        ->rows(3)
                        ->columnSpanFull(),

                    TextInput::make('city')
                        ->label('City')
                        ->maxLength(100),

                    TextInput::make('district')
                        ->label('District')
                        ->maxLength(100),

                    TextInput::make('country')
                        ->label('Country')
                        ->default('Bangladesh')
                        ->maxLength(100),
                ]),

            Section::make('Financial Terms')
                ->columns(2)
                ->schema([
                    Select::make('payment_terms')
                        ->label('Payment Terms')
                        ->options([
                            'cod'     => 'Cash on Delivery',
                            'net7'    => 'Net 7 Days',
                            'net15'   => 'Net 15 Days',
                            'net30'   => 'Net 30 Days',
                            'net45'   => 'Net 45 Days',
                            'net60'   => 'Net 60 Days',
                            'advance' => 'Advance Payment',
                        ])
                        ->default('cod')
                        ->required(),

                    TextInput::make('credit_limit')
                        ->label('Credit Limit')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->prefix('BDT'),

                    TextInput::make('currency')
                        ->label('Currency')
                        ->default('BDT')
                        ->maxLength(10),

                    TextInput::make('tax_id')
                        ->label('Tax ID (VAT/TIN)')
                        ->maxLength(100),
                ]),

            Section::make('Banking Details')
                ->collapsible()
                ->collapsed()
                ->columns(2)
                ->schema([
                    TextInput::make('bank_name')
                        ->label('Bank Name')
                        ->maxLength(255),

                    TextInput::make('bank_account_number')
                        ->label('Account Number')
                        ->maxLength(100),

                    TextInput::make('bank_routing_number')
                        ->label('Routing Number')
                        ->maxLength(100),
                ]),

            Section::make('Notes')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Textarea::make('notes')
                        ->label('Internal Notes')
                        ->rows(4)
                        ->columnSpanFull(),
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
                        ->defaultImageUrl(fn () => 'https://ui-avatars.com/api/?name=Supplier&background=random&size=96')
                        ->columnSpan(1),

                    Section::make()
                        ->columnSpan(3)
                        ->schema([
                            TextEntry::make('company_name')
                                ->label('Company Name')
                                ->size(\Filament\Support\Enums\TextSize::Large)
                                ->weight(\Filament\Support\Enums\FontWeight::Bold),

                            TextEntry::make('contact_person')
                                ->label('Contact Person')
                                ->placeholder('—'),

                            Grid::make(3)->schema([
                                TextEntry::make('phone')->label('Phone')->placeholder('—'),
                                TextEntry::make('email')->label('Email')->placeholder('—'),
                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'active'   => 'success',
                                        'inactive' => 'warning',
                                        'blocked'  => 'danger',
                                        default    => 'gray',
                                    }),
                            ]),
                        ])
                        ->extraAttributes(['style' => 'background:transparent;border:none;box-shadow:none;padding:0;']),
                ]),

            Section::make('Details')
                ->columns(3)
                ->schema([
                    TextEntry::make('supplier_code')->label('Supplier Code'),
                    TextEntry::make('payment_terms')->label('Payment Terms'),
                    TextEntry::make('current_balance')
                        ->label('Balance')
                        ->formatStateUsing(fn ($state) => 'BDT ' . number_format((float) $state, 2)),
                    TextEntry::make('address')->label('Address')->placeholder('—'),
                    TextEntry::make('city')->label('City')->placeholder('—'),
                    TextEntry::make('tax_id')->label('Tax ID')->placeholder('—'),
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
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->company_name ?? 'S') . '&background=random&size=40')
                    ->size(40),

                TextColumn::make('supplier_code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable(),

                TextColumn::make('payment_terms')
                    ->label('Payment Terms')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'cod'     => 'COD',
                        'net7'    => 'Net 7',
                        'net15'   => 'Net 15',
                        'net30'   => 'Net 30',
                        'net45'   => 'Net 45',
                        'net60'   => 'Net 60',
                        'advance' => 'Advance',
                        default   => $state,
                    }),

                TextColumn::make('current_balance')
                    ->label('Balance')
                    ->money('BDT')
                    ->sortable()
                    ->alignRight(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active'   => 'success',
                        'inactive' => 'warning',
                        'blocked'  => 'danger',
                        default    => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active'   => 'Active',
                        'inactive' => 'Inactive',
                        'blocked'  => 'Blocked',
                    ]),

                SelectFilter::make('supplier_type')
                    ->label('Type')
                    ->options([
                        'local'         => 'Local',
                        'international' => 'International',
                    ]),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'view'   => Pages\ViewSupplier::route('/{record}'),
            'edit'   => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }
}
