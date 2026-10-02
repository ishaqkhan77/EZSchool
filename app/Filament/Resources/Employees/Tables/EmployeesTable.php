<?php

namespace App\Filament\Resources\Employees\Tables;

use App\Models\Employee;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EmployeesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee_id')
                    ->label('Employee ID')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('user.name')
                    ->label('Employee Name')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Employee $record): string => $record->user->email),

                TextColumn::make('designation')
                    ->label('Designation')
                    ->searchable(),

                TextColumn::make('user.roles.name')
                ->label('Role')
                    ->badge()
                    ->searchable()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match (strtolower($state)) {
                        'teacher' => 'info',
                        'admin'   => 'danger',
                        default   => 'gray',
                    }),

                TextColumn::make('joining_date')
                    ->label('Joined')
                    ->date('M d, Y')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'teacher' => 'Teachers',
                        'staff' => 'Staff',
                        'admin' => 'Administrators',
                    ]),
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
