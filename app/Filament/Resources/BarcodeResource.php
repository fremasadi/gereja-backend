<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BarcodeResource\Pages;
use App\Filament\Resources\BarcodeResource\RelationManagers;
use App\Models\Barcode;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\Action;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class BarcodeResource extends Resource
{
    protected static ?string $model = Barcode::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('tanggal')
                    ->required(),
                TextInput::make('checkin_time')
                    ->label('Check-in Time')
                    ->type('time') // agar menjadi time picker
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('tanggal')
                ->date()
                ->sortable(),
            Tables\Columns\TextColumn::make('checkin_time'),
        ])
        ->filters([
            //
        ])
        ->actions([
            Tables\Actions\EditAction::make(),
            
            // Action Download Barcode PDF Individual
            Tables\Actions\Action::make('downloadBarcodePDF')
                ->label('Download Barcode PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function ($record) {
                    // Generate barcode image
                    $generator = new DNS1D();
                    $barcodeData = str_pad($record->id, 8, '0', STR_PAD_LEFT);
                    $barcodeBase64 = $generator->getBarcodePNG($barcodeData, 'C128', 3, 50);
                    
                    // Generate PDF
                    $pdf = Pdf::loadView('pdf.barcode', [
                        'record' => $record,
                        'barcodeImage' => 'data:image/png;base64,' . $barcodeBase64,
                        'barcodeData' => $barcodeData
                    ]);
                    
                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->output();
                    }, 'barcode-' . $record->id . '.pdf', [
                        'Content-Type' => 'application/pdf',
                    ]);
                }),
        ])
        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
                
                // Bulk Action Download Multiple Barcodes PDF
                Tables\Actions\BulkAction::make('downloadBarcodesPDF')
                    ->label('Download Barcodes PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function ($records) {
                        $generator = new DNS1D();
                        $barcodeData = [];
                        
                        foreach ($records as $record) {
                            $barcodeNumber = str_pad($record->id, 8, '0', STR_PAD_LEFT);
                            $barcodeData[] = [
                                'record' => $record,
                                'barcodeImage' => 'data:image/png;base64,' . $generator->getBarcodePNG($barcodeNumber, 'C128', 2, 40),
                                'barcodeData' => $barcodeNumber
                            ];
                        }
                        
                        $pdf = Pdf::loadView('pdf.barcode-bulk', [
                            'barcodeData' => $barcodeData,
                            'totalRecords' => count($barcodeData)
                        ]);
                        
                        return response()->streamDownload(function () use ($pdf) {
                            echo $pdf->output();
                        }, 'barcodes-' . now()->format('Y-m-d-H-i-s') . '.pdf', [
                            'Content-Type' => 'application/pdf',
                        ]);
                    })
                    ->deselectRecordsAfterCompletion(),
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
            'index' => Pages\ListBarcodes::route('/'),
            'create' => Pages\CreateBarcode::route('/create'),
            'edit' => Pages\EditBarcode::route('/{record}/edit'),
        ];
    }
}
