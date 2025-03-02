<?php

namespace App\Imports;

use App\Models\Student;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StudentsImport implements ToModel, WithHeadingRow
{
    protected $electionIds;

    public function __construct(array $electionIds)
    {
        $this->electionIds = $electionIds; // ✅ Store election IDs
    }

    public function model(array $row)
    {
        // Normalize keys to remove spaces and enforce lowercase
        $normalizedRow = [];
        foreach ($row as $key => $value) {
            $normalizedKey = strtolower(str_replace(' ', '_', trim($key)));
            $normalizedRow[$normalizedKey] = trim($value);
        }

        // dd($normalizedRow); // Debug before inserting

        // Ensure student_id exists
        if (empty($normalizedRow['student_id'])) {
            return null;
        }

        // Create or update student
        $student = Student::firstOrCreate(
            ['student_id' => (string) $normalizedRow['student_id']],
            [
                'first_name' => $normalizedRow['first_name'] ?? null,
                'last_name' => $normalizedRow['last_name'] ?? null,
                'department' => $normalizedRow['department'] ?? null,
            ]
        );

        // Attach student to elections
        $student->elections()->syncWithoutDetaching($this->electionIds);

        return $student;
    }


}
