<?php

namespace App\Filament\Resources\Classes;

use App\Filament\Resources\Classes\Pages\CreateClass;
use App\Filament\Resources\Classes\Pages\EditClass;
use App\Filament\Resources\Classes\Pages\ListClasses;
use App\Filament\Resources\Classes\Pages\ViewClass;
use App\Filament\Resources\Classes\Schemas\ClassForm;
use App\Filament\Resources\Classes\Schemas\ClassInfolist;
use App\Filament\Resources\Classes\Tables\ClassesTable;
use App\Models\Class;
use App\Models\Classes;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ClassResource extends Resource
{
    protected static ?string $model = Classes::class;

    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $recordTitleAttribute = 'Class';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if ($user instanceof User && $user->roles->contains('name', 'Teacher')) {
            $teacherId = $user->employee?->teacher?->id;

            return $teacherId
                ? $query->whereHas('teachers', fn (Builder $teachers) => $teachers->whereKey($teacherId))
                : $query->whereRaw('1 = 0');
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return ClassForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ClassInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClassesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SubjectsRelationManager::class,
            RelationManagers\StudentsRelationManager::class,
            RelationManagers\TeachersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClasses::route('/'),
            'create' => CreateClass::route('/create'),
            'view' => ViewClass::route('/{record}'),
            'edit' => EditClass::route('/{record}/edit'),
            'enter-marks' => Pages\EnterMarks::route('/{record}/enter-marks'),
        ];
    }
}
