<?php
namespace App\Filament\Resources\Students\Widgets;

use App\Models\Mark;
use App\Models\Exam;
use Filament\Widgets\ChartWidget;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Model;

class StudentAcademicTrend extends ChartWidget
{
    protected ?string $heading = 'Subject Proficiency Analysis';
    public ?string $academicYear = null;

    public ?Model $record = null;

    protected function getFilters(): ?array
    {
        return Exam::where('is_active', true)
            ->where('academic_year', $this->academicYear)
            ->pluck('name', 'id')->toArray();
    }

    protected function getData(): array
    {
        $query = Mark::where('student_id', $this->record->id)
            ->with('subject');

        $activeExamId = $this->filter;
        if (!$activeExamId) {
            $activeExamId = Mark::where('student_id', $this->record->id)
                ->whereHas('exam', fn($q) => $q->where('academic_year', $this->academicYear))
                ->latest()
                ->value('exam_id');
        }

        $query->where('exam_id', $activeExamId);
        $marks = $query->get()->unique('subject_id');

        return [
            'datasets' => [
                [
                    'label' => $activeExamId ? 'Exam Performance' : 'Current Proficiency (Latest)',
                    'data' => $marks->pluck('score')->toArray(),
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'borderColor' => 'rgb(54, 162, 235)',
                    'fill' => true,
                ],
            ],
            'labels' => $marks->pluck('subject.name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'radar';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'r' => [
                    'angleLines' => [
                        'display' => true,
                        'color' => 'rgba(200, 200, 200, 0.2)',
                    ],
                    'ticks' => [
                        'display' => false,
                    ],
                    'suggestedMin' => 0,
                    'suggestedMax' => 100,
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}
