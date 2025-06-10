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
use Milon\Barcode\DNS2D;
use Filament\Forms\Components\TimePicker;

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
                    TimePicker::make('checkin_time')
                    ->label('Check-in Time')
                    ->seconds(false) // hilangkan detik jika tidak perlu
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
            
            // Action Download QR Code PDF Individual
            Tables\Actions\Action::make('downloadQRCodePDF')
                ->label('Download QR Code PDF')
                ->icon('heroicon-o-qr-code')
                ->color('success')
                ->action(function ($record) {
                    // Generate QR Code dengan data lengkap
                    $generator = new DNS2D();
                    
                    // Data yang akan disimpan dalam QR Code
                    $qrData = json_encode([
                        'id' => $record->id,
                        'tanggal' => $record->tanggal ? $record->tanggal->format('Y-m-d') : null,
                        'checkin_time' => $record->checkin_time,
                        'type' => 'attendance_record'
                    ]);
                    
                    $qrCodeBase64 = $generator->getBarcodePNG($qrData, 'QRCODE', 8, 8);
                    
                    // Generate PDF
                    $pdf = Pdf::loadView('pdf.barcode', [
                        'record' => $record,
                        'qrCodeImage' => 'data:image/png;base64,' . $qrCodeBase64,
                        'qrData' => $qrData
                    ]);
                    
                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->output();
                    }, 'qrcode-' . $record->id . '.pdf', [
                        'Content-Type' => 'application/pdf',
                    ]);
                }),
        ])
        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
                
                // Bulk Action Download Multiple QR Codes PDF
                Tables\Actions\BulkAction::make('downloadQRCodesPDF')
                    ->label('Download QR Codes PDF')
                    ->icon('heroicon-o-qr-code')
                    ->color('success')
                    ->action(function ($records) {
                        $generator = new DNS2D();
                        $qrCodeData = [];
                        
                        foreach ($records as $record) {
                            $qrData = json_encode([
                                'id' => $record->id,
                                'tanggal' => $record->tanggal ? $record->tanggal->format('Y-m-d') : null,
                                'checkin_time' => $record->checkin_time,
                                'type' => 'attendance_record'
                            ]);
                            
                            $qrCodeData[] = [
                                'record' => $record,
                                'qrCodeImage' => 'data:image/png;base64,' . $generator->getBarcodePNG($qrData, 'QRCODE', 6, 6),
                                'qrData' => $qrData
                            ];
                        }
                        
                        $pdf = Pdf::loadView('pdf.barcode-bulk', [
                            'qrCodeData' => $qrCodeData,
                            'totalRecords' => count($qrCodeData)
                        ]);
                        
                        return response()->streamDownload(function () use ($pdf) {
                            echo $pdf->output();
                        }, 'qrcodes-' . now()->format('Y-m-d-H-i-s') . '.pdf', [
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
