<?php

namespace App\Filament\Resources\MarriageResource\Pages;

use App\Filament\Resources\MarriageResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateMarriage extends CreateRecord
{
    protected static string $resource = MarriageResource::class;
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
