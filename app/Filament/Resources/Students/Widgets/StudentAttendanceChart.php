<?php

namespace App\Filament\Resources\Students\Widgets;

use App\Models\AttendanceItem;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class StudentAttendanceChart extends ChartWidget
{
    protected ?string $heading = 'Monthly Attendance Trend';
    public ?Model $record = null;
    public ?string $academicYear = null; // Passed from parent page

    // This makes the chart reload when the filter changes
    protected ?string $pollingInterval = null;

    protected function getFilters(): ?array
    {
        if (!$this->academicYear) return [];

        // Split "2025-2026" into start and end years
        [$startYear, $endYear] = explode('-', $this->academicYear);

        // Define your school's standard months (e.g., Aug to June)
        $months = [
            "$startYear-08" => "August $startYear",
            "$startYear-09" => "September $startYear",
            "$startYear-10" => "October $startYear",
            "$startYear-11" => "November $startYear",
            "$startYear-12" => "December $startYear",
            "$endYear-01" => "January $endYear",
            "$endYear-02" => "February $endYear",
            "$endYear-03" => "March $endYear",
            "$endYear-04" => "April $endYear",
            "$endYear-05" => "May $endYear",
            "$endYear-06" => "June $endYear",
        ];

        return array_merge(['all' => 'Full Session View'], $months);
    }

    protected function getData(): array
    {
        if (!$this->record || !$this->academicYear) {
            return [
                'datasets' => [
                    ['label' => 'Present', 'data' => [], 'backgroundColor' => '#22c55e'],
                    ['label' => 'Absent', 'data' => [], 'backgroundColor' => '#ef4444'],
                    ['label' => 'Late', 'data' => [], 'backgroundColor' => '#eab308'],
                ],
                'labels' => [],
            ];
        }

        $activeFilter = $this->filter ?? 'all';
        $dataP = []; $dataA = []; $dataL = [];
        $labels = [];

        // Define the months we want to iterate over
        $monthsToQuery = [];

        if ($activeFilter === 'all' && $this->academicYear) {
            // Get all months in the dropdown keys (except 'all')
            $monthsToQuery = array_keys(array_filter($this->getFilters(), fn($k) => $k !== 'all', ARRAY_FILTER_USE_KEY));
        } else {
            $monthsToQuery = [$activeFilter];
        }

        foreach ($monthsToQuery as $monthString) {
            $date = Carbon::parse($monthString);
            $labels[] = $date->format('M Y');

            // Optimized Query: Filter by the month and the specific student

            $baseQuery = AttendanceItem::where('student_id', $this->record->id)
                ->whereHas('attendance', function ($q) use ($date) {
                    $q->whereBetween('date', [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()])
                        ->whereIn('class_id', function ($subQuery) {
                            $subQuery->select('class_id')
                                ->from('class_student')
                                ->where('student_id', $this->record->id)
                                ->where('academic_year', $this->academicYear);
                        });
                });

            $dataP[] = (clone $baseQuery)->where('status', 'p')->count();
            $dataA[] = (clone $baseQuery)->where('status', 'a')->count();
            $dataL[] = (clone $baseQuery)->where('status', 'l')->count();
        }

        return [
            'datasets' => [
                ['label' => 'Present', 'data' => $dataP, 'backgroundColor' => '#22c55e'],
                ['label' => 'Absent', 'data' => $dataA, 'backgroundColor' => '#ef4444'],
                ['label' => 'Late', 'data' => $dataL, 'backgroundColor' => '#eab308'],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'x' => ['stacked' => true],
                'y' => [
                    'stacked' => true,
                    'ticks' => ['stepSize' => 1, 'precision' => 0]
                ],
            ],
        ];
    }
}
