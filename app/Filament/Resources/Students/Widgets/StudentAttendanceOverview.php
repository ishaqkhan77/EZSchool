<?php

namespace App\Filament\Resources\Students\Widgets;

use App\Models\AttendanceItem;
use App\Models\Mark;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Illuminate\Support\Carbon;

class StudentAttendanceOverview extends StatsOverviewWidget
{
    public ?Model $record = null;
    public ?string $academicYear = null;

    protected function getStats(): array
    {
        if (!$this->record || !$this->academicYear) {
            return [];
        }

        [$startYear, $endYear] = explode('-', $this->academicYear);
        $yearStart = Carbon::create((int) $startYear, 8, 1)->startOfDay();
        $yearEnd = Carbon::create((int) $endYear, 6, 30)->endOfDay();

        $baseQuery = AttendanceItem::where('student_id', $this->record->id)
            ->whereHas('attendance', function ($q) use ($yearStart, $yearEnd) {
                $q->whereBetween('date', [$yearStart, $yearEnd])
                    ->whereIn('class_id', function ($subQuery) {
                    $subQuery->select('class_id')
                        ->from('class_student')
                        ->where('student_id', $this->record->id)
                        ->where('academic_year', $this->academicYear);
                });
            });

        $total = (clone $baseQuery)->count();
        $present = (clone $baseQuery)->where('status', 'p')->count();
        $late = (clone $baseQuery)->where('status', 'l')->count();
        $absent = (clone $baseQuery)->where('status', 'a')->count();

        $rate = $total > 0 ? round(($present / $total) * 100) : 0;

        $trendData = (clone $baseQuery)
            ->latest()
            ->take(10)
            ->get()
            ->map(fn($item) => match($item->status) {
                'p' => 10,
                'l' => 5,
                'a' => 0,
                default => 0
            })
            ->reverse()
            ->toArray();


        $studentAvg = Mark::where('student_id', $this->record->id)
                ->whereHas('exam', fn($q) => $q->where('academic_year', $this->academicYear))
                ->avg('score') ?? 0;

        // 2. Get all students in the SAME CLASS for that year
        $classId = $this->record->classes()
            ->wherePivot('academic_year', $this->academicYear)
            ->first()?->id;

        // 3. Rank calculation
        $allStudentsInClass = DB::table('class_student')
            ->where('class_id', $classId)
            ->where('academic_year', $this->academicYear)
            ->pluck('student_id');

        $rankings = Mark::whereIn('student_id', $allStudentsInClass)
            ->whereHas('exam', fn($q) => $q->where('academic_year', $this->academicYear))
            ->selectRaw('student_id, AVG(score) as average')
            ->groupBy('student_id')
            ->orderByDesc('average')
            ->get();

        $studentIndex = $rankings->search(fn($item) => $item->student_id == $this->record->id);
        $rank = $studentIndex === false ? null : $studentIndex + 1;
        $totalInClass = $allStudentsInClass->count();

        return [

            Stat::make("Attendance this year", "$rate%")
                ->value("$rate%")
                ->description($rate < 85 ? 'Below target' : 'Maintaining target')
                ->descriptionIcon($rate < 85 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-check-badge')
                ->chart($trendData)
                ->color($rate >= 90 ? 'success' : ($rate >= 75 ? 'warning' : 'danger')),

            Stat::make('Punctuality', $late)
                ->description('Times arrived late')
                ->descriptionIcon('heroicon-m-clock')
                ->color($late > 5 ? 'warning' : 'success'),

            Stat::make('Missed Days', $absent)
                ->description('Total unauthorized absences')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($absent > 3 ? 'danger' : 'gray'),

            Stat::make('Class Ranking', $rank === null ? 'N/A' : "{$rank} / {$totalInClass}")
                ->description('Based on average score this year')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color($rank !== null && $rank <= 3 ? 'success' : 'info'),

            Stat::make('Yearly GPA', round($studentAvg, 1) . '%')
                ->color('primary'),
        ];
    }
}
