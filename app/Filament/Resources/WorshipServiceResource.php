<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorshipServiceResource\Pages;
use App\Filament\Resources\WorshipServiceResource\RelationManagers;
use App\Models\WorshipService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TimePicker;

class WorshipServiceResource extends Resource
{
    protected static ?string $model = WorshipService::class;

    public static function getNavigationGroup(): string
    {
        return 'Worship & Events';    
    }
    
    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-calendar';
    }
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TimePicker::make('service_time')
                    ->required()
                    ->seconds(false)
                    ->minutesStep(15)
                    ->displayFormat('h:i A')
                    ->native(false)
                    ->label('Start Time')
                    ->helperText('Select the service start time'),
                Forms\Components\Toggle::make('is_active')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('service_time'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('download_barcode')
                    ->label('Download Barcode')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function ($record) {
                        $barcodeData = $record->id; // Bisa juga $record->name
                        $fileName = "barcode-{$barcodeData}.png";
            
                        // Generate barcode
                        $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
                        $barcodeImage = $generator->getBarcode($barcodeData, $generator::TYPE_CODE_128);
            
                        // Simpan sementara ke storage atau response langsung
                        $path = storage_path("app/public/{$fileName}");
                        file_put_contents($path, $barcodeImage);
            
                        return response()->download($path)->deleteFileAfterSend();
                    })
                    ->requiresConfirmation()
            ])
            
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorshipServices::route('/'),
            'create' => Pages\CreateWorshipService::route('/create'),
            'edit' => Pages\EditWorshipService::route('/{record}/edit'),
        ];
    }
}
