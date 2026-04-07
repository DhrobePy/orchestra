<?php

namespace App\Filament\Resources\Sales\CreditOrderResource\Pages;

use App\Filament\Resources\Sales\CreditOrderResource;
use App\Models\Branch;
use App\Models\CreditOrder;
use App\Services\CreditOrderWorkflowService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class ViewCreditOrder extends ViewRecord
{
    protected static string $resource = CreditOrderResource::class;

    // ── Order summary HTML for approval modals ────────────────────────────────

    private function orderSummaryHtml(): HtmlString
    {
        $o       = $this->getRecord()->load(['customer', 'items.product', 'items.variant']);
        $items   = $o->items;

        $rows = '';
        foreach ($items as $item) {
            $pName  = $item->product?->name ?? '—';
            $vName  = $item->variant?->name  ?? '';
            $rows  .= '<tr style="border-bottom:1px solid #f3f4f6;">'
                . '<td style="padding:5px 8px;font-size:12px;">' . e($pName . ($vName ? " – $vName" : '')) . '</td>'
                . '<td style="padding:5px 8px;font-size:12px;text-align:right;">' . number_format((float)$item->quantity, 2) . '</td>'
                . '<td style="padding:5px 8px;font-size:12px;text-align:right;">৳ ' . number_format((float)$item->unit_price, 2) . '</td>'
                . '<td style="padding:5px 8px;font-size:12px;text-align:right;font-weight:600;color:#065f46;">৳ ' . number_format((float)$item->subtotal, 2) . '</td>'
                . '</tr>';
        }

        $creditLimit    = (float)($o->customer?->credit_limit ?? 0);
        $creditBalance  = (float)($o->customer?->credit_balance ?? 0);
        $available      = max(0, $creditLimit - $creditBalance);
        $overLimit      = $o->total > $available && $creditLimit > 0;
        $creditWarning  = $overLimit
            ? '<div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:6px;padding:8px 10px;margin-top:8px;font-size:12px;color:#991b1b;">⚠️ <strong>Order total exceeds available credit.</strong> Available: ৳' . number_format($available, 2) . '</div>'
            : '';

        return new HtmlString(
            '<div style="font-size:13px;">'
            . '<div style="display:flex;justify-content:space-between;margin-bottom:8px;">'
            . '<div><strong>Customer:</strong> ' . e($o->customer?->name ?? '—') . '</div>'
            . '<div><strong>Order #:</strong> ' . e($o->order_number) . '</div>'
            . '</div>'
            . '<table style="width:100%;border-collapse:collapse;background:#f9fafb;border-radius:6px;overflow:hidden;">'
            . '<thead><tr style="background:#e5e7eb;">'
            . '<th style="padding:6px 8px;text-align:left;font-size:11px;font-weight:600;color:#374151;">Product</th>'
            . '<th style="padding:6px 8px;text-align:right;font-size:11px;font-weight:600;color:#374151;">Qty</th>'
            . '<th style="padding:6px 8px;text-align:right;font-size:11px;font-weight:600;color:#374151;">Unit Price</th>'
            . '<th style="padding:6px 8px;text-align:right;font-size:11px;font-weight:600;color:#374151;">Subtotal</th>'
            . '</tr></thead>'
            . '<tbody>' . $rows . '</tbody>'
            . '</table>'
            . '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-top:10px;">'
            . '<div style="background:#f3f4f6;border-radius:6px;padding:8px;text-align:center;">'
            . '<div style="font-size:10px;color:#6b7280;">Subtotal</div>'
            . '<div style="font-weight:700;font-size:14px;">৳ ' . number_format((float)$o->subtotal, 2) . '</div>'
            . '</div>'
            . '<div style="background:#f3f4f6;border-radius:6px;padding:8px;text-align:center;">'
            . '<div style="font-size:10px;color:#6b7280;">Discount / Tax</div>'
            . '<div style="font-size:12px;">-৳ ' . number_format((float)$o->discount, 2) . ' / +৳ ' . number_format((float)$o->tax, 2) . '</div>'
            . '</div>'
            . '<div style="background:#0f766e;border-radius:6px;padding:8px;text-align:center;">'
            . '<div style="font-size:10px;color:#ccfbf1;">Order Total</div>'
            . '<div style="font-weight:700;font-size:16px;color:white;">৳ ' . number_format((float)$o->total, 2) . '</div>'
            . '</div>'
            . '</div>'
            . $creditWarning
            . '</div>'
        );
    }

    // ── Header Actions ────────────────────────────────────────────────────────

    protected function getHeaderActions(): array
    {
        return [

            // ── Edit (draft only) ─────────────────────────────────────────
            EditAction::make()
                ->visible(fn () =>
                    $this->getRecord()->status === CreditOrder::STATUS_DRAFT
                    && CreditOrderResource::canEdit($this->getRecord())
                ),

            // ── Submit for Approval ────────────────────────────────────────
            Action::make('submit')
                ->label('Submit for Approval')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->visible(fn () =>
                    $this->getRecord()->status === CreditOrder::STATUS_DRAFT
                    && Auth::user()->hasAnyRole([
                        'Sales Executive', 'Sales Manager', 'super_admin', 'filament_admin',
                    ])
                )
                ->requiresConfirmation()
                ->modalHeading('Submit Order for Approval')
                ->modalDescription(fn () =>
                    'Submit order ' . $this->getRecord()->order_number
                    . '? Credit limit will be checked automatically — insufficient credit escalates to admin.'
                )
                ->action(function () {
                    try {
                        app(CreditOrderWorkflowService::class)->submit($this->getRecord());
                        $status = $this->getRecord()->fresh()->status;
                        $msg    = $status === CreditOrder::STATUS_ESCALATED
                            ? 'Order escalated to admin — insufficient credit limit.'
                            : 'Order submitted for accountant approval.';
                        Notification::make()->title($msg)->warning()->send();
                        $this->redirect($this->getResource()::getUrl('view', ['record' => $this->getRecord()]));
                    } catch (\Throwable $e) {
                        Notification::make()->title('Error: ' . $e->getMessage())->danger()->send();
                    }
                }),

            // ── Approve (Accountant — pending_approval) ────────────────────
            Action::make('approve')
                ->label('Approve Order')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () =>
                    $this->getRecord()->status === CreditOrder::STATUS_PENDING_APPROVAL
                    && Auth::user()->hasAnyRole(['Accountant', 'super_admin', 'filament_admin'])
                )
                ->modalHeading('Approve Credit Order')
                ->modalWidth('2xl')
                ->form([
                    Placeholder::make('_summary')
                        ->hiddenLabel()
                        ->content(fn (): HtmlString => $this->orderSummaryHtml())
                        ->columnSpanFull(),

                    Textarea::make('notes')
                        ->label('Approval Notes')
                        ->placeholder('Optional remarks for this approval...')
                        ->rows(2),
                ])
                ->action(function (array $data) {
                    try {
                        app(CreditOrderWorkflowService::class)->approve(
                            order: $this->getRecord(),
                            notes: $data['notes'] ?? null,
                        );
                        Notification::make()->title('Order approved successfully.')->success()->send();
                        $this->redirect($this->getResource()::getUrl('view', ['record' => $this->getRecord()]));
                    } catch (\Throwable $e) {
                        Notification::make()->title('Error: ' . $e->getMessage())->danger()->send();
                    }
                }),

            // ── Approve + Assign Branch (Admin — escalated) ────────────────
            Action::make('approve_escalated')
                ->label('Review & Approve')
                ->icon('heroicon-o-building-office')
                ->color('success')
                ->visible(fn () =>
                    $this->getRecord()->status === CreditOrder::STATUS_ESCALATED
                    && Auth::user()->hasAnyRole(['super_admin', 'filament_admin'])
                )
                ->modalHeading('Review Escalated Order')
                ->modalWidth('2xl')
                ->form([
                    Placeholder::make('_summary')
                        ->hiddenLabel()
                        ->content(fn (): HtmlString => $this->orderSummaryHtml())
                        ->columnSpanFull(),

                    Select::make('assigned_branch_id')
                        ->label('Assign Branch (Production)')
                        ->options(Branch::where('is_active', true)->orderBy('name')->pluck('name', 'id')->toArray())
                        ->required()
                        ->searchable(),

                    Select::make('priority')
                        ->label('Order Priority')
                        ->options([
                            CreditOrder::PRIORITY_URGENT => '🔴 Urgent',
                            CreditOrder::PRIORITY_NORMAL => '🟡 Normal',
                            CreditOrder::PRIORITY_LOW    => '🟢 Low',
                        ])
                        ->default(CreditOrder::PRIORITY_NORMAL)
                        ->required(),

                    DatePicker::make('delivery_date')
                        ->label('Confirmed Delivery Date')
                        ->required()
                        ->minDate(today()),

                    Textarea::make('delivery_address')
                        ->label('Delivery Address')
                        ->rows(2)
                        ->placeholder('Confirmed delivery address...'),

                    Textarea::make('notes')
                        ->label('Approval Notes')
                        ->rows(2),
                ])
                ->action(function (array $data) {
                    try {
                        app(CreditOrderWorkflowService::class)->approve(
                            order:        $this->getRecord(),
                            branchId:     $data['assigned_branch_id'] ?? null,
                            priority:     $data['priority'] ?? CreditOrder::PRIORITY_NORMAL,
                            deliveryDate: $data['delivery_date'] ?? null,
                            deliveryAddr: $data['delivery_address'] ?? null,
                            notes:        $data['notes'] ?? null,
                        );
                        Notification::make()->title('Order approved and branch assigned.')->success()->send();
                        $this->redirect($this->getResource()::getUrl('view', ['record' => $this->getRecord()]));
                    } catch (\Throwable $e) {
                        Notification::make()->title('Error: ' . $e->getMessage())->danger()->send();
                    }
                }),

            // ── Reject from approval stage ─────────────────────────────────
            Action::make('reject')
                ->label('Reject Order')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () =>
                    in_array($this->getRecord()->status, [
                        CreditOrder::STATUS_PENDING_APPROVAL,
                        CreditOrder::STATUS_ESCALATED,
                    ])
                    && Auth::user()->hasAnyRole(['Accountant', 'super_admin', 'filament_admin'])
                )
                ->modalHeading('Reject & Cancel Order')
                ->form([
                    Placeholder::make('_summary')
                        ->hiddenLabel()
                        ->content(fn (): HtmlString => $this->orderSummaryHtml())
                        ->columnSpanFull(),

                    Textarea::make('reason')
                        ->label('Rejection Reason')
                        ->required()
                        ->rows(3)
                        ->placeholder('State the reason for rejection...'),
                ])
                ->action(function (array $data) {
                    try {
                        app(CreditOrderWorkflowService::class)->reject(
                            order:  $this->getRecord(),
                            reason: $data['reason'],
                        );
                        Notification::make()->title('Order rejected and cancelled.')->warning()->send();
                        $this->redirect($this->getResource()::getUrl('view', ['record' => $this->getRecord()]));
                    } catch (\Throwable $e) {
                        Notification::make()->title('Error: ' . $e->getMessage())->danger()->send();
                    }
                }),

            // ── Request Cancellation ───────────────────────────────────────
            Action::make('request_cancellation')
                ->label('Request Cancellation')
                ->icon('heroicon-o-no-symbol')
                ->color('warning')
                ->visible(fn () =>
                    in_array($this->getRecord()->status, [
                        CreditOrder::STATUS_APPROVED,
                        CreditOrder::STATUS_IN_PRODUCTION,
                    ])
                    && Auth::user()->hasAnyRole([
                        'Sales Executive', 'Sales Manager', 'super_admin', 'filament_admin',
                    ])
                )
                ->form([
                    Textarea::make('reason')
                        ->label('Reason for Cancellation Request')
                        ->required()
                        ->rows(3)
                        ->placeholder('Why do you want to cancel this order?'),
                ])
                ->action(function (array $data) {
                    try {
                        app(CreditOrderWorkflowService::class)->requestCancellation(
                            order:  $this->getRecord(),
                            reason: $data['reason'],
                        );
                        Notification::make()->title('Cancellation request submitted. Awaiting approval.')->warning()->send();
                        $this->redirect($this->getResource()::getUrl('view', ['record' => $this->getRecord()]));
                    } catch (\Throwable $e) {
                        Notification::make()->title('Error: ' . $e->getMessage())->danger()->send();
                    }
                }),

            // ── Approve Cancellation ───────────────────────────────────────
            Action::make('approve_cancellation')
                ->label('Approve Cancellation')
                ->icon('heroicon-o-check-circle')
                ->color('danger')
                ->visible(fn () =>
                    $this->getRecord()->status === CreditOrder::STATUS_CANCELLATION_REQUESTED
                    && Auth::user()->hasAnyRole(['Accountant', 'super_admin', 'filament_admin'])
                )
                ->requiresConfirmation()
                ->modalHeading('Approve Cancellation')
                ->modalDescription('This will permanently cancel the order. This cannot be undone.')
                ->form([
                    Textarea::make('notes')->label('Notes')->rows(2)->nullable(),
                ])
                ->action(function (array $data) {
                    try {
                        app(CreditOrderWorkflowService::class)->approveCancellation(
                            order: $this->getRecord(),
                            notes: $data['notes'] ?? null,
                        );
                        Notification::make()->title('Order cancellation approved.')->success()->send();
                        $this->redirect($this->getResource()::getUrl('view', ['record' => $this->getRecord()]));
                    } catch (\Throwable $e) {
                        Notification::make()->title('Error: ' . $e->getMessage())->danger()->send();
                    }
                }),

            // ── Reject Cancellation ────────────────────────────────────────
            Action::make('reject_cancellation')
                ->label('Reject Cancellation')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('info')
                ->visible(fn () =>
                    $this->getRecord()->status === CreditOrder::STATUS_CANCELLATION_REQUESTED
                    && Auth::user()->hasAnyRole(['super_admin', 'filament_admin'])
                )
                ->requiresConfirmation()
                ->modalHeading('Reject Cancellation Request')
                ->modalDescription('Order will be restored to Approved status.')
                ->form([
                    Textarea::make('notes')->label('Notes')->rows(2)->nullable(),
                ])
                ->action(function (array $data) {
                    try {
                        app(CreditOrderWorkflowService::class)->rejectCancellation(
                            order:     $this->getRecord(),
                            notes:     $data['notes'] ?? null,
                            restoreTo: CreditOrder::STATUS_APPROVED,
                        );
                        Notification::make()->title('Cancellation rejected. Order restored.')->info()->send();
                        $this->redirect($this->getResource()::getUrl('view', ['record' => $this->getRecord()]));
                    } catch (\Throwable $e) {
                        Notification::make()->title('Error: ' . $e->getMessage())->danger()->send();
                    }
                }),

            // ── Start Production ───────────────────────────────────────────
            Action::make('start_production')
                ->label('Start Production')
                ->icon('heroicon-o-cog-8-tooth')
                ->color('primary')
                ->visible(fn () =>
                    $this->getRecord()->status === CreditOrder::STATUS_APPROVED
                    && Auth::user()->hasAnyRole(['Production Manager', 'super_admin', 'filament_admin'])
                )
                ->requiresConfirmation()
                ->modalHeading('Start Production')
                ->modalDescription(fn () => 'Start production for order ' . $this->getRecord()->order_number . '?')
                ->form([
                    Textarea::make('notes')->label('Production Notes / Instructions')->rows(2)->nullable(),
                ])
                ->action(function (array $data) {
                    try {
                        app(CreditOrderWorkflowService::class)->startProduction(
                            order: $this->getRecord(),
                            notes: $data['notes'] ?? null,
                        );
                        Notification::make()->title('Production started.')->success()->send();
                        $this->redirect($this->getResource()::getUrl('view', ['record' => $this->getRecord()]));
                    } catch (\Throwable $e) {
                        Notification::make()->title('Error: ' . $e->getMessage())->danger()->send();
                    }
                }),

            // ── Mark Ready to Ship ─────────────────────────────────────────
            Action::make('ready_to_ship')
                ->label('Mark Ready to Ship')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn () =>
                    $this->getRecord()->status === CreditOrder::STATUS_IN_PRODUCTION
                    && Auth::user()->hasAnyRole(['Production Manager', 'super_admin', 'filament_admin'])
                )
                ->modalHeading('Quality Check Complete — Ready to Ship')
                ->form([
                    Textarea::make('qc_notes')
                        ->label('QC Notes')
                        ->placeholder('Quality observations, batch numbers, weight checks...')
                        ->rows(3)
                        ->nullable(),
                ])
                ->action(function (array $data) {
                    try {
                        app(CreditOrderWorkflowService::class)->markReadyToShip(
                            order:   $this->getRecord(),
                            qcNotes: $data['qc_notes'] ?? null,
                        );
                        Notification::make()->title('Marked ready to ship. Logistics team notified.')->success()->send();
                        $this->redirect($this->getResource()::getUrl('view', ['record' => $this->getRecord()]));
                    } catch (\Throwable $e) {
                        Notification::make()->title('Error: ' . $e->getMessage())->danger()->send();
                    }
                }),

            // ── Dispatch ───────────────────────────────────────────────────
            Action::make('dispatch')
                ->label('Dispatch / Ship')
                ->icon('heroicon-o-truck')
                ->color('success')
                ->visible(fn () =>
                    $this->getRecord()->status === CreditOrder::STATUS_READY_TO_SHIP
                    && Auth::user()->hasAnyRole([
                        'Dispatcher', 'Logistics Manager', 'super_admin', 'filament_admin',
                    ])
                )
                ->modalHeading('Dispatch Order')
                ->modalDescription('Marking as shipped will update the customer ledger and credit balance.')
                ->form([
                    Select::make('trip_id')
                        ->label('Assign to Trip')
                        ->options(function () {
                            return \DB::table('trip_assignments')
                                ->where('status', 'planned')
                                ->orderByDesc('trip_date')
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(fn ($t) => [
                                    $t->id => 'Trip #' . $t->id . ' — ' . $t->trip_date
                                        . ($t->purpose ? ' (' . $t->purpose . ')' : ''),
                                ])
                                ->toArray();
                        })
                        ->nullable()
                        ->placeholder('— No specific trip —')
                        ->searchable(),

                    Textarea::make('notes')->label('Dispatch Notes')->rows(2)->nullable(),
                ])
                ->action(function (array $data) {
                    try {
                        app(CreditOrderWorkflowService::class)->dispatch(
                            order:  $this->getRecord(),
                            tripId: $data['trip_id'] ?? null,
                            notes:  $data['notes'] ?? null,
                        );
                        Notification::make()->title('Order dispatched! Customer ledger updated.')->success()->send();
                        $this->redirect($this->getResource()::getUrl('view', ['record' => $this->getRecord()]));
                    } catch (\Throwable $e) {
                        Notification::make()->title('Error: ' . $e->getMessage())->danger()->send();
                    }
                }),

            // ── Confirm Delivery ───────────────────────────────────────────
            Action::make('confirm_delivery')
                ->label('Confirm Delivery')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn () =>
                    $this->getRecord()->status === CreditOrder::STATUS_SHIPPED
                    && Auth::user()->hasAnyRole([
                        'Logistics Manager', 'super_admin', 'filament_admin',
                    ])
                )
                ->requiresConfirmation()
                ->modalHeading('Confirm Delivery')
                ->modalDescription(fn () => 'Confirm delivery of order ' . $this->getRecord()->order_number . ' to the customer?')
                ->form([
                    Textarea::make('notes')->label('Delivery Notes')->rows(2)->nullable(),
                ])
                ->action(function (array $data) {
                    try {
                        app(CreditOrderWorkflowService::class)->confirmDelivery(
                            order: $this->getRecord(),
                            notes: $data['notes'] ?? null,
                        );
                        Notification::make()->title('Delivery confirmed.')->success()->send();
                        $this->redirect($this->getResource()::getUrl('view', ['record' => $this->getRecord()]));
                    } catch (\Throwable $e) {
                        Notification::make()->title('Error: ' . $e->getMessage())->danger()->send();
                    }
                }),

            // ── Record Payment ─────────────────────────────────────────────
            Action::make('record_payment')
                ->label('Record Payment')
                ->icon('heroicon-o-banknotes')
                ->color('info')
                ->visible(fn () =>
                    $this->getRecord()->payment_status !== CreditOrder::PAYMENT_PAID
                    && ! in_array($this->getRecord()->status, [
                        CreditOrder::STATUS_DRAFT, CreditOrder::STATUS_CANCELLED,
                    ])
                    && Auth::user()->hasAnyRole(['Accountant', 'super_admin', 'filament_admin'])
                )
                ->modalHeading('Record Payment')
                ->form([
                    Placeholder::make('_balance')
                        ->hiddenLabel()
                        ->content(fn (): HtmlString => new HtmlString(
                            '<div style="background:#f0fdf4;border:1px solid #86efac;border-radius:8px;padding:10px 14px;display:flex;justify-content:space-between;align-items:center;">'
                            . '<span style="font-size:13px;color:#166534;">Balance Due</span>'
                            . '<span style="font-size:20px;font-weight:700;color:#15803d;">৳ '
                            . number_format((float)$this->getRecord()->balance, 2) . '</span>'
                            . '</div>'
                        ))
                        ->columnSpanFull(),

                    TextInput::make('amount')
                        ->label('Payment Amount (৳)')
                        ->numeric()
                        ->required()
                        ->minValue(0.01)
                        ->step(0.01)
                        ->prefix('৳'),

                    Select::make('method')
                        ->label('Payment Method')
                        ->options([
                            'cash'   => '💵 Cash',
                            'bank'   => '🏦 Bank Transfer',
                            'cheque' => '📄 Cheque',
                            'mobile' => '📱 Mobile Banking (bKash/Nagad)',
                        ])
                        ->default('cash')
                        ->required(),

                    TextInput::make('reference')
                        ->label('Reference / Transaction ID')
                        ->placeholder('Cheque no., txn ID...')
                        ->nullable(),

                    Textarea::make('notes')->label('Notes')->rows(2)->nullable(),
                ])
                ->action(function (array $data) {
                    try {
                        app(CreditOrderWorkflowService::class)->recordPayment(
                            order:     $this->getRecord(),
                            amount:    (float) $data['amount'],
                            method:    $data['method'] ?? 'cash',
                            reference: $data['reference'] ?? null,
                            notes:     $data['notes'] ?? null,
                        );
                        Notification::make()
                            ->title('Payment of ৳' . number_format((float) $data['amount'], 2) . ' recorded.')
                            ->success()
                            ->send();
                        $this->redirect($this->getResource()::getUrl('view', ['record' => $this->getRecord()]));
                    } catch (\Throwable $e) {
                        Notification::make()->title('Error: ' . $e->getMessage())->danger()->send();
                    }
                }),
        ];
    }
}
