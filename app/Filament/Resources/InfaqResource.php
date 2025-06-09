<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InfaqResource\Pages;
use App\Filament\Resources\InfaqResource\RelationManagers;
use App\Models\Infaq;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InfaqResource extends Resource
{
    protected static ?string $model = Infaq::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('order_id')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('transaction_id')
                    ->maxLength(255),
                Forms\Components\TextInput::make('donor_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('donor_email')
                    ->email()
                    ->maxLength(255),
                Forms\Components\TextInput::make('amount')
                    ->required()
                    ->numeric(),
                Forms\Components\Textarea::make('message')
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_anonymous')
                    ->required(),
                Forms\Components\TextInput::make('status')
                    ->required(),
                Forms\Components\TextInput::make('payment_type')
                    ->maxLength(255),
                Forms\Components\TextInput::make('payment_code')
                    ->maxLength(255),
                Forms\Components\TextInput::make('midtrans_response'),
                Forms\Components\DateTimePicker::make('transaction_time'),
                Forms\Components\DateTimePicker::make('settlement_time'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_id')
                    ->searchable(),
                // Tables\Columns\TextColumn::make('transaction_id')
                //     ->searchable(),
                Tables\Columns\TextColumn::make('donor_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('donor_email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->numeric()
                    ->sortable(),
                // Tables\Columns\IconColumn::make('is_anonymous')
                //     ->boolean(),
                Tables\Columns\TextColumn::make('status'),
                // Tables\Columns\TextColumn::make('payment_type')
                //     ->searchable(),
                // Tables\Columns\TextColumn::make('payment_code')
                //     ->searchable(),
                // Tables\Columns\TextColumn::make('transaction_time')
                //     ->dateTime()
                //     ->sortable(),
                // Tables\Columns\TextColumn::make('settlement_time')
                //     ->dateTime()
                //     ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
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
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListInfaqs::route('/'),
            // 'create' => Pages\CreateInfaq::route('/create'),
            // 'edit' => Pages\EditInfaq::route('/{record}/edit'),
        ];
    }
}
