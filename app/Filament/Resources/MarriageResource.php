<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MarriageResource\Pages;
use App\Filament\Resources\MarriageResource\RelationManagers;
use App\Models\Marriage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\FileUpload;

class MarriageResource extends Resource
{
    protected static ?string $model = Marriage::class;

    public static function getNavigationGroup(): string
    {
        return 'Presence & Service';
    }
    
    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-heart';
    }
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nama_lengkap_pria')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('nama_lengkap_wanita')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('no_telepon')
                    ->tel()
                    ->required()
                    ->maxLength(255),
                Forms\Components\DatePicker::make('tanggal_pernikahan')
                    ->required(),
                FileUpload::make('fotocopy_ktp')
                    ->image()
                    ->multiple()
                    ->label('Fotocopy KTP')
                    ->required(),
                
                FileUpload::make('fotocopy_kk')
                    ->image()
                    ->multiple()
                    ->label('Fotocopy KK')
                    ->required(),
                
                FileUpload::make('fotocopy_akte_kelahiran')
                    ->image()
                    ->multiple()
                    ->label('Akte Kelahiran')
                    ->required(),
                
                FileUpload::make('fotocopy_akte_baptis_selam')
                    ->image()
                    ->multiple()
                    ->label('Akte Baptis Selam')
                    ->required(),
                
                FileUpload::make('akte_nikah_orang_tua')
                    ->image()
                    ->multiple()
                    ->label('Akte Nikah Orang Tua')
                    ->required(),
                
                FileUpload::make('fotocopy_n1_n4')
                    ->image()
                    ->multiple()
                    ->label('Fotocopy N1-N4')
                    ->required(),
                
                FileUpload::make('foto_berdua')
                    ->image()
                    ->multiple()
                    ->label('Foto Berdua')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_lengkap_pria')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nama_lengkap_wanita')
                    ->searchable(),
                Tables\Columns\TextColumn::make('no_telepon')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tanggal_pernikahan')
                    ->date()
                    ->sortable(),
                // Tables\Columns\TextColumn::make('created_at')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
                // Tables\Columns\TextColumn::make('updated_at')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                
                Tables\Actions\Action::make('Lihat Dokumen')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading('Dokumen Pernikahan')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(function (\App\Models\Marriage $record) {
                        return view('filament.actions.view-marriage-documents', [
                            'record' => $record,
                        ]);
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
            'index' => Pages\ListMarriages::route('/'),
            // 'create' => Pages\CreateMarriage::route('/create'),
            // 'edit' => Pages\EditMarriage::route('/{record}/edit'),
        ];
    }
}
