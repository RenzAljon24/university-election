<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CandidateResource\Pages;
use App\Filament\Resources\CandidateResource\RelationManagers;
use App\Models\Candidate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CandidateResource extends Resource
{
    protected static ?string $model = Candidate::class;
    protected static ?string $navigationGroup = 'Election Management';
    protected static ?string $navigationIcon = 'heroicon-o-user';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Select::make('partylist_id')
                    ->relationship('partylist', 'name')
                    ->reactive() // 🔥 This makes it listen for changes
                    ->afterStateUpdated(fn($set) => $set('election_id', null)) // Reset election when partylist changes
                    ->nullable(),

                Select::make('election_id')
                    ->label('Election')
                    ->options(
                        fn($get) =>
                        \App\Models\Election::whereHas(
                            'partylists',
                            fn($query) =>
                            $query->where('id', $get('partylist_id'))
                        )->pluck('name', 'id')
                    )
                    ->required(),

                TextInput::make('name')->required(),
                TextInput::make('position')->required(),
                FileUpload::make('photo')
                    ->image()
                    ->directory('candidates')
                    ->visibility('public')
                    ->required(),
            ]);
    }


    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                ImageColumn::make('photo')->circular(),
                TextColumn::make('name')->sortable()->searchable(),
                TextColumn::make('position')->sortable()->searchable(),
                TextColumn::make('election.name')->label('Election')->sortable(),
                TextColumn::make('partylist.name')->label('Partylist')->sortable(),
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
            'index' => Pages\ListCandidates::route('/'),
            'create' => Pages\CreateCandidate::route('/create'),
            'edit' => Pages\EditCandidate::route('/{record}/edit'),
        ];
    }
}
