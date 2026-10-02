<?php

namespace App\Filament\Resources\Teachers\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TeacherForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Academic Details')
                    ->schema([
                        TextInput::make('specialization')
                            ->placeholder('e.g. Mathematics, Physics')
                            ->required(),
                        Textarea::make('bio')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);

    }
}
