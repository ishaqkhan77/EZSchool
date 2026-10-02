<?php

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Support\Facades\DB;

class StudentStats extends Page
{
    use InteractsWithRecord;

    protected static string $resource = StudentResource::class;
    protected string $view = 'filament.resources.students.pages.student-stats';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getSubNavigation(): array
    {
        $data = DB::table('class_student')
            ->select('academic_year','classes.name as class_name','classes.section')
            ->join('classes', 'class_student.class_id', '=', 'classes.id')
            ->where('student_id', $this->record->id)->get()
            ->unique('academic_year');


        $defaultYear = $data->first()?->academic_year;

        return $data->map(function ($label) use ($defaultYear) {

            return NavigationItem::make($label->class_name . ' - ' . $label->section . ' (' . $label->academic_year . ')')
                ->icon('heroicon-o-academic-cap')
                ->group('Academic History')
                ->url(static::getResource()::getUrl('stats', [
                    'record' => $this->record,
                    'year' => $label->academic_year,
                ]))
                ->isActiveWhen(function () use ($label, $defaultYear) {
                    $queryYear = request()->query('year');
                    return $queryYear === $label->academic_year || (empty($queryYear) && $label->academic_year === $defaultYear);
                });
        })->toArray();
    }

    public function getTabs(): array
    {
        $years = $this->record->classes()
            ->pluck('academic_year')
            ->unique()
            ->toArray();

        $tabs = [];
        foreach ($years as $year) {
            $tabs[$year] = Tab::make($year)
                ->query(fn ($query) => $query->where('academic_year', $year));
        }

        return $tabs;
    }

    public function getTitle(): string
    {
        return "{$this->record->first_name} {$this->record->last_name}";
    }

    protected function getHeaderWidgets(): array
    {
        $academicYear = request()->query('year')
            ?? $this->record->classes()->first()?->pivot?->academic_year;

        return [
            \App\Filament\Resources\Students\Widgets\StudentAttendanceOverview::make([
                'record' => $this->record,
                'academicYear' => $academicYear,
            ]),
            \App\Filament\Resources\Students\Widgets\StudentAcademicTrend::make([
                'record' => $this->record,
                'academicYear' => $academicYear,
            ]),
            \App\Filament\Resources\Students\Widgets\StudentAttendanceChart::make([
                'record' => $this->record,
                'academicYear' => $academicYear,
            ]),
            \App\Filament\Resources\Students\Widgets\StudentSubjectLeaderboard::make([
                'record' => $this->record,
                'academicYear' => $academicYear,
            ]),
        ];
    }
}
