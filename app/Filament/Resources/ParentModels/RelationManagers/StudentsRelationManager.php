<?php


namespace App\Filament\Resources\ParentModels\RelationManagers;

use App\Filament\Resources\Students\StudentResource;
use Filament\Actions\AttachAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DetachAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Tables;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;



class StudentsRelationManager extends RelationManager

{
    protected static string $relationship = 'students';
    protected static ?string $relatedResource = StudentResource::class;

    public function form(Schema $schema): Schema

    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('first_name')
                    ->required(),
                Forms\Components\TextInput::make('last_name')
                    ->required(),
                Forms\Components\TextInput::make('roll_number')
                    ->required(),
            ]);

    }



    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->recordTitleAttribute('first_name')
            ->columns([
                Tables\Columns\TextColumn::make('roll_number')
                    ->label('Roll Number')
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Name')
                    ->weight('bold')
                    ->color('primary'),
            ])
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->recordTitleAttribute('first_name')
                    ->recordSelectOptionsQuery(fn ($query) => $query->select(['students.id', 'first_name', 'last_name', 'roll_number']))
                    ->recordSelectSearchColumns(['first_name', 'last_name', 'roll_number'])

                    ->form(fn ($action): array => [
                        $action->getRecordSelect()
                            ->searchable()
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name} (Roll: {$record->roll_number})")

                            ->getSearchResultsUsing(function (string $search) {
                                return \App\Models\Student::query()
                                    ->select(['id', 'first_name', 'last_name', 'roll_number']) // Only fetch what we need
                                    ->where(function ($query) use ($search) {
                                        $query->where('first_name', 'like', "%{$search}%")
                                            ->orWhere('last_name', 'like', "%{$search}%")
                                            ->orWhere('roll_number', 'like', "%{$search}%");
                                    })
                                    ->limit(20) // Reduced from 50 to 20 for faster UI rendering
                                    ->get()
                                    ->mapWithKeys(fn ($student) => [
                                        $student->id => "{$student->first_name} {$student->last_name} (Roll: {$student->roll_number})"
                                    ])
                                    ->toArray();
                            })

                            ->getOptionLabelsUsing(function ($values): array|string {
                                if (is_array($values)) {
                                    return \App\Models\Student::whereIn('id', $values)
                                        ->select(['id', 'first_name', 'last_name', 'roll_number'])
                                        ->get()
                                        ->mapWithKeys(fn ($student) => [
                                            $student->id => "{$student->first_name} {$student->last_name} (Roll: {$student->roll_number})"
                                        ])
                                        ->toArray();
                                }

                                $student = \App\Models\Student::select(['id', 'first_name', 'last_name', 'roll_number'])->find($values);
                                return $student ? "{$student->first_name} {$student->last_name} (Roll: {$student->roll_number})" : (string) $values;
                            }),

                        Select::make('relationship_type')
                            ->label('Role')
                            ->options([
                                'father' => 'Father',
                                'mother' => 'Mother',
                                'guardian' => 'Guardian',
                            ])
                            ->required(),
                    ]),
                CreateAction::make()
            ])
            ->actions([
                DetachAction::make(),
                EditAction::make(),
            ]);
    }
}


