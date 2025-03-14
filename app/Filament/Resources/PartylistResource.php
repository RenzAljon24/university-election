<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PartylistResource\Pages;
use App\Filament\Resources\PartylistResource\RelationManagers;
use App\Models\Partylist;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;


use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;

class PartylistResource extends Resource
{
    protected static ?string $model = Partylist::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';
    protected static ?string $navigationGroup = 'Election Management';
    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Select::make('election_id')
                    ->relationship('election', 'name')
                    ->required(),
                TextInput::make('name')->required(),
                FileUpload::make('logo')
                    ->image()
                    ->directory('partylists') // Stores in storage/app/public/partylists
                    ->visibility('public')
                    ->required(),

            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo')->circular(),
                TextColumn::make('name')->sortable()->searchable(),
                TextColumn::make('election.name')->label('Election')->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListPartylists::route('/'),
            'create' => Pages\CreatePartylist::route('/create'),
            'edit' => Pages\EditPartylist::route('/{record}/edit'),
        ];
    }
}
