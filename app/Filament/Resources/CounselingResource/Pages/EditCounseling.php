<?php

namespace App\Filament\Resources\CounselingResource\Pages;

use App\Filament\Resources\CounselingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCounseling extends EditRecord
{
    protected static string $resource = CounselingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
