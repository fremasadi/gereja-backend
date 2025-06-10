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
            
            // Action Download Barcode Individual
            Tables\Actions\Action::make('downloadBarcode')
                ->label('Download Barcode')
                ->icon('heroicon-o-qr-code')
                ->color('success')
                ->action(function ($record) {
                    return response()->streamDownload(function () use ($record) {
                        echo view('pdf.barcode', compact('record'))->render();
                    }, 'barcode-' . $record->id . '.html', [
                        'Content-Type' => 'text/html',
                    ]);
                }),
        ])
        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
                
                // Bulk Action Download Multiple Barcodes
                Tables\Actions\BulkAction::make('downloadBarcodes')
                    ->label('Download Barcodes')
                    ->icon('heroicon-o-qr-code')
                    ->color('success')
                    ->action(function ($records) {
                        return response()->streamDownload(function () use ($records) {
                            echo view('pdf.barcode-bulk', compact('records'))->render();
                        }, 'barcodes-' . now()->format('Y-m-d-H-i-s') . '.html', [
                            'Content-Type' => 'text/html',
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
