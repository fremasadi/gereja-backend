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
        ])
        ->headerActions([
            Action::make('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->label('Download Absensi PDF')
                ->action(function () {
                    $records = \App\Models\Absensi::all();

                    $pdf = Pdf::loadView('pdf.absensi', [
                        'records' => $records,
                    ]);

                    return response()->streamDownload(
                        fn () => print($pdf->stream()),
                        'absensi.pdf'
                    );
                }),
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
            'index' => Pages\ListBarcodes::route('/'),
            'create' => Pages\CreateBarcode::route('/create'),
            'edit' => Pages\EditBarcode::route('/{record}/edit'),
        ];
    }
}
