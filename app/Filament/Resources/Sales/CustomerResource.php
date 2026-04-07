<?php

namespace App\Filament\Resources\Sales;

use App\Filament\Resources\Sales\CustomerResource\Pages;
use App\Models\Customer;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
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
                    ->options([
                        'cod'     => 'COD',
                        'advance' => 'Advance',
                        'credit'  => 'Credit',
                    ]),
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([1 => 'Active', 0 => 'Inactive']),
            ])
            ->actions([
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
            'index'  => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit'   => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::active()->count() ?: null;
    }
}
