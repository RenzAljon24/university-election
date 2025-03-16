<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VoteResource\Pages;
use App\Models\Vote;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;


class VoteResource extends Resource
{
    protected static ?string $model = Vote::class;
    protected static ?string $navigationGroup = 'Election Management';
    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';

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
                            ->with(['candidate', 'election'])
                            ->get()
                            ->map(function ($vote) {
                                if ($vote->candidate) {
                                    $position = $vote->candidate->position ?? 'Unknown Position';
                                    return "{$vote->candidate->name} ({$position} - {$vote->election->name})";
                                }
                                return "Abstained ({$vote->position} - {$vote->election->name})"; // Use vote.position, not election->position
                            })
                            ->implode(', ');
                    }),

                TextColumn::make('latest_voted_at')
                    ->label('Last Voted At')
                    ->getStateUsing(function ($record) {
                        return Vote::where('student_id', $record->student_id)
                            ->max('voted_at');
                    })
                    ->sortable(),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                return $query
                    ->select('votes.student_id') // Select only once
                    ->selectRaw('MAX(votes.id) as id, MAX(votes.voted_at) as latest_voted_at')
                    ->join('students', 'students.id', '=', 'votes.student_id') // Ensure student info is accessible
                    ->groupBy('votes.student_id') // No need to group by `students.student_id`
                    ->with(['student', 'candidate', 'election']); // ✅ FIX: Remove `votes.`
            })
            ->defaultSort('student_id', 'asc')




            ->actions([

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
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