<?php

namespace App\Filament\Resources\ChurchEventResource\Pages;

use App\Filament\Resources\ChurchEventResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateChurchEvent extends CreateRecord
{
    protected static string $resource = ChurchEventResource::class;
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
