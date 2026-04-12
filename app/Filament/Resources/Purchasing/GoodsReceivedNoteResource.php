<?php

namespace App\Filament\Resources\Purchasing;

use App\Filament\Concerns\ChecksStaffPanel;
use App\Filament\Resources\Purchasing\GoodsReceivedNoteResource\Pages;
use App\Models\GoodsReceivedNote;
use App\Models\PurchaseOrder;
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
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class GoodsReceivedNoteResource extends Resource
{
    use ChecksStaffPanel;

    protected static ?string $model = GoodsReceivedNote::class;


    protected static string|\UnitEnum|null $navigationGroup = 'Purchasing';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'grn_number';

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::whereDate('created_at', today())->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('GRN Details')
                ->schema([
                    Grid::make(3)->schema([
                        DatePicker::make('grn_date')
                            ->label('GRN Date')
                            ->required()
                            ->default(today())
                            ->maxDate(today()),

                        TextInput::make('truck_number')
                            ->label('Truck Number')
                            ->maxLength(50),

                        TextInput::make('vehicle_number')
                            ->label('Vehicle Number')
                            ->nullable()
                            ->maxLength(50),
                    ]),

                    Select::make('purchase_order_id')
                        ->label('Purchase Order')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->live()
                        ->getSearchResultsUsing(function (string $search) {
                            return PurchaseOrder::whereIn('po_status', ['draft', 'submitted', 'approved', 'partial'])
                                ->where(function ($q) use ($search) {
                                    $q->where('po_number', 'like', "%{$search}%")
                                      ->orWhere('supplier_name', 'like', "%{$search}%");
                                })
                                ->limit(50)
                                ->pluck('po_number', 'id')
                                ->toArray();
                        })
                        ->getOptionLabelUsing(fn ($value) => PurchaseOrder::find($value)?->po_number ?? $value),

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
                ]),

            Section::make('Delivery Details')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('receiving_branch')
                            ->label('Unload Point')
                            ->options([
                                'Sirajganj'   => 'Sirajganj',
                                'Demra'       => 'Demra',
                                'Rampura'     => 'Rampura',
                                'Head Office' => 'Head Office',
                                'Other'       => 'Other',
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
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('grn_number')
                    ->label('GRN #')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('grn_date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('po_number')
                    ->label('PO #')
                    ->searchable(),

                TextColumn::make('supplier_name')
                    ->label('Supplier')
                    ->searchable(),

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
            ->filters([
                SelectFilter::make('grn_status')
                    ->label('Status')
                    ->options([
                        'draft'     => 'Draft',
                        'verified'  => 'Verified',
                        'posted'    => 'Posted',
                        'cancelled' => 'Cancelled',
                    ]),

                SelectFilter::make('receiving_branch')
                    ->label('Unload Point')
                    ->options([
                        'Sirajganj'   => 'Sirajganj',
                        'Demra'       => 'Demra',
                        'Rampura'     => 'Rampura',
                        'Head Office' => 'Head Office',
                        'Other'       => 'Other',
                    ]),
            ])
            ->actions([
                EditAction::make()
                    ->visible(fn ($record) => $record->grn_status === 'draft'),
                ViewAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('grn_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListGoodsReceivedNotes::route('/'),
            'create' => Pages\CreateGoodsReceivedNote::route('/create'),
            'edit'   => Pages\EditGoodsReceivedNote::route('/{record}/edit'),
        ];
    }
}
