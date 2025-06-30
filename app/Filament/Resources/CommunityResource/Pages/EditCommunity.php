<?php

namespace App\Filament\Resources\CommunityResource\Pages;

use App\Filament\Resources\CommunityResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCommunity extends EditRecord
{
    protected static string $resource = CommunityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    // app/Filament/Resources/CommunityResource/Pages/EditCommunity.php
protected function mutateFormDataBeforeFill(array $data): array
{
    // Pastikan images ditampilkan dengan path yang benar untuk edit
    if (isset($data['images']) && is_array($data['images'])) {
        $data['images'] = collect($data['images'])->map(function ($image) {
            // Hapus prefix communities/ untuk tampilan edit
            return str_replace('communities/', '', $image);
        })->toArray();
    }
    
    return $data;
}

protected function mutateFormDataBeforeSave(array $data): array
{
    // Pastikan images disimpan dengan format yang konsisten
    if (isset($data['images']) && is_array($data['images'])) {
        $data['images'] = collect($data['images'])->map(function ($image) {
            // Tambahkan prefix communities/ jika belum ada
            return str_starts_with($image, 'communities/') 
                ? $image 
                : 'communities/' . ltrim($image, '/');
        })->toArray();
    }
    
    return $data;
}
}
