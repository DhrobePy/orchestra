<?php

namespace App\Filament\Resources\Sales\CustomerResource\RelationManagers;

use App\Models\CreditOrder;
use App\Models\CustomerLedger;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class LedgerEntriesRelationManager extends RelationManager
{
    protected static string $relationship = 'ledgerEntries';

    protected static ?string $title = 'Account Ledger Statement';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Account Ledger Statement')
            ->description(fn () => $this->buildStatementSummary())
            ->striped()
            ->columns([
                TextColumn::make('date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable()
                    ->width('110px'),

                TextColumn::make('description')
                    ->label('Description')
                    ->wrap()
                    ->searchable(),

                TextColumn::make('type_badge')
                    ->label('Type')
                    ->badge()
                    ->state(fn (CustomerLedger $record): string => $record->typeLabel())
                    ->color(fn (CustomerLedger $record): string => match (true) {
                        $record->isSale()    => 'danger',
                        $record->isPayment() => 'success',
                        default              => 'gray',
                    })
                    ->width('90px'),

                TextColumn::make('debit')
                    ->label('Debit (৳)')
                    ->formatStateUsing(fn ($state): string => (float) $state > 0
                        ? '৳ ' . number_format((float) $state, 2)
                        : '—'
                    )
                    ->alignRight()
                    ->color('danger')
                    ->weight(fn ($state) => (float) $state > 0 ? 'bold' : 'normal'),

                TextColumn::make('credit')
                    ->label('Credit (৳)')
                    ->formatStateUsing(fn ($state): string => (float) $state > 0
                        ? '৳ ' . number_format((float) $state, 2)
                        : '—'
                    )
                    ->alignRight()
                    ->color('success')
                    ->weight(fn ($state) => (float) $state > 0 ? 'bold' : 'normal'),

                TextColumn::make('balance')
                    ->label('Balance (৳)')
                    ->formatStateUsing(fn ($state): string => '৳ ' . number_format((float) $state, 2))
                    ->alignRight()
                    ->color(fn ($state): string => (float) $state > 0 ? 'danger' : 'success')
                    ->weight('bold'),
            ])
            ->filters([
                Filter::make('date_range')
                    ->label('Date Range')
                    ->form([
                        DatePicker::make('from')->label('From Date'),
                        DatePicker::make('to')->label('To Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('date', '>=', $data['from']))
                            ->when($data['to'],   fn ($q) => $q->whereDate('date', '<=', $data['to']));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) $indicators[] = 'From: ' . $data['from'];
                        if ($data['to'] ?? null)   $indicators[] = 'To: ' . $data['to'];
                        return $indicators;
                    }),
            ])
            ->actions([
                Action::make('view_details')
                    ->label('Details')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading(fn (CustomerLedger $record): string =>
                        $record->isSale() ? 'Credit Sale Invoice' : 'Payment Receipt'
                    )
                    ->modalWidth('4xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalContent(fn (CustomerLedger $record): View =>
                        $this->buildTransactionModal($record)
                    ),

                Action::make('edit_entry')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->visible(fn (): bool => Auth::user()?->hasAnyRole(['super_admin', 'filament_admin']) ?? false)
                    ->fillForm(fn (CustomerLedger $record): array => [
                        'date'        => $record->date?->toDateString(),
                        'description' => $record->description,
                        'debit'       => (float) $record->debit,
                        'credit'      => (float) $record->credit,
                    ])
                    ->form([
                        DatePicker::make('date')
                            ->label('Date')
                            ->required(),
                        Textarea::make('description')
                            ->label('Description')
                            ->required()
                            ->rows(2),
                        TextInput::make('debit')
                            ->label('Debit (৳)')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->prefix('৳')
                            ->helperText('Amount customer owes (sale / opening balance)'),
                        TextInput::make('credit')
                            ->label('Credit (৳)')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->prefix('৳')
                            ->helperText('Amount customer paid'),
                    ])
                    ->modalHeading('Edit Ledger Entry')
                    ->modalWidth('md')
                    ->action(function (CustomerLedger $record, array $data): void {
                        $record->update([
                            'date'        => $data['date'],
                            'description' => $data['description'],
                            'debit'       => $data['debit'],
                            'credit'      => $data['credit'],
                        ]);
                        $this->recalculateBalances($record->customer_id);
                    }),

                Action::make('delete_entry')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn (): bool => Auth::user()?->hasAnyRole(['super_admin', 'filament_admin']) ?? false)
                    ->requiresConfirmation()
                    ->modalHeading('Delete Ledger Entry')
                    ->modalDescription('This entry will be removed and all subsequent balances will be recalculated. This cannot be undone.')
                    ->modalSubmitActionLabel('Yes, Delete')
                    ->action(function (CustomerLedger $record): void {
                        $customerId = $record->customer_id;
                        $record->delete();
                        $this->recalculateBalances($customerId);
                    }),
            ])
            ->headerActions([
                Action::make('print_statement')
                    ->label('Print Statement')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn () => route('print.customer.statement', $this->getOwnerRecord()->id))
                    ->openUrlInNewTab(),

                Action::make('export_csv')
                    ->label('Export CSV')
                    ->icon('heroicon-o-table-cells')
                    ->color('success')
                    ->url(fn () => route('export.customer.ledger.csv', $this->getOwnerRecord()->id))
                    ->openUrlInNewTab(),
            ])
            ->defaultSort('date', 'asc')
            ->paginated([25, 50, 100, 'all']);
    }

    // ── Statement summary strip shown under the table heading ─────────────────

    private function buildStatementSummary(): HtmlString
    {
        $customer = $this->getOwnerRecord();
        $limit    = (float) ($customer->credit_limit ?? 0);
        $balance  = (float) ($customer->credit_balance ?? 0);
        $avail    = max(0, $limit - $balance);
        $pct      = $limit > 0 ? min(100, round(($balance / $limit) * 100)) : 0;
        $barColor = $pct >= 90 ? '#dc2626' : ($pct >= 70 ? '#f59e0b' : '#16a34a');

        return new HtmlString(
            '<div style="display:flex;flex-wrap:wrap;gap:12px;align-items:center;padding:6px 0;">'
            . self::statBubble('Credit Limit', '৳ ' . number_format($limit, 2), '#3b82f6')
            . self::statBubble('Amount Used', '৳ ' . number_format($balance, 2), $pct >= 90 ? '#dc2626' : '#f59e0b')
            . self::statBubble('Available', '৳ ' . number_format($avail, 2), '#16a34a')
            . '<div style="flex:1;min-width:180px;">'
            . '<div style="font-size:11px;color:#9ca3af;margin-bottom:4px;">Credit Utilisation: <strong style="color:#f3f4f6;">' . $pct . '%</strong></div>'
            . '<div style="background:rgba(255,255,255,0.1);border-radius:9999px;height:8px;overflow:hidden;">'
            . '<div style="width:' . $pct . '%;background:' . $barColor . ';height:100%;border-radius:9999px;transition:width .3s;"></div>'
            . '</div></div>'
            . '</div>'
        );
    }

    private static function statBubble(string $label, string $value, string $color): string
    {
        return '<div style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.12);border-radius:12px;padding:10px 16px;text-align:center;min-width:140px;">'
            . '<div style="font-size:10px;color:#9ca3af;text-transform:uppercase;letter-spacing:.06em;font-weight:600;">' . e($label) . '</div>'
            . '<div style="font-size:17px;font-weight:800;color:' . $color . ';margin-top:3px;">' . $value . '</div>'
            . '</div>';
    }

    // ── Transaction detail modal content ──────────────────────────────────────

    private function buildTransactionModal(CustomerLedger $record): View
    {
        if ($record->reference_type === 'credit_order' && $record->reference_id) {
            $order = CreditOrder::with([
                'customer', 'items.product', 'items.variant', 'assignedBranch', 'approvedBy',
            ])->find($record->reference_id);

            if ($order) {
                return $record->isSale()
                    ? view('filament.ledger.invoice-modal', ['order' => $order, 'entry' => $record])
                    : view('filament.ledger.payment-modal', ['order' => $order, 'entry' => $record]);
            }
        }

        if ($record->reference_type === 'customer_payment' && $record->reference_id) {
            $payment = \App\Models\CustomerPayment::with(['allocations.order', 'customer'])->find($record->reference_id);
            if ($payment) {
                return view('filament.ledger.customer-payment-modal', ['payment' => $payment, 'entry' => $record]);
            }
        }

        return view('filament.ledger.generic-modal', ['record' => $record]);
    }

    // ── Balance recalculation (called after edit or delete) ───────────────────

    private function recalculateBalances(int $customerId): void
    {
        $entries = CustomerLedger::where('customer_id', $customerId)
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $running = 0.0;
        foreach ($entries as $entry) {
            $running = max(0, $running + (float) $entry->debit - (float) $entry->credit);
            $entry->updateQuietly(['balance' => $running]);
        }

        // Keep customers.credit_balance in sync with the ledger
        DB::table('customers')
            ->where('id', $customerId)
            ->update(['credit_balance' => $running]);
    }
}
