<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChurchEventResource\Pages;
use App\Filament\Resources\ChurchEventResource\RelationManagers;
use App\Models\ChurchEvent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ChurchEventResource extends Resource
{
    protected static ?string $model = ChurchEvent::class;

    public static function getNavigationGroup(): string
    {
        return 'Worship & Events';    
    }
    
    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-megaphone';
    }
    
    public static function form(Form $form): Form
{
    return $form
        ->schema([
            Forms\Components\TextInput::make('location_name')
                ->required()
                ->maxLength(255),
            
            Forms\Components\Textarea::make('location_address')
                ->required()
                ->columnSpanFull(),

            Forms\Components\Textarea::make('location_spesific')
                ->required(),

                Forms\Components\Toggle::make('is_recurring')
                ->label('Is Recurring?')
                ->reactive(),
            
            Forms\Components\DatePicker::make('date')
                ->label('Date (for non-recurring event)')
                ->required(fn ($get) => !$get('is_recurring'))
                ->visible(fn ($get) => !$get('is_recurring')),
            
            
            Forms\Components\Select::make('recurring_days')
                ->label('Recurring Days')
                ->multiple()
                ->options([
                    'monday' => 'Monday',
                    'tuesday' => 'Tuesday',
                    'wednesday' => 'Wednesday',
                    'thursday' => 'Thursday',
                    'friday' => 'Friday',
                    'saturday' => 'Saturday',
                    'sunday' => 'Sunday',
                ])
                ->visible(fn ($get) => $get('is_recurring')),
            

            Forms\Components\TimePicker::make('time')
                ->required(),

            Forms\Components\TextInput::make('theme')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('bible_verse')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('sermon_title')
                ->required()
                ->maxLength(255),

            Forms\Components\Textarea::make('sermon_content')
                ->required()
                ->columnSpanFull()
                ->rows(10),

            Forms\Components\FileUpload::make('images')
                ->image()
                ->required(),

            Forms\Components\Hidden::make('created_by')
                ->default(auth()->id()),
        ]);
}

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('location_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('time'),
                Tables\Columns\TextColumn::make('theme')
                    ->searchable(),
                Tables\Columns\TextColumn::make('bible_verse')
                    ->searchable(),
                Tables\Columns\TextColumn::make('sermon_title')
                    ->searchable(),
                // Tables\Columns\TextColumn::make('images')
                //     ->searchable(),
                // Tables\Columns\TextColumn::make('created_by')
                //     ->numeric()
                //     ->sortable(),
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
            'index' => Pages\ListChurchEvents::route('/'),
            'create' => Pages\CreateChurchEvent::route('/create'),
            'edit' => Pages\EditChurchEvent::route('/{record}/edit'),
        ];
    }

    public static function mutateFormDataBeforeCreate(array $data): array
{
    $data['created_by'] = auth()->id();
    return $data;
}

}
