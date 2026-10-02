<?php

namespace App\Filament\Resources\Classes\Tables;

use App\Filament\Resources\Classes\ClassResource;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ClassesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Class')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('section')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('subjects_count')
                    ->counts('subjects')
                    ->label('Subjects')
                    ->badge()
                    ->color('primary')
                    ->suffix(' Subjects'),
                TextColumn::make('students_count')
                    ->counts('students')
                    ->label('Enrolled')
                    ->badge()
                    ->color('info')
                    ->suffix(' Students'),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('branch')
                    ->relationship('branch', 'name'),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
