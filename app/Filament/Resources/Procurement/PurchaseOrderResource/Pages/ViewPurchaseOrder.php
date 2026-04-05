<?php

namespace App\Filament\Resources\Procurement\PurchaseOrderResource\Pages;

use App\Filament\Resources\Procurement\PurchaseOrderResource;
use App\Services\ProcurementService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewPurchaseOrder extends ViewRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [
            EditAction::make()
                ->visible(fn () => $this->record->isEditable()),
        ];

        if (config('procurement.features.po_approval', false)) {
            $actions[] = Action::make('approvePO')
                ->label('Approve PO')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->canBeApproved())
                ->requiresConfirmation()
                ->modalHeading('Approve Purchase Order')
                ->modalDescription('Are you sure you want to approve this purchase order?')
                ->action(function () {
                    app(ProcurementService::class)->approvePurchaseOrder($this->record);
                    $this->record->refresh();
                    Notification::make()->title('Purchase order approved.')->success()->send();
                });
        }

        $actions[] = Action::make('cancelPO')
            ->label('Cancel PO')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn () => !in_array($this->record->po_status, ['cancelled', 'completed']))
            ->form([
                Textarea::make('rejection_reason')
                    ->label('Reason for Cancellation')
                    ->required()
                    ->rows(3),
            ])
            ->modalHeading('Cancel Purchase Order')
            ->action(function (array $data) {
                app(ProcurementService::class)->cancelPurchaseOrder(
                    $this->record,
                    $data['rejection_reason'] ?? ''
                );
                $this->record->refresh();
                Notification::make()->title('Purchase order cancelled.')->warning()->send();
            });

        $actions[] = Action::make('closePO')
            ->label('Close PO')
            ->icon('heroicon-o-lock-closed')
            ->color('gray')
            ->visible(fn () => $this->record->isFullyReceived() && $this->record->delivery_status !== 'closed')
            ->requiresConfirmation()
            ->modalHeading('Close Purchase Order')
            ->modalDescription('Close this PO. No more GRNs can be added after closing.')
            ->action(function () {
                app(ProcurementService::class)->closePurchaseOrder($this->record);
                $this->record->refresh();
                Notification::make()->title('Purchase order closed.')->success()->send();
            });

        return $actions;
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->schema([

            Section::make('Order Information')
                ->columns(4)
                ->schema([
                    TextEntry::make('po_number')->label('PO Number'),
                    TextEntry::make('po_date')->label('PO Date')->date('d M Y'),
                    TextEntry::make('supplier_name')->label('Supplier'),
                    TextEntry::make('po_status')->label('Status')->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'draft'     => 'gray',
                            'submitted' => 'warning',
                            'approved'  => 'info',
                            'partial'   => 'primary',
                            'completed' => 'success',
                            'cancelled' => 'danger',
                            default     => 'gray',
                        }),
                ]),

            Section::make('Commodity & Pricing')
                ->columns(4)
                ->schema([
                    TextEntry::make('commodity_description')->label('Commodity'),
                    TextEntry::make('origin')->label('Origin'),
                    TextEntry::make('quantity')->label('Ordered Qty')
                        ->formatStateUsing(fn ($state, $record) =>
                            number_format((float) $state, 2) . ' ' . ($record->unit_of_measure ?? 'KG')),
                    TextEntry::make('unit_price')->label('Unit Price')
                        ->formatStateUsing(fn ($state) => number_format((float) $state, 4)),
                    TextEntry::make('total_order_value')->label('Total Order Value')
                        ->formatStateUsing(fn ($state, $record) =>
                            ($record->currency ?? 'BDT') . ' ' . number_format((float) $state, 2)),
                    TextEntry::make('payment_basis')->label('Payment Basis')
                        ->formatStateUsing(fn ($state) => $state === 'expected_qty' ? 'Expected Quantity' : 'Received Quantity'),
                    TextEntry::make('payment_terms')->label('Payment Terms')
                        ->formatStateUsing(fn ($state) => strtoupper($state ?? '')),
                ]),

            Section::make('Receipt & Payment Summary')
                ->columns(4)
                ->schema([
                    TextEntry::make('total_received_qty')->label('Total Received')
                        ->formatStateUsing(fn ($state, $record) =>
                            number_format((float) $state, 2) . ' ' . ($record->unit_of_measure ?? 'KG')),
                    TextEntry::make('qty_yet_to_receive')->label('Pending Receipt')
                        ->formatStateUsing(fn ($state, $record) =>
                            number_format((float) $state, 2) . ' ' . ($record->unit_of_measure ?? 'KG')),
                    TextEntry::make('total_paid')->label('Total Paid')
                        ->formatStateUsing(fn ($state, $record) =>
                            ($record->currency ?? 'BDT') . ' ' . number_format((float) $state, 2)),
                    TextEntry::make('balance_payable')->label('Balance Payable')
                        ->formatStateUsing(fn ($state, $record) =>
                            ($record->currency ?? 'BDT') . ' ' . number_format((float) $state, 2))
                        ->color(fn ($state) => (float) $state > 0 ? 'danger' : 'success'),
                    TextEntry::make('delivery_status')->label('Delivery')->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'pending'       => 'gray',
                            'partial'       => 'warning',
                            'completed'     => 'success',
                            'over_received' => 'danger',
                            'closed'        => 'gray',
                            default         => 'gray',
                        }),
                    TextEntry::make('payment_status')->label('Payment')->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'unpaid'  => 'danger',
                            'partial' => 'warning',
                            'paid'    => 'success',
                            'overpaid' => 'primary',
                            default   => 'gray',
                        }),
                ]),
        ]);
    }
}
