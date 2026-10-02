<?php

namespace App\Filament\Resources\ParentModels\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Laravel\Prompts\TextareaPrompt;

class ParentModelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Parent Information')
                    ->schema([
                        TextInput::make('full_name')->label('Name')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set, ?string $state) => $set('user.name', $state)),
                        TextInput::make('phone')
                            ->tel()
                            ->required(),
                        Textarea::make('address'),
                    ]),

                Section::make('Login Credentials')
                    ->description('An account will be created and login details emailed to the parent.')
                    ->relationship('user')
                    ->schema([
                        Hidden::make('name')
                            ->required(),

                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true),

                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->required(fn ($context) => $context === 'create')
                            ->default(fn () => str()->random(8))
                            ->visibleOn('create')
                            ->helperText('You can edit this or leave the generated password.'),
                    ]),
            ]);
    }
}
