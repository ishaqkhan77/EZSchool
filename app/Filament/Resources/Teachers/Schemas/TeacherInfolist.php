<?php

namespace App\Filament\Resources\Teachers\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TeacherInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Professional Profile')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('employee.user.name')
                                    ->label('Teacher Name')
                                    ->weight('bold')
                                    ->size('lg'),

                                TextEntry::make('specialization')
                                    ->badge()
                                    ->color('info'),

                                TextEntry::make('employee.user.email')
                                    ->label('Email')
                                    ->icon('heroicon-m-envelope')
                                    ->copyable(),

                                TextEntry::make('employee.employee_id')
                                    ->label('Employee ID')
                                    ->badge()
                                    ->color('gray'),
                            ]),
                    ]),

                // Academic/Employment Details
                Section::make('Employment Details')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('employee.designation')
                                    ->label('Job Title'),

                                TextEntry::make('employee.joining_date')
                                    ->label('Joining Date')
                                    ->date(),

                                TextEntry::make('branchData.name')
                                    ->label('Assigned Branch')
                                    ->badge()
                                    ->color('success'),
                            ]),
                    ]),

                Section::make('System Info')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Registered On')
                            ->dateTime()
                            ->size('sm'),

                        TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime()
                            ->size('sm'),
                    ]),
            ]);
    }
}
