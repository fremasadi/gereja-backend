<?php

namespace App\Filament\Resources\InfaqResource\Pages;

use App\Filament\Resources\InfaqResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInfaq extends EditRecord
{
    protected static string $resource = InfaqResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
