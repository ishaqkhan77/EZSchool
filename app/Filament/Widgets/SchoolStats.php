<?php

namespace App\Filament\Widgets;

use App\Models\Employee;
use App\Models\Student;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SchoolStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $branchId = Filament::getTenant()?->getKey();

        if (!$branchId) {
            return [];
        }

        return [
            Stat::make('Total Students', Student::where('branch_id', $branchId)->count())
                ->description('All branch enrollments')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),

            Stat::make('Total Employees', Employee::where('branch_id', $branchId)->count())
                ->description('Staff & Faculty')
                ->descriptionIcon('heroicon-m-academic-cap'),

            Stat::make('Inactive Students', Student::where('branch_id', $branchId)->where('status', 'inactive')->count())
                ->description('Currently inactive')
                ->color('warning'),
        ];
    }
}
