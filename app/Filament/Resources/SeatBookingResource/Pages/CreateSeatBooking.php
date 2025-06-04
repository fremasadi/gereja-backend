<?php

namespace App\Filament\Resources\SeatBookingResource\Pages;

use App\Filament\Resources\SeatBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSeatBooking extends CreateRecord
{
    protected static string $resource = SeatBookingResource::class;
    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Simpan'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
