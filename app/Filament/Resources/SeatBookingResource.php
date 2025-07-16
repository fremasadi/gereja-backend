<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SeatBookingResource\Pages;
use App\Filament\Resources\SeatBookingResource\RelationManagers;
use App\Models\SeatBooking;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\DatePicker;
use Illuminate\Support\Carbon;
use Filament\Forms\Components\Select;

class SeatBookingResource extends Resource
{
    protected static ?string $model = SeatBooking::class;

    public static function getNavigationGroup(): string
    {
        return 'Worship & Events';    
    }
    
    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-ticket';
    }
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('worship_service_id')
                    ->relationship('worshipService', 'name')
                    ->required(),
                Forms\Components\Select::make('seat_id')
                    ->relationship('seat', 'label')
                    ->required(),
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                    Select::make('service_date')
                    ->required()
                    ->options(function () {
                        $options = [];
                        $currentDate = now()->next(Carbon::SUNDAY);
                        
                        for ($i = 0; $i < 12; $i++) { // 12 minggu ke depan
                            $dateString = $currentDate->format('Y-m-d');
                            $displayDate = $currentDate->translatedFormat('l, j F Y');
                            $options[$dateString] = $displayDate;
                            $currentDate->addWeek();
                        }
                        
                        return $options;
                    })
                    ->searchable()
                    ->label('Pilih Minggu Ibadah')

                
                // Forms\Components\TextInput::make('status')
                //     ->required()
                //     ->maxLength(255)
                //     ->default('booked'),
                // Forms\Components\TextInput::make('booking_code')
                //     ->required()
                //     ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('worshipService.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('seat.label')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('service_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('booking_code')
                    ->searchable(),
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
            ->defaultSort('created_at', 'desc')

            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListSeatBookings::route('/'),
            'create' => Pages\CreateSeatBooking::route('/create'),
            'edit' => Pages\EditSeatBooking::route('/{record}/edit'),
        ];
    }
}
