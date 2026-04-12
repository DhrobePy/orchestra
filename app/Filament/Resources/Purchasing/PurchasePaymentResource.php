<?php

namespace App\Filament\Resources\Purchasing;

use App\Filament\Concerns\ChecksStaffPanel;
use App\Filament\Resources\Purchasing\PurchasePaymentResource\Pages;
use App\Models\PurchaseOrder;
use App\Models\PurchasePayment;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PurchasePaymentResource extends Resource
{
    use ChecksStaffPanel;

    protected static ?string $model = PurchasePayment::class;


    protected static string|\UnitEnum|null $navigationGroup = 'Purchasing';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'voucher_number';

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', 'draft')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Payment Details')
                ->schema([
                    Grid::make(3)->schema([
                        DatePicker::make('payment_date')
                            ->label('Payment Date')
                            ->required()
                            ->default(today())
                            ->maxDate(today()),

                        TextInput::make('amount_paid')
                            ->label('Amount Paid')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->step(0.01)
                            ->prefix('BDT'),

                        Select::make('payment_type')
                            ->label('Payment Type')
                            ->options([
                                'advance'    => 'Advance',
                                'regular'    => 'Regular',
                                'final'      => 'Final Settlement',
                                'adjustment' => 'Adjustment',
                            ])
                            ->default('regular')
                            ->required(),
                    ]),

                    Select::make('purchase_order_id')
                        ->label('Purchase Order')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->live()
                        ->getSearchResultsUsing(function (string $search) {
                            return PurchaseOrder::whereNotIn('po_status', ['cancelled'])
                                ->where('balance_payable', '>', 0)
                                ->where(function ($q) use ($search) {
                                    $q->where('po_number', 'like', "%{$search}%")
                                      ->orWhere('supplier_name', 'like', "%{$search}%");
                                })
                                ->limit(50)
                                ->pluck('po_number', 'id')
                                ->toArray();
                        })
                        ->getOptionLabelUsing(fn ($value) => PurchaseOrder::find($value)?->po_number ?? $value),

                    Select::make('payment_method')
                        ->label('Payment Method')
                        ->options([
                            'bank_transfer'  => 'Bank Transfer',
                            'cash'           => 'Cash',
                            'cheque'         => 'Cheque',
                            'mobile_banking' => 'Mobile Banking',
                            'other'          => 'Other',
                        ])
                        ->default('cash')
                        ->required()
                        ->live(),
                ]),

            Section::make('Payment Method Details')
                ->visible(fn (Get $get) => $get('payment_method') !== null)
                ->schema([
                    TextInput::make('bank_name')
                        ->label('Bank Name')
                        ->nullable()
                        ->maxLength(255)
                        ->visible(fn (Get $get) => in_array($get('payment_method'), ['bank_transfer', 'cheque'])),

                    TextInput::make('reference_number')
                        ->label('Reference / Cheque / Transaction No.')
                        ->nullable()
                        ->maxLength(100)
                        ->visible(fn (Get $get) => $get('payment_method') !== 'cash'),

                    TextInput::make('handled_by')
                        ->label('Cash Handled By (Staff Name)')
                        ->nullable()
                        ->maxLength(255)
                        ->visible(fn (Get $get) => $get('payment_method') === 'cash'),

                    Textarea::make('remarks')
                        ->label('Remarks')
                        ->nullable()
                        ->rows(3),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('voucher_number')
                    ->label('Voucher #')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('payment_date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('po_number')
                    ->label('PO #')
                    ->searchable(),

                TextColumn::make('supplier_name')
                    ->label('Supplier')
                    ->searchable(),

                TextColumn::make('amount_paid')
                    ->label('Amount')
                    ->formatStateUsing(fn ($state) => 'BDT ' . number_format((float) $state, 2))
                    ->alignRight()
                    ->sortable(),

                TextColumn::make('payment_method')
                    ->label('Method')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'bank_transfer'  => 'info',
                        'cash'           => 'gray',
                        'cheque'         => 'warning',
                        'mobile_banking' => 'primary',
                        'other'          => 'secondary',
                        default          => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'bank_transfer'  => 'Bank Transfer',
                        'cash'           => 'Cash',
                        'cheque'         => 'Cheque',
                        'mobile_banking' => 'Mobile Banking',
                        'other'          => 'Other',
                        default          => $state,
                    }),

                TextColumn::make('payment_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'advance'    => 'warning',
                        'regular'    => 'info',
                        'final'      => 'success',
                        'adjustment' => 'gray',
                        default      => 'gray',
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'posted'    => 'success',
                        'draft'     => 'warning',
                        'cancelled' => 'danger',
                        default     => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft'     => 'Draft',
                        'posted'    => 'Posted',
                        'cancelled' => 'Cancelled',
                    ]),

                SelectFilter::make('payment_method')
                    ->label('Method')
                    ->options([
                        'bank_transfer'  => 'Bank Transfer',
                        'cash'           => 'Cash',
                        'cheque'         => 'Cheque',
                        'mobile_banking' => 'Mobile Banking',
                        'other'          => 'Other',
                    ]),

                SelectFilter::make('payment_type')
                    ->label('Type')
                    ->options([
                        'advance'    => 'Advance',
                        'regular'    => 'Regular',
                        'final'      => 'Final Settlement',
                        'adjustment' => 'Adjustment',
                    ]),
            ])
            ->actions([
                EditAction::make()
                    ->visible(fn ($record) => $record->status === 'draft'),
                ViewAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('payment_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPurchasePayments::route('/'),
            'create' => Pages\CreatePurchasePayment::route('/create'),
            'edit'   => Pages\EditPurchasePayment::route('/{record}/edit'),
        ];
    }
}
