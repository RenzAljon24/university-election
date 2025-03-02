<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VoteResource\Pages;
use App\Models\Vote;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class VoteResource extends Resource
{
    protected static ?string $model = Vote::class;
    protected static ?string $navigationGroup = 'Election';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('student_id')
                    ->relationship('student', 'student_id')
                    ->required(),
                Select::make('candidate_id')
                    ->relationship('candidate', 'name')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.student_id')
                    ->label('Voter')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('candidate_names')
                    ->label('Voted Candidates')
                    ->getStateUsing(function ($record) {
                        return Vote::where('student_id', $record->student_id)
                            ->with(['candidate', 'election']) // ✅ Only loading required relationships
                            ->get()
                            ->map(function ($vote) {
                                if ($vote->candidate) {
                                    $position = $vote->candidate->position ?? 'Unknown Position'; // ✅ Avoids errors if position is null
                                    return "{$vote->candidate->name} ({$position} - {$vote->election->name})";
                                } else {
                                    return "Abstained in {$vote->election->name}"; // ✅ Fix for null candidate
                                }
                            })
                            ->implode(', ');
                    }),

                TextColumn::make('voted_at')
                    ->label('Last Voted At')
                    ->sortable(),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                // Create a subquery for the latest votes by student
                $latestVotes = DB::table('votes')
                    ->select('student_id', DB::raw('MAX(voted_at) as voted_at'))
                    ->groupBy('student_id');

                // Join this back to the main votes table to get all columns
                return $query
                    ->joinSub($latestVotes, 'latest_votes', function ($join) {
                    $join->on('votes.student_id', '=', 'latest_votes.student_id')
                        ->on('votes.voted_at', '=', 'latest_votes.voted_at');
                });
            })
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVotes::route('/'),
            'create' => Pages\CreateVote::route('/create'),
            'edit' => Pages\EditVote::route('/{record}/edit'),
        ];
    }
}
