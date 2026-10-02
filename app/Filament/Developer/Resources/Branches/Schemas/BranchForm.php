<?php

namespace App\Filament\Developer\Resources\Branches\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BranchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('school_id')
                    ->relationship('school', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('location'),
                TextInput::make('phone')
                    ->tel(),

                Section::make('Assign Principal')
                    ->description('The user who will manage this school branch.')
                    ->schema([
                        Select::make('users')
                            ->relationship('members', 'name')
                            ->multiple()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')->required(),
                                TextInput::make('email')->email()->required()->unique('users', 'email'),
                                TextInput::make('password')
                                    ->password()
                                    ->required()
                            ])
                    ]),
            ]);
    }
}
