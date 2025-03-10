<?php

namespace App\Imports;

use App\Models\Student;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\DB;

class StudentsImport implements ToModel, WithHeadingRow
{
    protected $electionIds;
    protected $batchSize = 500; // ✅ Process 500 students at a time

    public function __construct(array $electionIds)
    {
        $this->electionIds = $electionIds;
    }

    public function model(array $row)
    {
        // Normalize keys to remove spaces and enforce lowercase
        $normalizedRow = [];
        foreach ($row as $key => $value) {
            $normalizedKey = strtolower(str_replace(' ', '_', trim($key)));
            $normalizedRow[$normalizedKey] = trim($value);
        }

        // Ensure student_id exists
        if (empty($normalizedRow['student_id'])) {
            return null;
        }

        // Bulk insert preparation
        static $students = [];
        $students[] = [
            'student_id' => (string) $normalizedRow['student_id'],
            'first_name' => $normalizedRow['first_name'] ?? null,
            'last_name' => $normalizedRow['last_name'] ?? null,
            'department' => $normalizedRow['department'] ?? null,
        ];

        // Process in batches to optimize performance
        if (count($students) >= $this->batchSize) {
            $this->insertStudents($students);
            $students = []; // Reset batch
        }

        return null; // ✅ No need to return individual model instances
    }

    protected function insertStudents(array $students)
    {
        // ✅ Bulk insert/update students without timestamps
        Student::upsert($students, ['student_id'], ['first_name', 'last_name', 'department']);

        // ✅ Attach students to elections
        $studentIds = Student::whereIn('student_id', collect($students)->pluck('student_id'))->pluck('id');
        $pivotData = [];
        foreach ($studentIds as $studentId) {
            foreach ($this->electionIds as $electionId) {
                $pivotData[] = [
                    'student_id' => $studentId,
                    'election_id' => $electionId,
                ];
            }
        }

        if (!empty($pivotData)) {
            DB::table('election_student')->insertOrIgnore($pivotData);
        }
    }
}
