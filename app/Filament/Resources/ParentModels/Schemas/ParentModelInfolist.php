<?php

namespace App\Filament\Resources\ParentModels\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ParentModelInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Primary Details')
                    ->description('Key information about the parent/guardian.')
                    ->schema([
                        Grid::make(3)
                        ->schema([
                            TextEntry::make('full_name')
                                ->label('Name')
                                ->weight('bold')
                                ->color('primary'),
                            TextEntry::make('phone')
                                ->label('Phone Number')
                                ->icon('heroicon-m-phone')
                                ->copyable()
                                ->copyMessage('Phone number copied!'),
                            TextEntry::make('address')->
                                label('Address')
                                ->icon('heroicon-m-home'),
                        ]),

                        Grid::make(3)
                            ->schema([

                                TextEntry::make('created_at')
                                    ->label('Enrollment Date')
                                    ->date()
                                    ->placeholder('-'),
                                TextEntry::make('updated_at')
                                    ->label('Last Profile Update')
                                    ->date()
                                    ->placeholder('-'),
                                TextEntry::make('primary_relationship')
                                    ->label('Relation to Student')
                                    ->badge()
                                    ->color(fn ($state) => match ($state) {
                                        'father' => 'primary',
                                        'mother' => 'success',
                                        'guardian' => 'warning',
                                        default => 'secondary',
                                    })
                                    ->placeholder('None')
                            ]),

                    ])->collapsible(),

                Section::make('Children')
                    ->description('List of associated students')
                    ->schema([
                        RepeatableEntry::make('students')
                            ->label(false)
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        TextEntry::make('roll_number')
                                            ->label('Roll Number')
                                            ->badge()
                                            ->color('success'),
                                        TextEntry::make('full_name')
                                            ->label('Name')
                                            ->weight('bold')
                                            ->color('primary'),
                                    ]),
                            ])
                            ->columns(1)
                    ])->collapsible(),
            ]);
    }
}
