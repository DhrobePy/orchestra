<?php

namespace App\Filament\Resources\Purchasing\PurchaseOrderResource\RelationManagers;

use App\Services\ProcurementService;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GoodsReceivedNotesRelationManager extends RelationManager
{
    protected static string $relationship = 'goodsReceivedNotes';

    protected static ?string $title = 'Goods Received Notes';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->schema([
                DatePicker::make('grn_date')
                    ->label('GRN Date')
                    ->required()
                    ->default(today())
                    ->maxDate(today()),

                TextInput::make('truck_number')
                    ->label('Truck Number')
                    ->maxLength(50)
                    ->placeholder('e.g. DHK-12-1234'),

                TextInput::make('vehicle_number')
                    ->label('Vehicle Number')
                    ->nullable()
                    ->maxLength(50),
            ]),

            Grid::make(2)->schema([
                TextInput::make('expected_quantity')
                    ->label('Expected Quantity (KG)')
                    ->numeric()
                    ->nullable()
                    ->minValue(0)
                    ->step(0.01),

                TextInput::make('received_quantity')
                    ->label('Received Quantity (KG)')
                    ->numeric()
                    ->required()
                    ->minValue(0.01)
                    ->step(0.01),
            ]),

            Grid::make(2)->schema([
                Select::make('receiving_branch')
                    ->label('Unload Point')
                    ->options([
                        'Sirajganj'    => 'Sirajganj',
                        'Demra'        => 'Demra',
                        'Rampura'      => 'Rampura',
                        'Head Office'  => 'Head Office',
                        'Other'        => 'Other',
                    ])
                    ->nullable(),

                TextInput::make('driver_name')
                    ->label('Driver Name')
                    ->nullable(),
            ]),

            Select::make('payment_basis_override')
                ->label('Payment Basis Override')
                ->placeholder('Inherit from PO')
                ->options([
                    'received_qty' => 'Pay on Received Qty',
                    'expected_qty' => 'Pay on Expected Qty',
                ])
                ->nullable(),

            TextInput::make('variance_remarks')
                ->label('Variance Remarks')
                ->nullable()
                ->maxLength(500),

            Textarea::make('remarks')
                ->label('Remarks')
                ->nullable()
                ->rows(3),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('grn_number')
            ->columns([
                TextColumn::make('grn_number')
                    ->label('GRN #')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('grn_date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('truck_number')
                    ->label('Truck'),

                TextColumn::make('receiving_branch')
                    ->label('Unload Point'),

                TextColumn::make('expected_quantity')
                    ->label('Expected')
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format((float) $state, 2) : '-')
                    ->alignRight(),

                TextColumn::make('received_quantity')
                    ->label('Received')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2))
                    ->alignRight(),

                TextColumn::make('weight_variance')
                    ->label('Variance')
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format((float) $state, 2) : '-')
                    ->color(fn ($state) => match (true) {
                        $state === null          => 'gray',
                        (float) $state < -0.001  => 'danger',
                        (float) $state > 0.001   => 'success',
                        default                  => 'gray',
                    })
                    ->alignRight(),

                TextColumn::make('total_value')
                    ->label('Value')
                    ->formatStateUsing(fn ($state) => 'BDT ' . number_format((float) $state, 2))
                    ->alignRight(),

                TextColumn::make('grn_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft'     => 'gray',
                        'verified'  => 'success',
                        'posted'    => 'info',
                        'cancelled' => 'danger',
                        default     => 'gray',
                    }),
            ])
            ->headerActions([
                CreateAction::make()
                    ->using(function (array $data, string $model) {
                        $po = $this->getOwnerRecord();
                        $data['purchase_order_id'] = $po->id;

                        return app(ProcurementService::class)->recordGoodsReceived($data);
                    })
                    ->successNotificationTitle('GRN recorded successfully')
                    ->visible(fn () => $this->getOwnerRecord()->canBeReceived()),
            ])
            ->actions([
                EditAction::make()
                    ->visible(fn ($record) => $record->grn_status === 'draft'),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }
}
