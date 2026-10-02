<?php

namespace App\Filament\Resources\Classes\Schemas;

use App\Models\Exam;
use App\Models\Subject;
use Filament\Facades\Filament;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ClassForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Class Details')
                    ->description('Define the grade and section for this campus.')
                    ->schema([
                        Hidden::make('branch_id')
                            ->default(fn () => Filament::getTenant()?->id),
                        TextInput::make('name')
                            ->label('Class Name')
                            ->placeholder('e.g. Grade 10')
                            ->required(),
                        TextInput::make('section')
                            ->label('Section/Room')
                            ->placeholder('e.g. Section A'),
                    ])->columns(2)
            ->columnSpanFull()
            ]);
    }

    public static function getMarksFormSchema(Schema $schema, $livewire): Schema
    {
        return $schema
            ->schema([
                Section::make('Filters')
                    ->description('Select an exam and subject to manage student marks.')
                    ->schema([
                        Select::make('exam_id')
                            ->options(Exam::where('is_active', true)->pluck('name', 'id'))
                            ->label('Select Exam')
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn ($state) => $livewire->exam_id = $state),

                        Select::make('subject_id')
                            ->options(fn () =>
                            Subject::whereHas('classes', fn($q) => $q->where('classes.id', $livewire->record->id))
                                ->pluck('name', 'id')
                            )
                            ->label('Select Subject')
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn ($state) => $livewire->subject_id = $state),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

//    public static function getMarksFormSchema(Schema $schema, $livewire): Schema
//{
//    return $schema
//        ->schema([
//            Section::make('Exam & Subject')
//                ->schema([
//                    Select::make('exam_id')
//                        ->options(Exam::where('is_active', true)->pluck('name', 'id'))
//                        ->required()
//                        ->label('Select Exam')
//                        ->live(),
//                    Select::make('subject_id')
//                        ->options(fn () =>
//                        Subject::whereHas('classes', fn($q) => $q->where('classes.id', $livewire->record->id))
//                            ->pluck('name', 'id')
//                        )
//                        ->label('Select Subject')
//                        ->required()
//                        ->live()
//                        ->afterStateUpdated(fn () => $livewire->loadStudents()),
//                ])->columns(2),
//
//            Section::make('Marks Entry Grid')
//                ->description('Enter student scores below. Data is saved when you submit the form.')
//                ->schema([
//                    Repeater::make('marks_grid')
//                        ->schema([
//                            // Student Name (Static)
//                            TextInput::make('student_name')
//                                ->label('Student Name')
//                                ->disabled()
//                                ->dehydrated(false)
//                                ->columnSpan(2),
//
//                            Hidden::make('student_id')
//                                ->dehydrated()
//                                ->required(),
//
//                            // Obtained Score
//                            TextInput::make('score')
//                                ->label('Obtained Marks')
//                                ->numeric()
//                                ->minValue(0)
//                                ->placeholder('0')
//                                ->required()
//                                ->columnSpan(1),
//
//                            // Max Marks (Prefilled)
//                            TextInput::make('max_score')
//                                ->label('Max Marks')
//                                ->numeric()
//                                ->default(100)
//                                ->required()
//                                ->columnSpan(1),
//                        ])
//                        ->addable(false)
//                        ->deletable(false)
//                        ->reorderable(false)
//                        ->grid(1) // Keep it as a list for readability
//                        ->columns(4) // This creates a nice horizontal row
//                        ->itemLabel(fn (array $state): ?string => $state['student_name'] ?? null),
//                ])
//                ->visible(fn ($get) => filled($get('subject_id'))),
//        ])
//        ->statePath('data');
//}
}
