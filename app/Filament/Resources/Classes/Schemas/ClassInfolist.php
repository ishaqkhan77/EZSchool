<?php

namespace App\Filament\Resources\Classes\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ClassInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextEntry::make('name')
                    ->label('Class')
                    ->weight('bold'),
                TextEntry::make('section')
                    ->badge()
                    ->color('gray'),
                TextEntry::make('branch.name')
                    ->label('Campus')
                    ->icon('heroicon-m-building-office')
                    ->color('primary'),
                TextEntry::make('students_count')
                    ->counts('students')
                    ->label('Enrolled')
                    ->badge()
                    ->color('info')
                    ->suffix(' Students'),
            ]);

    }
}
