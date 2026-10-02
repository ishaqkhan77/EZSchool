<?php

namespace App\Filament\Parent\Pages;

use App\Models\ParentModel;
use Filament\Pages\Page;
use App\Models\Student;
use Filament\Navigation\NavigationItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class Dashboard extends Page
{
    protected string $view = 'filament.parent.pages.dashboard';
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $title = 'Dashboard';

    public ?Student $record = null;

    public function mount(): void
    {

        $parentRecord = ParentModel::where('user_id', Auth::id())
            ->where('branch_id', filament()->getTenant()->getKey())
            ->first();

        if (!$parentRecord) {
            abort(403, 'Parent record not found for this user.');
        }
        $students = $parentRecord->students;

        $studentId = request()->query('student_id') ?? $students->first()?->id;

        if ($studentId) {
            $this->record = $students->firstWhere('id', $studentId);

            if (!$this->record) {
                abort(403, 'Unauthorized access to student data.');
            }
        }
    }

    /**
     * This replaces your Resource Sub-Navigation.
     * It allows parents to switch between children or academic years.
     */
    public function getSubNavigation(): array
    {
        $navItems = [
            NavigationItem::make('Messages')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->url(route('wirechat.chats.chats')),
        ];

        if (!$this->record) return $navItems;

        $parent = ParentModel::where('user_id', Auth::id())
            ->where('branch_id', filament()->getTenant()->getKey())
            ->first();
        $allChildren = $parent?->students ?? collect();

        if ($allChildren->count() > 1) {
            foreach ($allChildren as $child) {
                $navItems[] = NavigationItem::make($child->full_name)
                    ->icon('heroicon-o-user')
                    ->group('Switch Student')
                    ->url(static::getUrl([
                        'tenant' => filament()->getTenant(),
                        'student_id' => $child->id,
                    ]))
                    ->isActiveWhen(fn () => $this->record->id === $child->id);
            }
        }

        $academicData = DB::table('class_student')
            ->select('academic_year','classes.name as class_name','classes.section')
            ->join('classes', 'class_student.class_id', '=', 'classes.id')
            ->where('student_id', $this->record->id)
            ->orderBy('academic_year')
            ->get()
            ->unique('academic_year');


        $defaultYear = $academicData->first()?->academic_year;

        foreach ($academicData as $label) {
            $navItems[] = NavigationItem::make($label->class_name . ' - ' . $label->section . ' (' . $label->academic_year . ')')
                ->icon('heroicon-o-academic-cap')
                ->group('Academic History')
                ->url(static::getUrl([
                    'student_id' => $this->record->id,
                    'year' => $label->academic_year,
                ]))
                ->isActiveWhen(function () use ($label, $defaultYear) {
                    $queryYear = request()->query('year');

                    return $queryYear === $label->academic_year || (empty($queryYear) && $label->academic_year === $defaultYear);
                });
        }

        return $navItems;
    }

    protected function getHeaderWidgets(): array
    {
        if (!$this->record) return [];

        $academicYear = request()->query('year')
            ?? DB::table('class_student')
                ->where('student_id', $this->record->id)
                ->orderBy('academic_year')
                ->value('academic_year');

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
