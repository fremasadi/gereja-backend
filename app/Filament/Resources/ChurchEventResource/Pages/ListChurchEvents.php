<?php

namespace App\Filament\Resources\ChurchEventResource\Pages;

use App\Filament\Resources\ChurchEventResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListChurchEvents extends ListRecords
{
    protected static string $resource = ChurchEventResource::class;

    protected function getHeaderActions(): array
{
    return [
        Actions\CreateAction::make()
            ->label('Tambah')
            ->icon('heroicon-o-plus')
            ->color('primary')
            ->extraAttributes([
                'style' => 'position: fixed; bottom: 16px; right: 16px; z-index: 50;',
                'class' => 'rounded-full ' .
                           'shadow-lg ' .
                           'py-3 px-4 ' .
                           'inline-flex items-center ' .
                           'bg-primary-600 ' .
                           'text-white ' .
                           'hover:bg-primary-700 ' .
                           'transition-all duration-300 ' .
                           'print:hidden'
            ])
    ];
}
}
