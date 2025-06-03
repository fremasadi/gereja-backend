<?php

namespace App\Filament\Resources\SeatBookingResource\Pages;

use App\Filament\Resources\SeatBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSeatBookings extends ListRecords
{
    protected static string $resource = SeatBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
