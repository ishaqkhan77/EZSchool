<?php

namespace App\Exports;

use App\Models\Student;
use App\Models\Subject;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class MarksTemplateExport implements FromCollection, WithHeadings, WithStyles, WithEvents
{
    /**
    * @return \Illuminate\Support\Collection
    */

    public function collection()
    {
        return collect();
    }

    public function headings(): array
    {
        return [
            ['INSTRUCTIONS:'],
            ['1. Make sure to fill in the Roll Number of students exactly as they appear in the system.'],
            ['2. Select the Subject from the dropdown or type exactly as listed in the system.'],
            ['3. Obtained Marks must be a number between 0 and Max Score.'],
            [''],
            ['Roll Number', 'Student Name', 'Subject', 'Obtained Marks', 'Max Score']
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FF0000']]],
            6 => ['font' => ['bold' => true]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // fix: get the subject of the current school
                $subjects = Subject::pluck('name')->toArray();
                $subjectList = '"' . implode(',', $subjects) . '"';

                $validation = $event->sheet->getDelegate()->getCell('C7')->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(false);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1($subjectList);

                for ($i = 7; $i <= 300; $i++) {
                    $event->sheet->getDelegate()->getCell("C$i")->setDataValidation(clone $validation);
                }
            },
        ];
    }
}
