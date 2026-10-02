<?php

namespace App\Filament\Resources\Classes\RelationManagers;

use App\Filament\Resources\Students\StudentResource;
use Filament\Actions\AttachAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DetachAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class StudentsRelationManager extends RelationManager
{
    protected static string $relationship = 'students';
    protected static ?string $relatedResource = StudentResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('first_name')
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('roll_number')->label('Roll Number'),
                \Filament\Tables\Columns\TextColumn::make('first_name')->label('First Name'),
                \Filament\Tables\Columns\TextColumn::make('last_name')->label('Last Name'),
            ])
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->recordTitleAttribute('first_name')
                    ->recordSelectOptionsQuery(fn ($query) => $query->select(['students.id', 'first_name', 'last_name', 'roll_number']))
                    ->recordSelectSearchColumns(['first_name', 'last_name', 'roll_number'])
                    ->form(fn ($action): array => [
                        $action->getRecordSelect()
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name} (Roll: {$record->roll_number})")
                            ->getSearchResultsUsing(function (string $search) {
                                return \App\Models\Student::query()
                                    ->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%")
                                    ->orWhere('roll_number', 'like', "%{$search}%")
                                    ->limit(20)
                                    ->get()
                                    ->mapWithKeys(fn ($student) => [
                                        $student->id => "{$student->first_name} {$student->last_name} (Roll: {$student->roll_number})"
                                    ]);
                            })
                            ->getOptionLabelsUsing(function ($values): array {
                                return \App\Models\Student::whereIn('id', (array) $values)
                                    ->get()
                                    ->mapWithKeys(fn ($student) => [
                                        $student->id => "{$student->first_name} {$student->last_name} (Roll: {$student->roll_number})"
                                    ])
                                    ->toArray();
                            }),

                        \Filament\Forms\Components\Select::make('academic_year')
                            ->label('Academic Year')
                            ->options([
                                '2025-2026' => '2025-2026',
                                '2026-2027' => '2026-2027',
                                '2027-2028' => '2027-2028',
                            ])
                            ->default('2025-2026')
                            ->required(),
                    ])
                    ->action(function (array $data, $livewire): void {
                        $studentIds = (array) $data['recordId'];
                        $year = $data['academic_year'];

                        $pivotData = collect($studentIds)->mapWithKeys(fn ($id) => [
                            $id => ['academic_year' => $year]
                        ])->toArray();

                        $livewire->getOwnerRecord()->students()->syncWithoutDetaching($pivotData);
                    }),
                CreateAction::make()->label('Create New Student')
            ])
            ->actions([

                DetachAction::make()->label('Remove Student')
                    ->modalHeading('Remove Student from Class')
                    ->modalDescription('Are you sure you want to remove this student from the current class? This will not delete the student record, only their enrollment in this specific class.')
                    ->modalSubmitActionLabel('Yes, remove student')
                    ->color('danger'),
            ]);
    }
}
