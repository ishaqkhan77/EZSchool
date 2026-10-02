<?php

namespace App\Filament\Resources\Students\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StudentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Student Information')
                    ->description('Primary identification and academic details.')
                    ->schema([
                        Grid::make(3) // Split into 3 columns
                        ->schema([
                            TextEntry::make('first_name')
                                ->label('First Name')
                                ->weight('bold')
                                ->color('primary'),
                            TextEntry::make('last_name')
                                ->label('Last Name')
                                ->weight('bold')
                                ->color('primary'),
                            TextEntry::make('roll_number')
                                ->label('Roll Number')
                                ->badge() // Makes it look like a tag
                                ->color('success'),
                        ]),

                        Grid::make(3)
                            ->schema([
                                TextEntry::make('branch.name')
                                    ->label('Branch')
                                    ->icon('heroicon-m-building-office'),
                                TextEntry::make('created_at')
                                    ->label('Enrollment Date')
                                    ->date()
                                    ->placeholder('-'),
                                TextEntry::make('updated_at')
                                    ->label('Last Profile Update')
                                    ->date()
                                    ->placeholder('-'),
                            ]),
                    ])->collapsible(),

                Section::make('Parent & Guardian Details')
                    ->description('Linked family members and emergency contacts.')
                    ->schema([
                        RepeatableEntry::make('parents')
                            ->label(false)
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        TextEntry::make('full_name')
                                        ->label('Parent Name')
                                            ->weight('bold'),
                                        TextEntry::make('phone')
                                        ->label('Phone Number')
                                            ->icon('heroicon-m-phone')
                                            ->copyable()
                                            ->copyMessage('Phone number copied!'),

                                        TextEntry::make('pivot.relationship_type')
                                            ->label('Relationship')
                                            ->badge()
                                            ->color(fn ($state) => match ($state) {
                                                'father' => 'primary',
                                                'mother' => 'success',
                                                'guardian' => 'warning',
                                                default => 'secondary',
                                            })
                                    ]),
                            ])
                            ->columns(1)
                    ])->collapsible(),

                Section::make('Academic Performance')
                    ->description('Summary of recent grades and attendance.')
                    ->schema([
                        TextEntry::make('latest_grade')
                            ->label('Latest Grade')
                            ->badge()
                            ->color('info'),
                        TextEntry::make('attendance_percentage')
                            ->label('Attendance %')
                            ->suffix('%')
                            ->badge()
                            ->color(fn ($state) => $state >= 90 ? 'success' : ($state >= 75 ? 'warning' : 'danger')),
                    ])->collapsible(),
            ]);
    }
}
