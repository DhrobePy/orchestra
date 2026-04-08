<?php

namespace App\Filament\Resources\Sales\CustomerResource\Pages;

use App\Filament\Resources\Sales\CustomerResource;
use App\Models\BankAccount;
use App\Models\CreditOrder;
use App\Services\CustomerPaymentService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\HtmlString;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),

            // ── Collect Payment ───────────────────────────────────────────────
            Action::make('collect_payment')
                ->label('Collect Payment')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->modalHeading('Collect Payment')
                ->modalWidth('lg')
                ->modalDescription(fn () => 'Customer: ' . $this->getRecord()->name
                    . ' — Outstanding Balance: ৳ ' . number_format((float) $this->getRecord()->credit_balance, 2))
                ->form(function (): array {
                    // Build the "payment against" options for this customer
                    $customer = $this->getRecord();

                    $invoiceOptions = CreditOrder::where('customer_id', $customer->id)
                        ->whereIn('status', [
                            CreditOrder::STATUS_DELIVERED,
                            CreditOrder::STATUS_SHIPPED,
                            CreditOrder::STATUS_APPROVED,
                            CreditOrder::STATUS_IN_PRODUCTION,
                            CreditOrder::STATUS_READY_TO_SHIP,
                        ])
                        ->where('balance', '>', 0)
                        ->orderBy('created_at')
                        ->get()
                        ->mapWithKeys(fn ($order) => [
                            'order_' . $order->id => $order->order_number
                                . '  —  ৳ ' . number_format((float) $order->balance, 2) . ' due'
                                . '  (' . CreditOrder::statusLabel($order->status) . ')',
                        ])
                        ->toArray();

                    $allocationOptions = [
                        'auto'         => '⚡ Auto-allocate — oldest invoice first',
                        'unallocated'  => '📂 Previous / Opening Balance (no specific invoice)',
                    ];

                    if (!empty($invoiceOptions)) {
                        $allocationOptions['_divider'] = '── Select a specific invoice ──';
                        $allocationOptions = array_merge($allocationOptions, $invoiceOptions);
                    }

                    return [
                        Select::make('allocate_to')
                            ->label('Payment Against')
                            ->options($allocationOptions)
                            ->default('auto')
                            ->required()
                            ->live()
                            ->helperText(fn (Get $get): string => match(true) {
                                $get('allocate_to') === 'auto'        => 'Payment will be applied to the oldest unpaid invoices first.',
                                $get('allocate_to') === 'unallocated' => 'Payment is recorded but not linked to any specific invoice. Use this for previous or opening balances.',
                                $get('allocate_to') === '_divider'    => '',
                                default => 'Payment will be fully applied to this invoice, then any remainder to the next oldest.',
                            }),

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
                            ->label('Deposit to Bank Account')
                            ->options(BankAccount::activeOptions())
                            ->searchable()
                            ->nullable()
                            ->visible(fn (Get $get): bool => $get('payment_method') === 'bank_transfer')
                            ->helperText('Select the bank account this payment was deposited into.'),

                        TextInput::make('reference')
                            ->label('Reference / Cheque No.')
                            ->maxLength(100)
                            ->placeholder(fn (Get $get): string => match($get('payment_method')) {
                                'bank_transfer'  => 'Bank transaction reference',
                                'cheque'         => 'Cheque number',
                                'mobile_banking' => 'Transaction ID',
                                default          => 'Optional',
                            }),

                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(2),
                    ];
                })
                ->action(function (array $data): void {
                    $allocateTo = $data['allocate_to'] ?? 'auto';

                    // Resolve specific order id if one was selected
                    $orderId             = null;
                    $skipAutoAllocation  = false;

                    if ($allocateTo === 'unallocated') {
                        $skipAutoAllocation = true;
                    } elseif (str_starts_with($allocateTo, 'order_')) {
                        $orderId = (int) str_replace('order_', '', $allocateTo);
                    }
                    // 'auto' → both null / false → service auto-allocates oldest first

                    (new CustomerPaymentService())->collect(
                        customer:           $this->getRecord(),
                        amount:             (float) $data['amount'],
                        method:             $data['payment_method'] ?? 'cash',
                        reference:          $data['reference'] ?? null,
                        notes:              $data['notes'] ?? null,
                        paymentDate:        $data['payment_date'] ?? null,
                        bankAccountId:      isset($data['bank_account_id']) ? (int) $data['bank_account_id'] : null,
                        orderId:            $orderId,
                        skipAutoAllocation: $skipAutoAllocation,
                    );

                    Notification::make()
                        ->title('Payment of ৳ ' . number_format((float) $data['amount'], 2) . ' collected')
                        ->success()
                        ->send();
                }),

            // ── Record Previous / Opening Due ────────────────────────────────
            Action::make('record_previous_due')
                ->label('Add Previous Due')
                ->icon('heroicon-o-exclamation-circle')
                ->color('warning')
                ->modalHeading('Record Previous / Opening Due')
                ->modalWidth('md')
                ->modalDescription('Use this to record a balance owed by this customer that existed before Orchestra was set up. This increases their outstanding balance without creating a credit order.')
                ->form([
                    TextInput::make('amount')
                        ->label('Due Amount (৳)')
                        ->numeric()
                        ->minValue(0.01)
                        ->required()
                        ->prefix('৳'),

                    DatePicker::make('date')
                        ->label('As of Date')
                        ->default(now()->toDateString())
                        ->required(),

                    TextInput::make('description')
                        ->label('Description')
                        ->default('Opening / previous balance')
                        ->required()
                        ->maxLength(200),
                ])
                ->action(function (array $data): void {
                    (new CustomerPaymentService())->recordOpeningBalance(
                        customer:    $this->getRecord(),
                        amount:      (float) $data['amount'],
                        description: $data['description'],
                        date:        $data['date'],
                    );

                    Notification::make()
                        ->title('Previous due of ৳ ' . number_format((float) $data['amount'], 2) . ' recorded')
                        ->warning()
                        ->send();
                }),

            // ── Print / Export ───────────────────────────────────────────────
            Action::make('print_statement')
                ->label('Print Statement')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn () => route('print.customer.statement', $this->getRecord()->id))
                ->openUrlInNewTab(),

            Action::make('export_csv')
                ->label('Export CSV')
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->url(fn () => route('export.customer.ledger.csv', $this->getRecord()->id))
                ->openUrlInNewTab(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $data;
    }
}
