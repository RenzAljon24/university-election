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

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;
    protected static ?string $navigationGroup = 'Election Management';
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
                Forms\Components\TextInput::make('college')
                    ->required(),
                Forms\Components\TextInput::make('course')
                    ->required(),
                Forms\Components\TextInput::make('session')
                    ->required(),
                Forms\Components\TextInput::make('semester')
                    ->required(),
                Forms\Components\TextInput::make('learning_modality')
                    ->required(),
                Select::make('elections')
                    ->multiple()
                    ->relationship('elections', 'name') // Correct relationship usage
                    ->label('Assigned Elections'),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('college')
                    ->sortable()
                    ->badge()

                    ->label('College'),
                TextColumn::make('student_id')->sortable()->searchable(isIndividual: true),
                TextColumn::make('first_name')->sortable()->searchable(isIndividual: true),
                TextColumn::make('last_name')->sortable()->searchable(isIndividual: true),
                TextColumn::make('middle_name')->sortable()->searchable(isIndividual: true),
                TextColumn::make('course')->sortable()->searchable(isIndividual: true),
                TextColumn::make('session')->sortable()->searchable(),
                TextColumn::make('semester')->sortable()->searchable(),
                TextColumn::make('learning_modality')->sortable()->searchable(),

                TextColumn::make('elections.name')
                    ->searchable(isIndividual: true)
                    ->label('Assigned Elections')
                    ->formatStateUsing(
                        fn($record) =>
                        $record->elections->pluck('name')->join(', ')
                    ),

            ])
            ->filters([
                Tables\Filters\SelectFilter::make('college')
                    ->options(Student::query()->pluck('college', 'college')->toArray()),

                Tables\Filters\SelectFilter::make('course')
                    ->options(Student::query()->pluck('course', 'course')->toArray()),

                Tables\Filters\SelectFilter::make('semester')
                    ->options(Student::query()->pluck('semester', 'semester')->toArray()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\BulkAction::make('bulk_edit_elections')
                    ->label('Assign Elections')
                    ->form(fn($records) => [
                        Select::make('elections')
                            ->multiple()
                            ->options(Election::pluck('name', 'id')->toArray()) // Get available elections
                            ->label('Assign to Elections')
                            ->default(
                                $records->flatMap(fn($record) => $record->elections->pluck('id'))->unique()->toArray()
                            ), // Preload existing elections
                    ])
                    ->action(function ($records, $data) {
                        $records->each(function ($record) use ($data) {
                            $record->elections()->sync($data['elections']); // Sync elections
                        });
                    })
                    ->deselectRecordsAfterCompletion(),
            ])
            ->modifyQueryUsing(
                fn(\Illuminate\Database\Eloquent\Builder $query) =>
                $query->orderBy('college')->orderBy('last_name')
            )
            ->searchPlaceholder('Search students...');
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
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
        ];
    }
}