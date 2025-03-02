<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use App\Models\Election;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\StudentsImport;
use Illuminate\Support\Facades\Storage;

class ListStudents extends ListRecords
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            // ✅ Import Students with Multiple Elections
            Actions\Action::make('importStudents')
                ->label('Import Students')
                ->icon('heroicon-o-document-arrow-up')
                ->modalHeading('Import Students via Excel')
                ->modalWidth('lg')
                ->form([
                    FileUpload::make('file')
                        ->label('Upload Excel File')
                        ->disk('local')
                        ->directory('imports')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel'
                        ])
                        ->required(),
                    Select::make('election_ids') // ✅ Multiple selections allowed
                        ->label('Assign to Elections')
                        ->multiple()
                        ->options(Election::pluck('name', 'id')) // ✅ Fetch elections dynamically
                        ->required(),
                ])
                ->action(function (array $data) {
                    $filePath = Storage::disk('local')->path($data['file']); // ✅ Correct file path
        
                    Excel::import(new StudentsImport($data['election_ids']), $filePath);
                })
                ->successNotificationTitle('Students Imported Successfully'),
        ];
    }
}
