<?php

namespace App\Filament\Resources\Exams\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ExamForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Exam Configuration')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->placeholder('e.g., Spring Mid-Terms'),
                        Select::make('academic_year')
                            ->options([
                                '2025-2026' => '2025-2026',
                                '2026-2027' => '2026-2027',
                            ])
                            ->required(),
                        Toggle::make('is_active')
                            ->label('Accepting Mark Entries')
                            ->default(true),
                    ])->columns(1),
            ]);
    }
}
