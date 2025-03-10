<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentResource\Pages;
use App\Models\Student;
use App\Models\Election;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\DB;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;
    protected static ?string $navigationGroup = 'Election';
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationBadgeTooltip = 'The number of students';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('student_id')
                    ->required()
                    ->label('Student ID'),
                Forms\Components\TextInput::make('first_name')
                    ->required(),
                Forms\Components\TextInput::make('last_name')
                    ->required(),
                Forms\Components\TextInput::make('department')
                    ->required(),
                Select::make('elections')
                    ->multiple()
                    ->relationship('elections', 'name')
                    ->label('Assigned Elections'),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('department')
                    ->sortable()
                    ->badge()
                    ->label('Department'),
                TextColumn::make('student_id')->sortable()->searchable(),
                TextColumn::make('first_name')->sortable()->searchable(),
                TextColumn::make('last_name')->sortable()->searchable(),
                TextColumn::make('elections.name')
                    ->label('Assigned Elections')
                    ->formatStateUsing(
                        fn($record) =>
                        $record->elections->pluck('name')->join(', ')
                    ),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('department')
                    ->options(Student::query()->pluck('department', 'department')->toArray()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),


                Tables\Actions\BulkAction::make('bulk_edit_elections')
                    ->label('Assign Elections')
                    ->form([
                        Select::make('elections')
                            ->multiple()
                            ->options(Election::pluck('name', 'id')->toArray())
                            ->label('Assign to Elections')
                            ->default([]),
                    ])
                    ->action(function ($livewire, $data) {
                        // Select only students who are not assigned to an election
                        $selectedIds = Student::whereDoesntHave('elections')
                            ->whereIn('id', $livewire->selectedTableRecords)
                            ->limit(1000)
                            ->pluck('id')
                            ->toArray();

                        if (empty($selectedIds)) {
                            return;
                        }

                        // Process in chunks to prevent memory exhaustion
                        collect($selectedIds)->chunk(1000)->each(function ($chunk) use ($data) {
                            DB::table('election_student')->whereIn('student_id', $chunk)->delete();

                            $insertData = [];
                            foreach ($chunk as $studentId) {
                                foreach ($data['elections'] as $electionId) {
                                    $insertData[] = [
                                        'student_id' => $studentId,
                                        'election_id' => $electionId,
                                    ];
                                }
                            }

                            foreach (array_chunk($insertData, 1000) as $batch) {
                                DB::table('election_student')->insert($batch);
                            }
                        });
                    })
                    ->deselectRecordsAfterCompletion(),



            ])
            ->modifyQueryUsing(
                fn(\Illuminate\Database\Eloquent\Builder $query) =>
                $query->orderBy('department')->orderBy('last_name')
            )
            ->searchPlaceholder('Search students...');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
        ];
    }
}
