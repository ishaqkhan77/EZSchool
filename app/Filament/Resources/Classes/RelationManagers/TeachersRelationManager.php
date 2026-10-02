<?php

namespace App\Filament\Resources\Classes\RelationManagers;

use App\Filament\Resources\Teachers\TeacherResource;
use Filament\Actions\AttachAction;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TeachersRelationManager extends RelationManager
{
    protected static string $relationship = 'teachers';
    protected static ?string $relatedResource = TeacherResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('employee.user.name')
            ->columns([
                TextColumn::make('employee.user.name')
                    ->label('Teacher Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('employee.employee_id')
                    ->label('ID'),
                IconColumn::make('pivot.is_main_teacher')
                    ->label('Main Teacher')
                    ->boolean(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->form(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Select::make('academic_year')
                            ->options([
                                '2025-2026' => '2025-2026',
                                '2026-2027' => '2026-2027',
                            ])->required(),
                        Toggle::make('is_main_teacher')
                            ->label('Primary Class Teacher'),
                    ]),
            ]);
    }
}
