<?php

namespace App\Filament\Resources\Employees\Schemas;

use App\Models\Employee;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class EmployeeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
            Section::make('Personal Information')
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('user.name')
                        ->label('Full Name')
                        ->weight('bold')
                        ->icon('heroicon-m-user')
                        ->size('md'),

                    TextEntry::make('user.email')
                        ->label('Email Address')
                        ->icon('heroicon-m-envelope')
                        ->copyable(),

                    TextEntry::make('designation')
                        ->label('Current Designation'),

                    TextEntry::make('user.roles.name')
                        ->label('Role')
                        ->badge()
                        ->formatStateUsing(fn (string $state): string => ucfirst($state))
                        ->color(fn (string $state): string => match (strtolower($state)) {
                            'teacher' => 'info',
                            'admin' => 'danger',
                            'staff' => 'warning',
                            default => 'gray',
                        }),

                    TextEntry::make('employee_id')
                        ->label('Employee ID')
                        ->badge()
                        ->color('gray'),
                ])->columns(2),

            Section::make('Employment Details')
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('joining_date')
                        ->date('F d, Y'),

                    TextEntry::make('salary')
                        ->money('PKR'),

                    TextEntry::make('created_at')
                        ->label('Account Created')
                        ->dateTime(),
                ])->columns(3),
            ]);
    }
}
