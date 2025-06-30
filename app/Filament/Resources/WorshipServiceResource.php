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
use SimpleSoftwareIO\QrCode\Generator;

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
                Tables\Actions\Action::make('download_qrcode')
                    ->label('Download QR Code')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function ($record) {
                        $qrData = $record->id; // Bisa juga name atau data lain
                        $fileName = "qrcode-{$qrData}.png";
    
                        // Generate QR Code PNG
                        $qrImage = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')->size(300)->generate($qrData);
    
                        return response()->streamDownload(function () use ($qrImage) {
                            echo $qrImage;
                        }, $fileName, [
                            'Content-Type' => 'image/png',
                            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                        ]);
                    })
                    ->requiresConfirmation()
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    // Bulk action untuk download multiple QR codes sebagai ZIP
                    Tables\Actions\BulkAction::make('downloadQRCodesZip')
                        ->label('Download QR Codes ZIP')
                        ->icon('heroicon-o-archive-box-arrow-down')
                        ->color('success')
                        ->action(function ($records) {
                            $zip = new \ZipArchive();
                            $zipFileName = 'qrcodes-' . now()->format('Y-m-d-H-i-s') . '.zip';
                            $tempZipPath = sys_get_temp_dir() . '/' . $zipFileName;
                            
                            if ($zip->open($tempZipPath, \ZipArchive::CREATE) === TRUE) {
                                foreach ($records as $record) {
                                    $qrData = $record->id;
                                    $fileName = "qrcode-{$qrData}.png";
                                    
                                    // Generate QR Code PNG
                                    $qrImage = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')->size(300)->generate($qrData);
                                    
                                    // Add to ZIP
                                    $zip->addFromString($fileName, $qrImage);
                                }
                                $zip->close();
                                
                                return response()->streamDownload(function () use ($tempZipPath) {
                                    readfile($tempZipPath);
                                    unlink($tempZipPath); // Delete temp file after streaming
                                }, $zipFileName, [
                                    'Content-Type' => 'application/zip',
                                    'Content-Disposition' => 'attachment; filename="' . $zipFileName . '"',
                                ]);
                            }
                            
                            return response()->json(['error' => 'Could not create ZIP file'], 500);
                        })
                        ->deselectRecordsAfterCompletion(),
                        
                    // Bulk action untuk download multiple QR codes sebagai PDF
                    Tables\Actions\BulkAction::make('downloadQRCodesPDF')
                        ->label('Download QR Codes PDF')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('info')
                        ->action(function ($records) {
                            // Pastikan Anda sudah install: composer require barryvdh/laravel-dompdf
                            $qrCodeData = [];
                            
                            foreach ($records as $record) {
                                $qrData = $record->id;
                                
                                // Generate QR Code sebagai base64
                                $qrImage = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')->size(200)->generate($qrData);
                                $qrBase64 = 'data:image/png;base64,' . base64_encode($qrImage);
                                
                                $qrCodeData[] = [
                                    'record' => $record,
                                    'qrCodeImage' => $qrBase64,
                                    'qrData' => $qrData
                                ];
                            }
                            
                            // Buat HTML sederhana untuk PDF
                            $html = '<html><body>';
                            $html .= '<h1 style="text-align: center;">QR Codes</h1>';
                            $html .= '<div style="display: flex; flex-wrap: wrap; justify-content: center;">';
                            
                            foreach ($qrCodeData as $data) {
                                $html .= '<div style="margin: 20px; text-align: center; page-break-inside: avoid;">';
                                $html .= '<h3>' . htmlspecialchars($data['record']->name) . '</h3>';
                                $html .= '<img src="' . $data['qrCodeImage'] . '" style="width: 200px; height: 200px;">';
                                $html .= '<p>ID: ' . $data['record']->id . '</p>';
                                $html .= '</div>';
                            }
                            
                            $html .= '</div></body></html>';
                            
                            // Jika menggunakan DomPDF
                            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
                            $pdfFileName = 'qrcodes-' . now()->format('Y-m-d-H-i-s') . '.pdf';
                            
                            return response()->streamDownload(function () use ($pdf) {
                                echo $pdf->output();
                            }, $pdfFileName, [
                                'Content-Type' => 'application/pdf',
                                'Content-Disposition' => 'attachment; filename="' . $pdfFileName . '"',
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
            'index' => Pages\ListWorshipServices::route('/'),
            'create' => Pages\CreateWorshipService::route('/create'),
            'edit' => Pages\EditWorshipService::route('/{record}/edit'),
        ];
    }
}
