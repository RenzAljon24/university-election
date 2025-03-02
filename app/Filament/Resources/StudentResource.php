<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentResource\Pages;
use App\Models\Student;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms\Components\Select;

use Filament\Tables\Columns\TextColumn;



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
                TextColumn::make('elections')
                    ->label('Assigned Elections')
                    ->formatStateUsing(fn($record) => $record->elections->pluck('name')->join(', ')),
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
                    ->form(fn($records) => [
                        Select::make('elections')
                            ->multiple()
                            ->relationship('elections', 'name')
                            ->label('Assign to Elections')
                            ->default(
                                $records->flatMap(fn($record) => $record->elections->pluck('id'))->unique()->toArray()
                            ), // Preload existing elections
                    ])
                    ->action(function ($records, $data) {
                        $records->each(function ($record) use ($data) {
                            $existingElections = $record->elections->pluck('id')->toArray();
                            $newElections = array_merge($existingElections, $data['elections']);
                            $record->elections()->sync(array_unique($newElections)); // Keep old & new elections
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
