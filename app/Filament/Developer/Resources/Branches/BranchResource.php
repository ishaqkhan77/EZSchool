<?php

namespace App\Filament\Developer\Resources\Branches;

use App\Filament\Developer\Resources\Branches\Pages\CreateBranch;
use App\Filament\Developer\Resources\Branches\Pages\EditBranch;
use App\Filament\Developer\Resources\Branches\Pages\ListBranches;
use App\Filament\Developer\Resources\Branches\Pages\ViewBranch;
use App\Filament\Developer\Resources\Branches\Schemas\BranchForm;
use App\Filament\Developer\Resources\Branches\Schemas\BranchInfolist;
use App\Filament\Developer\Resources\Branches\Tables\BranchesTable;
use App\Models\Branch;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BranchResource extends Resource
{
    protected static ?string $model = Branch::class;

    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $recordTitleAttribute = 'Branch';

    public static function form(Schema $schema): Schema
    {
        return BranchForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BranchInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BranchesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBranches::route('/'),
            'create' => CreateBranch::route('/create'),
            'view' => ViewBranch::route('/{record}'),
            'edit' => EditBranch::route('/{record}/edit'),
        ];
    }
}
