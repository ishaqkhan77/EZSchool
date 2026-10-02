<?php

namespace App\Filament\Resources\Employees\Schemas;

use App\Models\Role;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {

        return $schema
            ->schema([
                Section::make('Account Information')
                    ->description('Create the login credentials for this employee.')
                    ->schema([
                        TextInput::make('user.name')
                            ->required(),
                        TextInput::make('user.email')
                            ->email()
                            ->required()
                            ->unique(
                                table: 'users',
                                column: 'email',
                                ignorable: fn ($record) => $record?->user
                            ),
                        TextInput::make('user.password')
                            ->password()
                            ->required()
                            ->visibleOn('create')
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state)),

                        Hidden::make('user.branch_id')
                            ->default(fn () => Filament::getTenant()->id),
                    ])->columns(3),

                Section::make('Employment Details')
                    ->schema([
                        TextInput::make('employee_id')
                            ->label('Staff ID')
                            ->default('EMP-' . now()->year . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT))
                            ->unique(ignoreRecord: true)
                            ->required(),
                        TextInput::make('designation')
                            ->placeholder('e.g. Senior Accountant')
                            ->required(),
//                        Select::make('type')
//                            ->options([
//                                'teacher' => 'Teacher',
//                                'staff' => 'General Staff',
//                                'admin' => 'Administrator',
//                            ])
//                            ->required()
//                            ->native(false),
                        TextInput::make('salary')
                            ->numeric()
                            ->prefix('PKR')
                            ->required(),
                        DatePicker::make('joining_date')
                            ->default(now())
                            ->required(),
                    ])->columns(2),

                Section::make('Assign Roles')
                    ->description('Assign one or more roles to this employee.')
                    ->schema([
                        Select::make('assigned_roles')
                        ->label('Roles')
                            ->multiple()
                            ->options(fn () => Role::where('branch_id', filament()->getTenant()->id)->pluck('name', 'id'))
                            ->preload()
                            ->searchable()
                    ])->columns(1),
            ]);
    }
}
