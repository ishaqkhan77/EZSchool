<?php

namespace App\Filament\Resources\Teachers\Tables;

use App\Models\Teacher;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TeachersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // Reaching through Employee to User for the name
                TextColumn::make('employee.user.name')
                    ->label('Teacher Name')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Teacher $record) => "ID: {$record->employee->employee_id}"),

                TextColumn::make('specialization')
                    ->label('Specialization')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('employee.designation')
                    ->label('Designation')
                    ->badge()
                    ->color('info'),

                // Using the HasOneThrough shortcut for the branch name
                TextColumn::make('branchData.name')
                    ->label('Campus/Branch')
                    ->badge()
                    ->color('success')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('employee.joining_date')
                    ->label('Joined')
                    ->date()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('specialization')
                    ->options([
                        'General' => 'General',
                        'Maths' => 'Mathematics',
                        'Science' => 'Science',
                        'English' => 'English',
                    ])
                    ->searchable(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
