<?php

namespace App\Filament\Resources\CounselingResource\Pages;

use App\Filament\Resources\CounselingResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCounseling extends CreateRecord
{
    protected static string $resource = CounselingResource::class;
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
