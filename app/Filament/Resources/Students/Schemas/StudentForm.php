<?php

namespace App\Filament\Resources\Students\Schemas;

use App\Models\ParentModel;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class StudentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('branch_id')
                    ->relationship(
                        name: 'branch',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query) => $query->whereIn('id', auth()->user()->branches->pluck('id'))
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label('Assign to Branch'),
                Select::make('parents')
                    ->relationship(
                        name: 'parents',
                        titleAttribute: 'full_name'
                    )
                    ->getOptionLabelFromRecordUsing(fn (ParentModel $record) => "{$record->full_name} — {$record->phone}")
                    ->searchable(['full_name', 'phone'])
                    ->multiple()
                    ->preload()
                    ->getSearchResultsUsing(function (string $search) {
                        return ParentModel::where('full_name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(fn ($parent) => [$parent->id => "{$parent->full_name} ({$parent->phone})"]);
                    })
                    ->createOptionForm([
                        TextInput::make('full_name')->required(),
                        TextInput::make('phone')->tel()->required(),
                        Textarea::make('address'),
                    ])
                    ->required(),
                TextInput::make('first_name')
                    ->required(),
                TextInput::make('last_name')
                    ->required(),
                TextInput::make('roll_number')
                    ->required(),
                DatePicker::make('date_of_birth'),
                Select::make('status')
                    ->options(['active' => 'Active', 'inactive' => 'Inactive'])
                    ->default('active')
                    ->required(),
            ]);
    }
}
