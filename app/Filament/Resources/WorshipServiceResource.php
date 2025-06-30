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
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Milon\Barcode\DNS2D;
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
             // 👁️ Show QR Code dalam Modal
    Tables\Actions\Action::make('showQRCodeModal')
    ->label('Tampilkan QR Code')
    ->icon('heroicon-o-eye')
    ->color('primary')
    ->modalHeading('QR Code Data ID')
    ->modalSubmitAction(false) // Tidak butuh tombol submit
    ->modalCancelActionLabel('Tutup')
    ->modalContent(function ($record) {
        $generator = new \Milon\Barcode\DNS2D();
        $qrData = (string) $record->id;
        $qrCodeBase64 = $generator->getBarcodePNG($qrData, 'QRCODE', 8, 8);
        $qrImage = 'data:image/png;base64,' . $qrCodeBase64;

        return view('components.qr-code-modal', [
            'qrCodeImage' => $qrImage,
            'qrData' => $qrData,
        ]);
    }),
    //         Tables\Actions\Action::make('downloadQRCodePDF')
    // ->label('Download QR Code PDF')
    // ->icon('heroicon-o-qr-code')
    // ->color('success')
    // ->action(function ($record) {
    //     $generator = new \Milon\Barcode\DNS2D();

    //     // 🧾 Simpan hanya ID (bukan JSON)
    //     $qrData = (string) $record->id;

    //     $qrCodeBase64 = $generator->getBarcodePNG($qrData, 'QRCODE', 8, 8);

    //     $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.barcode', [
    //         'record' => $record,
    //         'qrCodeImage' => 'data:image/png;base64,' . $qrCodeBase64,
    //         'qrData' => $qrData,
    //     ]);

    //     return response()->streamDownload(function () use ($pdf) {
    //         echo $pdf->output();
    //     }, 'qrcode-' . $record->id . '.pdf', [
    //         'Content-Type' => 'application/pdf',
    //     ]);
    // })


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
