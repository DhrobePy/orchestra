<?php

namespace App\Filament\Resources\Sales\CustomerPaymentResource\Pages;

use App\Filament\Resources\Sales\CustomerPaymentResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomerPayment extends ViewRecord
{
    protected static string $resource = CustomerPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print_receipt')
                ->label('Print Receipt')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn () => route('print.payment.receipt', $this->getRecord()->id))
                ->openUrlInNewTab(),

            EditAction::make(),
        ];
    }
}
