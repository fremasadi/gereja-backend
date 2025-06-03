<?php

namespace App\Filament\Resources\WorshipServiceResource\Pages;

use App\Filament\Resources\WorshipServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWorshipService extends EditRecord
{
    protected static string $resource = WorshipServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
