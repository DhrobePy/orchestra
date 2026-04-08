<?php

namespace App\Filament\Resources\Sales;

use App\Filament\Resources\Sales\CreditOrderResource\Pages;
use App\Models\CreditOrder;
use App\Models\CreditOrderItem;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class CreditOrderResource extends Resource
{
    protected static ?string $model = CreditOrder::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'order_number';

    // ── RBAC ──────────────────────────────────────────────────────────────────

    public static function canCreate(): bool
    {
        return Auth::user()->hasAnyRole([
            'Sales Executive', 'Sales Manager', 'super_admin', 'filament_admin',
        ]);
    }

    public static function canEdit(Model $record): bool
    {
        $user = Auth::user();
        if ($user->hasAnyRole(['super_admin', 'filament_admin'])) {
            return true;
        }
        return $record->status === CreditOrder::STATUS_DRAFT
            && $user->hasAnyRole(['Sales Executive', 'Sales Manager']);
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()->hasAnyRole(['super_admin', 'filament_admin'])
            && $record->status === CreditOrder::STATUS_DRAFT;
    }

    // ── Shared line-total helper (mirrors CreditOrderItem::calcSubtotal) ───────

    public static function computeLineTotal(Get $get): float
    {
        $qty          = (float) ($get('quantity') ?? 0);
        $price        = (float) ($get('unit_price') ?? 0);
        $discount     = (float) ($get('discount') ?? 0);
        $discountType = $get('discount_type') ?? 'flat';
        return CreditOrderItem::calcSubtotal($qty, $price, $discount, $discountType);
    }

    // ── Form ─────────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        // Roles that can see credit/financial details
        $canSeeCredit = fn () => Auth::user()->hasAnyRole([
            'Accountant', 'Sales Manager', 'super_admin', 'filament_admin',
        ]);

        return $schema->components([

            // ── Order Header ───────────────────────────────────────────────
            Section::make('Order Details')
                ->schema([
                    Grid::make(3)->schema([
                        TextInput::make('order_number')
                            ->label('Order #')
                            ->placeholder('Auto-generated')
                            ->helperText('Leave blank to auto-generate')
                            ->maxLength(50)
                            ->nullable()
                            ->dehydrated(true),

                        DatePicker::make('order_date')
                            ->label('Order Date')
                            ->required()
                            ->default(today()),

                        DatePicker::make('delivery_date')
                            ->label('Requested Delivery Date')
                            ->nullable()
                            ->minDate(today()),
                    ]),

                    Grid::make(2)->schema([
                        Select::make('customer_id')
                            ->label('Customer')
                            ->required()
                            ->live()
                            ->options(fn () => Customer::active()
                                ->orderBy('name')
                                ->limit(200)
                                ->pluck('name', 'id')
                                ->toArray()
                            )
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search) => Customer::active()
                                ->where(fn ($q) => $q
                                    ->where('name', 'like', "%{$search}%")
                                    ->orWhere('phone', 'like', "%{$search}%")
                                    ->orWhere('company_name', 'like', "%{$search}%")
                                )
                                ->limit(50)
                                ->pluck('name', 'id')
                                ->toArray()
                            )
                            ->getOptionLabelUsing(fn ($value) => Customer::find($value)?->name ?? $value),

                        // Customer info card — credit details role-gated
                        Placeholder::make('customer_info')
                            ->label('Customer Details')
                            ->content(function (Get $get) use ($canSeeCredit): HtmlString {
                                $cid = $get('customer_id');
                                if (!$cid) {
                                    return new HtmlString(
                                        '<span style="font-size:13px;color:#9ca3af;font-style:italic;">Select a customer to see details</span>'
                                    );
                                }
                                $c = Customer::find($cid);
                                if (!$c) return new HtmlString('');

                                $parts = [];
                                if ($c->phone)        $parts[] = '📱 ' . e($c->phone);
                                if ($c->company_name) $parts[] = '🏢 ' . e($c->company_name);
                                $terms = match ($c->payment_terms) {
                                    'cod'     => 'COD',
                                    'advance' => 'Advance',
                                    'credit'  => 'Credit',
                                    default   => $c->payment_terms ?? '—',
                                };
                                $parts[] = '💳 ' . $terms;

                                // Credit info only for authorised roles
                                if ($canSeeCredit()) {
                                    $limit     = (float) ($c->credit_limit ?? 0);
                                    $used      = (float) ($c->credit_balance ?? 0);
                                    $available = max(0, $limit - $used);
                                    if ($limit > 0) {
                                        $color = $available < ($limit * 0.2) ? '#dc2626' : '#16a34a';
                                        $parts[] = '🏦 Limit: ৳' . number_format($limit, 2);
                                        $parts[] = '<span style="color:' . $color . ';font-weight:600;">Available: ৳' . number_format($available, 2) . '</span>';
                                    }
                                }

                                return new HtmlString(
                                    '<div style="display:flex;flex-wrap:wrap;gap:6px 12px;font-size:13px;color:#374151;">'
                                    . implode(' <span style="color:#d1d5db;">·</span> ', $parts)
                                    . '</div>'
                                );
                            }),
                    ]),

                    // Credit warning (only shown to privileged roles when over limit)
                    Placeholder::make('credit_warning')
                        ->label('')
                        ->visible($canSeeCredit)
                        ->content(function (Get $get): HtmlString {
                            $cid = $get('customer_id');
                            if (!$cid) return new HtmlString('');
                            $c = Customer::find($cid);
                            if (!$c || !$c->credit_limit) return new HtmlString('');

                            $limit     = (float) $c->credit_limit;
                            $used      = (float) ($c->credit_balance ?? 0);
                            $available = max(0, $limit - $used);

                            if ($available > 0) return new HtmlString('');

                            return new HtmlString(
                                '<div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;padding:10px 14px;display:flex;align-items:center;gap:8px;">'
                                . '<span style="font-size:18px;">⚠️</span>'
                                . '<div>'
                                . '<strong style="color:#991b1b;font-size:13px;">Credit Limit Exceeded</strong>'
                                . '<p style="color:#b91c1c;font-size:12px;margin:2px 0 0;">This customer has no available credit (Used: ৳'
                                . number_format($used, 2) . ' of ৳' . number_format($limit, 2)
                                . '). The order will be <strong>escalated</strong> for admin approval on submission.</p>'
                                . '</div></div>'
                            );
                        })
                        ->columnSpanFull(),

                    Textarea::make('delivery_address')
                        ->label('Delivery Address')
                        ->placeholder('Full delivery address...')
                        ->rows(2)
                        ->columnSpanFull()
                        ->nullable(),
                ]),

            // ── Order Items ────────────────────────────────────────────────
            Section::make('Order Items')
                ->schema([
                    Repeater::make('items')
                        ->label('')
                        ->relationship('items')
                        ->schema([
                            Grid::make(12)->schema([
                                // Product (span 4)
                                Select::make('product_id')
                                    ->label('Product')
                                    ->required()
                                    ->live()
                                    ->options(fn () => Product::where('is_active', true)
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->toArray()
                                    )
                                    ->searchable()
                                    ->afterStateUpdated(function (Set $set) {
                                        $set('variant_id', null);
                                        $set('unit_price', null);
                                    })
                                    ->columnSpan(4),

                                // Variant (span 3)
                                Select::make('variant_id')
                                    ->label('Variant / Grade')
                                    ->live()
                                    ->options(function (Get $get) {
                                        $pid = $get('product_id');
                                        if (!$pid) return [];
                                        return ProductVariant::where('product_id', $pid)
                                            ->where('is_active', true)
                                            ->get()
                                            ->mapWithKeys(fn ($v) => [
                                                $v->id => trim(($v->name ?? '') . ' — ৳' . number_format((float) $v->price, 2)),
                                            ])
                                            ->toArray();
                                    })
                                    ->afterStateUpdated(function (?int $state, Set $set) {
                                        if ($state) {
                                            $v = ProductVariant::find($state);
                                            if ($v) $set('unit_price', $v->price);
                                        }
                                    })
                                    ->nullable()
                                    ->placeholder('Default')
                                    ->columnSpan(3),

                                // Quantity (span 2)
                                TextInput::make('quantity')
                                    ->label('Qty')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(0.01)
                                    ->step(0.01)
                                    ->live(onBlur: true)
                                    ->columnSpan(2),

                                // Unit Price (span 3)
                                TextInput::make('unit_price')
                                    ->label('Unit Price (৳)')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->live(onBlur: true)
                                    ->columnSpan(3),
                            ]),

                            // Discount row
                            Grid::make(12)->schema([
                                Select::make('discount_type')
                                    ->label('Discount Type')
                                    ->options([
                                        'flat'     => '৳ Flat (off line)',
                                        'per_unit' => '৳ Per Unit',
                                        'percent'  => '% Percentage',
                                    ])
                                    ->default('flat')
                                    ->live()
                                    ->columnSpan(4),

                                TextInput::make('discount')
                                    ->label(fn (Get $get): string => match ($get('discount_type')) {
                                        'per_unit' => 'Discount per Unit (৳)',
                                        'percent'  => 'Discount (%)',
                                        default    => 'Discount (৳ off line)',
                                    })
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->live(onBlur: true)
                                    ->suffix(fn (Get $get): string => $get('discount_type') === 'percent' ? '%' : '৳')
                                    ->columnSpan(4),

                                // Line Total
                                Placeholder::make('line_total')
                                    ->label('Line Total')
                                    ->content(function (Get $get): HtmlString {
                                        $total = static::computeLineTotal($get);
                                        return new HtmlString(
                                            '<div style="padding-top:6px;">'
                                            . '<span style="font-weight:700;font-size:15px;color:#16a34a;">৳ ' . number_format($total, 2) . '</span>'
                                            . '</div>'
                                        );
                                    })
                                    ->columnSpan(4),
                            ]),
                        ])
                        ->addActionLabel('+ Add Product')
                        ->reorderable(false)
                        ->collapsible()
                        ->itemLabel(function (array $state): ?string {
                            $pid = $state['product_id'] ?? null;
                            if (!$pid) return 'New item';
                            $name  = Product::find($pid)?->name ?? 'Product';
                            $qty   = (float) ($state['quantity'] ?? 0);
                            $price = (float) ($state['unit_price'] ?? 0);
                            $disc  = (float) ($state['discount'] ?? 0);
                            $dtype = $state['discount_type'] ?? 'flat';
                            $total = CreditOrderItem::calcSubtotal($qty, $price, $disc, $dtype);
                            return $name . ' × ' . $qty . ' = ৳' . number_format($total, 2);
                        })
                        ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                            $data['subtotal'] = CreditOrderItem::calcSubtotal(
                                (float) ($data['quantity'] ?? 0),
                                (float) ($data['unit_price'] ?? 0),
                                (float) ($data['discount'] ?? 0),
                                $data['discount_type'] ?? 'flat',
                            );
                            return $data;
                        })
                        ->mutateRelationshipDataBeforeSaveUsing(function (array $data): array {
                            $data['subtotal'] = CreditOrderItem::calcSubtotal(
                                (float) ($data['quantity'] ?? 0),
                                (float) ($data['unit_price'] ?? 0),
                                (float) ($data['discount'] ?? 0),
                                $data['discount_type'] ?? 'flat',
                            );
                            return $data;
                        }),
                ]),

            // ── Order-level Totals ─────────────────────────────────────────
            Section::make('Order Totals')
                ->columns(3)
                ->schema([
                    TextInput::make('discount')
                        ->label('Order-level Discount (৳)')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->step(0.01)
                        ->prefix('৳')
                        ->helperText('Additional ৳ discount on the whole order'),

                    TextInput::make('tax')
                        ->label('Tax / VAT (৳)')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->step(0.01)
                        ->prefix('৳'),

                    TextInput::make('paid_amount')
                        ->label('Advance Paid (৳)')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->step(0.01)
                        ->prefix('৳'),
                ]),

            // ── Notes ──────────────────────────────────────────────────────
            Section::make('Notes')
                ->collapsed()
                ->collapsible()
                ->schema([
                    Textarea::make('notes')
                        ->label('Special Instructions / Notes')
                        ->placeholder('Delivery instructions, special requests, remarks...')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),

            // ── Workflow Info (view / edit only) ───────────────────────────
            Section::make('Approval & Workflow Details')
                ->visible(fn (?CreditOrder $record) => $record !== null)
                ->collapsible()
                ->schema([
                    Grid::make(3)->schema([
                        Placeholder::make('_status')
                            ->label('Status')
                            ->content(fn (?CreditOrder $record): HtmlString => $record
                                ? new HtmlString(self::statusPill($record->status))
                                : new HtmlString('')),

                        Placeholder::make('_priority')
                            ->label('Priority')
                            ->content(fn (?CreditOrder $record): HtmlString => $record
                                ? new HtmlString(
                                    '<span style="font-size:13px;font-weight:600;">'
                                    . CreditOrder::priorityLabel($record->priority ?? 2) . '</span>'
                                )
                                : new HtmlString('')),

                        Placeholder::make('_payment')
                            ->label('Payment')
                            ->content(fn (?CreditOrder $record): HtmlString => $record
                                ? new HtmlString(self::paymentPill($record->payment_status))
                                : new HtmlString('')),
                    ]),

                    Grid::make(2)->schema([
                        Placeholder::make('_approved')
                            ->label('Approved By')
                            ->content(fn (?CreditOrder $record): HtmlString => $record && $record->approved_by
                                ? new HtmlString(
                                    '<span style="font-size:13px;">'
                                    . e($record->approvedBy?->name ?? 'Unknown')
                                    . ' &mdash; ' . ($record->approved_at?->format('d M Y H:i') ?? '')
                                    . '</span>'
                                )
                                : new HtmlString('<span style="font-size:13px;color:#9ca3af;">Not yet approved</span>')
                            ),

                        Placeholder::make('_branch')
                            ->label('Assigned Branch')
                            ->content(fn (?CreditOrder $record): HtmlString => $record && $record->assigned_branch_id
                                ? new HtmlString('<span style="font-size:13px;font-weight:600;">' . e($record->assignedBranch?->name ?? '—') . '</span>')
                                : new HtmlString('<span style="font-size:13px;color:#9ca3af;">Not assigned</span>')
                            ),
                    ]),

                    Placeholder::make('_financials')
                        ->label('Financial Summary')
                        ->content(fn (?CreditOrder $record): HtmlString => $record
                            ? new HtmlString(
                                '<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;">'
                                . self::financialCard('Items Subtotal', '৳ ' . number_format((float) $record->subtotal, 2), '#374151')
                                . self::financialCard('Order Discount', '- ৳ ' . number_format((float) $record->discount, 2), '#dc2626')
                                . self::financialCard('Tax / VAT', '+ ৳ ' . number_format((float) $record->tax, 2), '#374151')
                                . self::financialCard('Order Total', '৳ ' . number_format((float) $record->total, 2), '#0f766e', true)
                                . self::financialCard('Paid', '৳ ' . number_format((float) $record->paid_amount, 2), '#16a34a')
                                . self::financialCard('Balance Due', '৳ ' . number_format((float) $record->balance, 2), (float) $record->balance > 0 ? '#dc2626' : '#16a34a', (float) $record->balance > 0)
                                . '</div>'
                            )
                            : new HtmlString('')
                        )
                        ->columnSpanFull(),
                ]),

            // ── Status Timeline ─────────────────────────────────────────────
            Section::make('Status History')
                ->visible(fn (?CreditOrder $record) => $record !== null)
                ->collapsible()
                ->schema([
                    Placeholder::make('_timeline')
                        ->hiddenLabel()
                        ->content(fn (?CreditOrder $record): HtmlString => $record
                            ? self::renderTimeline($record)
                            : new HtmlString('')
                        )
                        ->columnSpanFull(),
                ]),
        ]);
    }

    // ── UI Helpers ────────────────────────────────────────────────────────────

    private static function statusPill(string $status): string
    {
        $colors = [
            CreditOrder::STATUS_DRAFT                  => ['bg' => '#f3f4f6', 'fg' => '#374151'],
            CreditOrder::STATUS_PENDING_APPROVAL       => ['bg' => '#fef3c7', 'fg' => '#92400e'],
            CreditOrder::STATUS_ESCALATED              => ['bg' => '#fee2e2', 'fg' => '#991b1b'],
            CreditOrder::STATUS_APPROVED               => ['bg' => '#dbeafe', 'fg' => '#1e40af'],
            CreditOrder::STATUS_IN_PRODUCTION          => ['bg' => '#ede9fe', 'fg' => '#5b21b6'],
            CreditOrder::STATUS_READY_TO_SHIP          => ['bg' => '#d1fae5', 'fg' => '#065f46'],
            CreditOrder::STATUS_SHIPPED                => ['bg' => '#d1fae5', 'fg' => '#065f46'],
            CreditOrder::STATUS_DELIVERED              => ['bg' => '#bbf7d0', 'fg' => '#14532d'],
            CreditOrder::STATUS_CANCELLED              => ['bg' => '#fee2e2', 'fg' => '#7f1d1d'],
            CreditOrder::STATUS_CANCELLATION_REQUESTED => ['bg' => '#ffedd5', 'fg' => '#9a3412'],
        ];
        $c = $colors[$status] ?? ['bg' => '#f3f4f6', 'fg' => '#374151'];
        return '<span style="display:inline-block;background:' . $c['bg'] . ';color:' . $c['fg']
            . ';padding:3px 12px;border-radius:999px;font-size:12px;font-weight:600;">'
            . CreditOrder::statusLabel($status) . '</span>';
    }

    private static function paymentPill(string $status): string
    {
        $colors = [
            'paid'           => ['bg' => '#d1fae5', 'fg' => '#065f46', 'label' => '✓ Paid'],
            'partially_paid' => ['bg' => '#fef3c7', 'fg' => '#92400e', 'label' => '⚡ Partial'],
            'unpaid'         => ['bg' => '#f3f4f6', 'fg' => '#374151', 'label' => '— Unpaid'],
        ];
        $c = $colors[$status] ?? $colors['unpaid'];
        return '<span style="display:inline-block;background:' . $c['bg'] . ';color:' . $c['fg']
            . ';padding:3px 12px;border-radius:999px;font-size:12px;font-weight:600;">'
            . $c['label'] . '</span>';
    }

    private static function financialCard(string $label, string $value, string $color, bool $bold = false): string
    {
        return '<div style="background:#f9fafb;border-radius:8px;padding:10px 12px;border:1px solid #e5e7eb;">'
            . '<div style="font-size:11px;color:#6b7280;margin-bottom:3px;">' . e($label) . '</div>'
            . '<div style="font-size:15px;color:' . $color . ';' . ($bold ? 'font-weight:700;' : '') . '">' . $value . '</div>'
            . '</div>';
    }

    public static function renderTimeline(CreditOrder $record): HtmlString
    {
        $history = $record->statusHistory()->with('changedBy')->orderBy('created_at')->get();

        if ($history->isEmpty()) {
            return new HtmlString('<p style="font-size:13px;color:#9ca3af;font-style:italic;">No history recorded yet.</p>');
        }

        $dotColors = [
            CreditOrder::STATUS_DRAFT                  => '#9ca3af',
            CreditOrder::STATUS_PENDING_APPROVAL       => '#f59e0b',
            CreditOrder::STATUS_ESCALATED              => '#ef4444',
            CreditOrder::STATUS_APPROVED               => '#3b82f6',
            CreditOrder::STATUS_IN_PRODUCTION          => '#8b5cf6',
            CreditOrder::STATUS_READY_TO_SHIP          => '#10b981',
            CreditOrder::STATUS_SHIPPED                => '#059669',
            CreditOrder::STATUS_DELIVERED              => '#047857',
            CreditOrder::STATUS_CANCELLED              => '#b91c1c',
            CreditOrder::STATUS_CANCELLATION_REQUESTED => '#f97316',
        ];

        $items = $history->values();
        $count = $items->count();
        $html  = '<div style="position:relative;padding-left:28px;">';
        // Vertical line
        $html .= '<div style="position:absolute;left:9px;top:10px;bottom:10px;width:2px;background:#e5e7eb;"></div>';

        foreach ($items as $i => $entry) {
            $dotColor = $dotColors[$entry->to_status] ?? '#9ca3af';
            $fromLbl  = $entry->from_status ? CreditOrder::statusLabel($entry->from_status) : 'Created';
            $toLbl    = CreditOrder::statusLabel($entry->to_status);
            $by       = $entry->changedBy?->name ?? 'System';
            $when     = $entry->created_at?->format('d M Y, H:i') ?? '';
            $mb       = ($i < $count - 1) ? '20px' : '0';

            $html .= '<div style="position:relative;margin-bottom:' . $mb . ';display:flex;gap:12px;align-items:flex-start;">';
            // Dot
            $html .= '<div style="position:absolute;left:-24px;top:3px;width:16px;height:16px;border-radius:50%;background:'
                . $dotColor . ';border:2px solid white;box-shadow:0 0 0 2px ' . $dotColor . ';flex-shrink:0;"></div>';
            // Content
            $html .= '<div style="flex:1;min-width:0;">';
            $html .= '<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">';
            $html .= '<div>';
            $html .= '<p style="font-size:13px;font-weight:600;color:#111827;margin:0;">'
                . e($fromLbl) . ' <span style="color:#9ca3af;">→</span> '
                . '<span style="color:' . $dotColor . ';">' . e($toLbl) . '</span></p>';
            $html .= '<p style="font-size:12px;color:#6b7280;margin:2px 0 0;">By: ' . e($by) . '</p>';
            if ($entry->notes) {
                $html .= '<p style="font-size:12px;color:#6b7280;font-style:italic;margin:3px 0 0;padding:4px 8px;background:#f9fafb;border-radius:4px;border-left:3px solid ' . $dotColor . ';">'
                    . e($entry->notes) . '</p>';
            }
            $html .= '</div>';
            $html .= '<span style="font-size:11px;color:#9ca3af;white-space:nowrap;flex-shrink:0;">' . e($when) . '</span>';
            $html .= '</div></div></div>';
        }

        $html .= '</div>';
        return new HtmlString($html);
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->label('Order #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('order_date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => CreditOrder::statusLabel($state))
                    ->color(fn (string $state): string => CreditOrder::statusColor($state)),

                TextColumn::make('priority')
                    ->label('Priority')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => CreditOrder::priorityLabel((int) $state))
                    ->color(fn ($state): string => match ((int) $state) {
                        1 => 'danger',
                        2 => 'warning',
                        3 => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid'           => 'success',
                        'partially_paid' => 'warning',
                        default          => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'paid'           => '✓ Paid',
                        'partially_paid' => '⚡ Partial',
                        default          => '— Unpaid',
                    }),

                TextColumn::make('total')
                    ->label('Total')
                    ->formatStateUsing(fn ($state) => '৳ ' . number_format((float) $state, 2))
                    ->alignRight()
                    ->sortable(),

                TextColumn::make('balance')
                    ->label('Balance')
                    ->formatStateUsing(fn ($state) => '৳ ' . number_format((float) $state, 2))
                    ->alignRight()
                    ->color(fn ($state) => (float) $state > 0 ? 'danger' : 'success'),

                TextColumn::make('delivery_date')
                    ->label('Delivery')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('assignedBranch.name')
                    ->label('Branch')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options(CreditOrder::allStatuses()),
                SelectFilter::make('payment_status')
                    ->label('Payment')
                    ->options(['unpaid' => 'Unpaid', 'partially_paid' => 'Partially Paid', 'paid' => 'Paid']),
                SelectFilter::make('priority')
                    ->options([1 => '🔴 Urgent', 2 => '🟡 Normal', 3 => '🟢 Low']),
                SelectFilter::make('customer_id')
                    ->label('Customer')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make()->visible(fn (CreditOrder $record) => static::canEdit($record)),
                Action::make('print_invoice')
                    ->label('Print')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn (CreditOrder $record) => route('print.credit-order', $record->id))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => Auth::user()->hasAnyRole(['super_admin', 'filament_admin'])),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    // ── Pages ─────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCreditOrders::route('/'),
            'create' => Pages\CreateCreditOrder::route('/create'),
            'view'   => Pages\ViewCreditOrder::route('/{record}'),
            'edit'   => Pages\EditCreditOrder::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $user = Auth::user();

        $count = match (true) {
            $user->hasAnyRole(['super_admin', 'filament_admin']) =>
                CreditOrder::whereIn('status', [
                    CreditOrder::STATUS_ESCALATED,
                    CreditOrder::STATUS_CANCELLATION_REQUESTED,
                ])->count(),
            $user->hasRole('Accountant') =>
                CreditOrder::where('status', CreditOrder::STATUS_PENDING_APPROVAL)->count(),
            $user->hasRole('Production Manager') =>
                CreditOrder::where('status', CreditOrder::STATUS_APPROVED)->count(),
            $user->hasAnyRole(['Logistics Manager', 'Dispatcher']) =>
                CreditOrder::where('status', CreditOrder::STATUS_READY_TO_SHIP)->count(),
            default =>
                CreditOrder::whereIn('status', [
                    CreditOrder::STATUS_DRAFT,
                    CreditOrder::STATUS_PENDING_APPROVAL,
                    CreditOrder::STATUS_ESCALATED,
                ])->count(),
        };

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $user = Auth::user();
        if ($user->hasAnyRole(['super_admin', 'filament_admin'])) {
            return CreditOrder::where('status', CreditOrder::STATUS_ESCALATED)->exists()
                ? 'danger' : 'warning';
        }
        return 'warning';
    }
}
