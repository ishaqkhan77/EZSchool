<?php

namespace App\Filament\Resources\Students\Widgets;

use App\Models\Mark;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

class StudentSubjectLeaderboard extends StatsOverviewWidget
{
    protected string $view = 'filament.resources.students.widgets.student-subject-leaderboard';

    public ?Model $record = null;
    public ?string $academicYear = null;

    protected function getViewData(): array
    {

        $subjectData = Mark::where('student_id', $this->record->id)
            ->whereHas('exam', fn($q) => $q->where('academic_year', $this->academicYear))
            ->with('subject')
            ->get()
            ->groupBy('subject_id')
            ->map(fn($marks) => [
                'name' => $marks->first()->subject->name,
                'avg' => round($marks->avg('score')),
            ])
            ->sortByDesc('avg');


        return [
            'topSubjects' => $subjectData->take(3),
            'bottomSubjects' => $subjectData->reverse()->take(3),
        ];
    }
}
