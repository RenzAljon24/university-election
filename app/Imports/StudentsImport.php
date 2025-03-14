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
    protected $students = []; // ✅ Store students in memory

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
        $this->students[] = [
            'student_id' => (string) $normalizedRow['student_id'],
            'first_name' => $normalizedRow['first_name'] ?? null,
            'last_name' => $normalizedRow['last_name'] ?? null,
            'middle_name' => !empty($normalizedRow['middle_name']) ? $normalizedRow['middle_name'] : null,
            'college' => $normalizedRow['college'] ?? null,
            'course' => $normalizedRow['course'] ?? null,
            'session' => $normalizedRow['session'] ?? null,
            'semester' => $normalizedRow['semester'] ?? null,
            'learning_modality' => $normalizedRow['learning_modality'] ?? null,
        ];

        // Process in batches to optimize performance
        if (count($this->students) >= $this->batchSize) {
            $this->insertStudents();
        }

        return null; // ✅ No need to return individual model instances
    }

    protected function insertStudents()
    {
        if (!empty($this->students)) {
            // ✅ Bulk insert/update students
            Student::upsert(
                $this->students,
                ['student_id'],
                ['first_name', 'last_name', 'middle_name', 'college', 'course', 'session', 'semester', 'learning_modality']
            );

            // ✅ Attach students to elections
            $studentIds = Student::whereIn('student_id', collect($this->students)->pluck('student_id'))->pluck('id');
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

            // ✅ Clear batch after insertion
            $this->students = [];
        }
    }

    public function __destruct()
    {
        // ✅ Insert remaining students when the import is done
        $this->insertStudents();
    }
}
