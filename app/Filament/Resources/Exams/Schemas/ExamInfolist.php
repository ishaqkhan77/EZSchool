<?php

namespace App\Filament\Resources\Exams\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ExamInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('branch.name')
                    ->label('Branch'),
                TextEntry::make('name'),
                TextEntry::make('academic_year'),
                IconEntry::make('is_active')
                    ->boolean(),
            ]);
    }
}
