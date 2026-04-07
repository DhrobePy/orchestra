<?php

namespace App\Filament\Resources\Purchasing;

use App\Filament\Resources\Purchasing\PurchaseOrderResource\Pages;
use App\Filament\Resources\Purchasing\PurchaseOrderResource\RelationManagers;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;


    protected static string|\UnitEnum|null $navigationGroup = 'Purchasing';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'po_number';

    public static function form(Schema $schema): Schema
    {
        $origins = [
            'কানাডা'    => 'কানাডা (Canada)',
            'রাশিয়া'   => 'রাশিয়া (Russia)',
            'Australia' => 'Australia',
            'Ukraine'   => 'Ukraine',
            'India'     => 'India',
            'USA'       => 'USA',
            'Argentina' => 'Argentina',
            'Brazil'    => 'Brazil',
            'Local'     => 'Local',
            'Other'     => 'Other',
        ];

        $uomOptions = collect(config('procurement.uom', ['KG', 'MT', 'TON']))
            ->mapWithKeys(fn ($uom) => [$uom => $uom])
            ->toArray();

        return $schema->components([

            // ── Basic Information ─────────────────────────────────────────
            Section::make('Basic Information')
                ->schema([
                    Grid::make(3)->schema([
                        TextInput::make('po_number')
                            ->label('PO Number (Optional)')
                            ->placeholder('Auto-generated if left blank')
                            ->helperText('Leave blank to auto-generate')
                            ->maxLength(50)
                            ->nullable()
                            ->dehydrated(true),

                        DatePicker::make('po_date')
                            ->label('PO Date')
                            ->required()
                            ->default(today()),

                        DatePicker::make('expected_delivery_date')
                            ->label('Expected Delivery Date')
                            ->nullable()
                            ->minDate(today()),
                    ]),

                    Grid::make(2)->schema([
                        Select::make('supplier_id')
                            ->label('Supplier')
                            ->required()
                            ->relationship('supplier', 'company_name')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->getSearchResultsUsing(function (string $search) {
                                return Supplier::active()
                                    ->where(function ($q) use ($search) {
                                        $q->where('company_name', 'like', "%{$search}%")
                                          ->orWhere('supplier_code', 'like', "%{$search}%");
                                    })
                                    ->limit(50)
                                    ->pluck('company_name', 'id')
                                    ->toArray();
                            })
                            ->getOptionLabelUsing(fn ($value) => Supplier::find($value)?->company_name ?? $value),

                        Placeholder::make('supplier_info')
                            ->label('Supplier Contact')
                            ->content(function (Get $get): HtmlString {
                                $supplierId = $get('supplier_id');
                                if (!$supplierId) {
                                    return new HtmlString('<span class="text-sm text-gray-400 italic">Select a supplier</span>');
                                }
                                $supplier = Supplier::find($supplierId);
                                if (!$supplier) {
                                    return new HtmlString('');
                                }
                                $parts = [];
                                if ($supplier->supplier_code) {
                                    $parts[] = '<span class="font-mono text-xs bg-gray-100 px-1 rounded">' . e($supplier->supplier_code) . '</span>';
                                }
                                if ($supplier->contact_person) {
                                    $parts[] = e($supplier->contact_person);
                                }
                                if ($supplier->phone) {
                                    $parts[] = e($supplier->phone);
                                }
                                return new HtmlString(
                                    '<div class="flex flex-wrap gap-x-4 gap-y-1 text-sm text-gray-700">'
                                    . implode(' &nbsp;·&nbsp; ', $parts)
                                    . '</div>'
                                );
                            }),
                    ]),
                ]),

            // ── Wheat Details ─────────────────────────────────────────────
            Section::make('Wheat Details')
                ->schema([
                    Grid::make(3)->schema([
                        TextInput::make('commodity_description')
                            ->label('Commodity Description')
                            ->default('Wheat')
                            ->maxLength(255),

                        Select::make('origin')
                            ->label('Wheat Origin')
                            ->options($origins)
                            ->searchable()
                            ->required(),

                        Select::make('unit_of_measure')
                            ->label('Unit of Measure')
                            ->options($uomOptions)
                            ->default('KG')
                            ->required(),
                    ]),

                    Grid::make(2)->schema([
                        TextInput::make('quantity')
                            ->label('Quantity')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->step(0.01)
                            ->live(onBlur: true),

                        TextInput::make('unit_price')
                            ->label('Unit Price (৳)')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->step(0.0001)
                            ->live(onBlur: true),
                    ]),

                    Placeholder::make('total_display')
                        ->label('Total Order Value')
                        ->content(function (Get $get): HtmlString {
                            $qty   = (float) ($get('quantity') ?? 0);
                            $price = (float) ($get('unit_price') ?? 0);
                            $total = $qty * $price;
                            $uom   = $get('unit_of_measure') ?? 'KG';

                            if ($qty <= 0 || $price <= 0) {
                                return new HtmlString('<span class="text-gray-400 italic text-sm">Enter quantity and unit price to see total</span>');
                            }

                            $formatted = '৳ ' . number_format($total, 2);
                            $millions  = $total >= 1_000_000
                                ? ' <span class="text-base text-gray-500">(৳ ' . number_format($total / 1_000_000, 2) . 'M)</span>'
                                : '';
                            $formula = '<div class="text-xs text-gray-500 mt-1">'
                                . number_format($qty, 2) . ' ' . e($uom)
                                . ' × ৳' . number_format($price, 4)
                                . '</div>';

                            return new HtmlString(
                                '<div class="text-2xl font-bold text-primary-600">'
                                . $formatted . $millions
                                . '</div>'
                                . $formula
                            );
                        }),
                ]),

            // ── Payment Configuration ─────────────────────────────────────
            Section::make('Payment Configuration')
                ->schema([
                    Grid::make(3)->schema([
                        Select::make('payment_basis')
                            ->label('Payment Basis')
                            ->options([
                                'received_qty' => 'Pay on Received Qty',
                                'expected_qty' => 'Pay on Expected Qty',
                            ])
                            ->default('received_qty')
                            ->required()
                            ->helperText('Determines payment basis for GRNs'),

                        Select::make('payment_terms')
                            ->label('Payment Terms')
                            ->options([
                                'cod'             => 'Cash on Delivery',
                                'credit'          => 'Credit',
                                'advance'         => 'Full Advance',
                                'partial_advance' => 'Partial Advance',
                            ])
                            ->default('cod')
                            ->required()
                            ->live(),

                        Select::make('currency')
                            ->label('Currency')
                            ->options(['BDT' => 'BDT (৳)', 'USD' => 'USD ($)'])
                            ->default('BDT')
                            ->required(),
                    ]),

                    Grid::make(2)->schema([
                        TextInput::make('credit_days')
                            ->label('Credit Days')
                            ->numeric()
                            ->minValue(1)
                            ->nullable()
                            ->visible(fn (Get $get) => $get('payment_terms') === 'credit'),

                        TextInput::make('advance_percentage')
                            ->label('Advance Percentage')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->suffix('%')
                            ->nullable()
                            ->visible(fn (Get $get) => in_array($get('payment_terms'), ['advance', 'partial_advance'])),
                    ]),
                ]),

            // ── Additional Information ────────────────────────────────────
            Section::make('Additional Information')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Select::make('po_status')
                        ->label('PO Status')
                        ->options([
                            'draft'     => 'Draft',
                            'submitted' => 'Submitted',
                        ])
                        ->default('draft')
                        ->visible(fn () => (bool) config('procurement.features.po_approval', false)),

                    Textarea::make('terms_conditions')
                        ->label('Terms & Conditions')
                        ->rows(3),

                    Textarea::make('internal_notes')
                        ->label('Remarks / Internal Notes')
                        ->placeholder('Enter any additional notes...')
                        ->rows(4),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('po_number')
                    ->label('PO #')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('po_date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('supplier_name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('commodity_description')
                    ->label('Commodity')
                    ->limit(25),

                TextColumn::make('origin')
                    ->label('Origin'),

                TextColumn::make('quantity')
                    ->label('Qty')
                    ->formatStateUsing(fn ($state, $record) => number_format((float) $state, 2) . ' ' . ($record->unit_of_measure ?? 'KG'))
                    ->alignRight(),

                TextColumn::make('unit_price')
                    ->label('Unit Price')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 4))
                    ->alignRight(),

                TextColumn::make('total_order_value')
                    ->label('Order Value')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2))
                    ->alignRight()
                    ->sortable(),

                TextColumn::make('total_received_qty')
                    ->label('Received')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2))
                    ->alignRight(),

                TextColumn::make('balance_payable')
                    ->label('Balance')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2))
                    ->alignRight()
                    ->color(fn ($state) => (float) $state > 0 ? 'danger' : 'success'),

                TextColumn::make('po_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft'     => 'gray',
                        'submitted' => 'warning',
                        'approved'  => 'info',
                        'partial'   => 'primary',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default     => 'gray',
                    }),

                TextColumn::make('delivery_status')
                    ->label('Delivery')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending'       => 'gray',
                        'partial'       => 'warning',
                        'completed'     => 'success',
                        'over_received' => 'danger',
                        'closed'        => 'gray',
                        default         => 'gray',
                    }),

                TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'unpaid'   => 'danger',
                        'partial'  => 'warning',
                        'paid'     => 'success',
                        'overpaid' => 'primary',
                        default    => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('po_status')
                    ->label('PO Status')
                    ->options([
                        'draft'     => 'Draft',
                        'submitted' => 'Submitted',
                        'approved'  => 'Approved',
                        'partial'   => 'Partial',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),

                SelectFilter::make('delivery_status')
                    ->label('Delivery')
                    ->options([
                        'pending'       => 'Pending',
                        'partial'       => 'Partial',
                        'completed'     => 'Completed',
                        'over_received' => 'Over Received',
                        'closed'        => 'Closed',
                    ]),

                SelectFilter::make('payment_status')
                    ->label('Payment')
                    ->options([
                        'unpaid'   => 'Unpaid',
                        'partial'  => 'Partial',
                        'paid'     => 'Paid',
                        'overpaid' => 'Overpaid',
                    ]),

                SelectFilter::make('supplier_id')
                    ->label('Supplier')
                    ->relationship('supplier', 'company_name')
                    ->searchable()
                    ->preload(),

                Filter::make('po_date')
                    ->label('Date Range')
                    ->form([
                        DatePicker::make('from')->label('From'),
                        DatePicker::make('to')->label('To'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('po_date', '>=', $data['from']))
                            ->when($data['to'], fn ($q) => $q->whereDate('po_date', '<=', $data['to']));
                    }),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\GoodsReceivedNotesRelationManager::class,
            RelationManagers\PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'view'   => Pages\ViewPurchaseOrder::route('/{record}'),
            'edit'   => Pages\EditPurchaseOrder::route('/{record}/edit'),
        ];
    }
}
