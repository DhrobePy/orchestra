<?php

namespace App\Filament\Resources\Sales;

use App\Filament\Resources\Sales\CustomerPaymentResource\Pages;
use App\Models\BankAccount;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerPayment;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;

class CustomerPaymentResource extends Resource
{
    protected static ?string $model                          = CustomerPayment::class;
    protected static string|\BackedEnum|null $navigationIcon  = null;
    protected static ?string $navigationLabel                 = 'Customer Payments';
    protected static string|\UnitEnum|null $navigationGroup   = 'Sales';
    protected static ?int    $navigationSort                  = 4;
    protected static ?string $modelLabel                      = 'Payment';
    protected static ?string $pluralModelLabel                = 'Customer Payments';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Payment Details')->columns(2)->schema([

                Select::make('customer_id')
                    ->label('Customer')
                    ->options(Customer::orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->required(),

                DatePicker::make('payment_date')
                    ->label('Payment Date')
                    ->default(now()->toDateString())
                    ->required(),

                TextInput::make('amount')
                    ->label('Amount (৳)')
                    ->numeric()
                    ->minValue(0.01)
                    ->required()
                    ->prefix('৳'),

                Select::make('payment_method')
                    ->label('Payment Method')
                    ->options([
                        'cash'           => 'Cash',
                        'bank_transfer'  => 'Bank Transfer',
                        'cheque'         => 'Cheque',
                        'mobile_banking' => 'Mobile Banking',
                    ])
                    ->default('cash')
                    ->required()
                    ->live(),

                Select::make('bank_account_id')
                    ->label('Bank Account')
                    ->options(BankAccount::activeOptions())
                    ->searchable()
                    ->nullable()
                    ->visible(fn (Get $get): bool => $get('payment_method') === 'bank_transfer')
                    ->columnSpanFull()
                    ->helperText('Select the bank account into which this payment was deposited.'),

                TextInput::make('reference')
                    ->label('Reference / Cheque No.')
                    ->maxLength(100)
                    ->placeholder(fn (Get $get): string => match($get('payment_method')) {
                        'bank_transfer'  => 'Transaction / transfer reference',
                        'cheque'         => 'Cheque number',
                        'mobile_banking' => 'Transaction ID',
                        default          => 'Optional reference',
                    }),

                Select::make('branch_id')
                    ->label('Branch')
                    ->options(Branch::orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->nullable(),

                Textarea::make('notes')
                    ->label('Notes')
                    ->columnSpanFull()
                    ->rows(2),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('payment_date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('amount')
                    ->label('Amount (৳)')
                    ->formatStateUsing(fn ($state) => '৳ ' . number_format((float) $state, 2))
                    ->alignRight()
                    ->color('success')
                    ->weight('bold')
                    ->sortable(),

                TextColumn::make('payment_method')
                    ->label('Method')
                    ->badge()
                    ->formatStateUsing(fn ($state) => CustomerPayment::methodLabel($state))
                    ->color('info'),

                TextColumn::make('bankAccount.bank_name')
                    ->label('Bank')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('reference')
                    ->label('Reference')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('allocations_count')
                    ->label('Orders')
                    ->counts('allocations')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => $state === 'confirmed' ? 'success' : 'danger'),

                TextColumn::make('created_at')
                    ->label('Recorded At')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('customer_id')
                    ->label('Customer')
                    ->options(Customer::orderBy('name')->pluck('name', 'id'))
                    ->searchable(),

                SelectFilter::make('payment_method')
                    ->label('Method')
                    ->options([
                        'cash'           => 'Cash',
                        'bank_transfer'  => 'Bank Transfer',
                        'cheque'         => 'Cheque',
                        'mobile_banking' => 'Mobile Banking',
                    ]),

                SelectFilter::make('status')
                    ->options(['confirmed' => 'Confirmed', 'reversed' => 'Reversed']),

                Filter::make('date_range')
                    ->label('Date Range')
                    ->form([
                        DatePicker::make('from')->label('From'),
                        DatePicker::make('to')->label('To'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('payment_date', '>=', $data['from']))
                            ->when($data['to'],   fn ($q) => $q->whereDate('payment_date', '<=', $data['to']));
                    }),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->defaultSort('payment_date', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCustomerPayments::route('/'),
            'create' => Pages\CreateCustomerPayment::route('/create'),
            'view'   => Pages\ViewCustomerPayment::route('/{record}'),
            'edit'   => Pages\EditCustomerPayment::route('/{record}/edit'),
        ];
    }
}
