<?php

namespace App\Filament\Resources\WorshipServiceResource\Pages;

use App\Filament\Resources\WorshipServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateWorshipService extends CreateRecord
{
    protected static string $resource = WorshipServiceResource::class;
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
