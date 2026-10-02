<?php

namespace App\Filament\Resources\Teachers\RelationManagers;

use App\Filament\Resources\Classes\ClassResource;
use App\Models\Classes;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ClassesRelationManager extends RelationManager
{
    protected static string $relationship = 'classes';

    protected static ?string $relatedResource = ClassResource::class;


    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('class_id')
                    ->relationship('classes', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),

                TextInput::make('academic_year')
                    ->placeholder('2025-2026')
                    ->required(),

                Toggle::make('is_main_teacher')
                    ->label('Is Class Teacher?')
                    ->default(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Class Name')
                    ->searchable(),

                TextColumn::make('academic_year')
                    ->badge()
                    ->color('gray'),

                IconColumn::make('is_main_teacher')
                    ->label('Main Teacher')
                    ->boolean(),
            ])
            ->filters([
                // Filter by Academic Year
            ])
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->recordTitleAttribute('name')
                    ->recordSelectOptionsQuery(fn ($query) => $query->select(['classes.id','name', 'section']))
                    ->recordSelectSearchColumns(['name', 'section'])

                    ->form(fn (AttachAction $action): array => [
                        $action->getRecordSelect()
                        ->searchable()
                        ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->name} {$record->section}")
                            ->getSearchResultsUsing(function (string $search) {
                                return Classes::query()
                                    ->select(['id', 'name', 'section'])
                                    ->where(function ($query) use ($search) {
                                        $query->where('name', 'like', "%{$search}%")
                                            ->orWhere('section', 'like', "%{$search}%");
                                    })
                                    ->limit(20)
                                    ->get()
                                    ->mapWithKeys(fn ($class) => [
                                        $class->id => "{$class->name} {$class->section}"
                                    ])
                                    ->toArray();
                            })

                            ->getOptionLabelsUsing(function ($values): array|string {
                                if (is_array($values)) {
                                    return Classes::whereIn('id', $values)
                                        ->select(['id', 'name', 'section'])
                                        ->get()
                                        ->mapWithKeys(fn ($class) => [
                                            $class->id => "{$class->name} {$class->section}"
                                        ])
                                        ->toArray();
                                }

                                $class = Classes::select(['id', 'name', 'section'])->find($values);
                                return $class ? "{$class->name} {$class->section}" : (string) $values;
                            }),

                        Select::make('academic_year')
                            ->label('Academic Year')
                            ->options([
                                '2025-2026' => '2025-2026',
                                '2026-2027' => '2026-2027',
                                '2027-2028' => '2027-2028',
                            ])
                            ->default('2025-2026')
                            ->required(),
                        Toggle::make('is_main_teacher'),
                    ]),
            ])
            ->actions([
                EditAction::make(),
                DetachAction::make(),
            ]);
    }
}
