<?php

namespace App\Filament\Resources\Procurement\PurchaseOrderResource\RelationManagers;

use App\Services\ProcurementService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'allPayments';

    protected static ?string $title = 'Payments';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->schema([
                DatePicker::make('payment_date')
                    ->label('Payment Date')
                    ->required()
                    ->default(today()),

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

            TextInput::make('bank_name')
                ->label('Bank Name')
                ->nullable()
                ->maxLength(255)
                ->visible(fn (\Filament\Forms\Get $get) => in_array(
                    $get('payment_method'),
                    ['bank_transfer', 'cheque']
                )),

            TextInput::make('reference_number')
                ->label('Reference / Cheque Number')
                ->nullable()
                ->maxLength(100)
                ->visible(fn (\Filament\Forms\Get $get) => $get('payment_method') !== 'cash'),

            TextInput::make('handled_by')
                ->label('Handled By (Staff Name)')
                ->nullable()
                ->maxLength(255)
                ->visible(fn (\Filament\Forms\Get $get) => $get('payment_method') === 'cash'),

            Textarea::make('remarks')
                ->label('Remarks')
                ->nullable()
                ->rows(3),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('voucher_number')
            ->columns([
                TextColumn::make('voucher_number')
                    ->label('Voucher #')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('payment_date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),

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
            ->headerActions([
                CreateAction::make()
                    ->using(function (array $data, string $model) {
                        $po = $this->getOwnerRecord();
                        $data['purchase_order_id'] = $po->id;

                        return app(ProcurementService::class)->recordPayment($data);
                    })
                    ->successNotificationTitle('Payment recorded successfully'),
            ])
            ->actions([])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }
}
