<?php

namespace App\Imports;

use App\Models\Mark;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;

class MarksImport implements ToModel, WithHeadingRow, WithStartRow
{

    protected Collection $studentsCache;

    public function __construct(protected $examId, protected $branchId)
    {
        $this->studentsCache = Student::where('branch_id', $this->branchId)
            ->with('classes.subjects')
            ->get()
            ->keyBy('roll_number');
    }

    public function headingRow(): int
    {
        return 6;
    }

    public function startRow(): int
    {
        return 7;
    }

    public function model(array $row)
    {
        $rollNumber = $row['roll_number'];
        $subjectName = trim($row['subject']);

        $student = $this->studentsCache->get($rollNumber);

        if (!$student) {
            return null;
        }

        $class = $student->classes->first(
            fn ($class) => $class->subjects->contains('name', $subjectName)
        );

        if (!$class) {
            return null;
        }

        $subject = $class->subjects->firstWhere('name', $subjectName);

        return Mark::updateOrCreate(
            [
                'student_id' => $student->id,
                'subject_id' => $subject->id,
                'exam_id'    => $this->examId,
            ],
            [
                'score'    => $row['obtained_marks'],
                'max_score'    => $row['max_score'] ?? 100,
                'class_id'       => $class->id,
            ]
        );
    }
}
