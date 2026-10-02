<?php

namespace App\Filament\Resources\ParentModels;

use App\Filament\Resources\ParentModels\Pages\CreateParentModel;
use App\Filament\Resources\ParentModels\Pages\EditParentModel;
use App\Filament\Resources\ParentModels\Pages\ListParentModels;
use App\Filament\Resources\ParentModels\Pages\ViewParentModel;
use App\Filament\Resources\ParentModels\Schemas\ParentModelForm;
use App\Filament\Resources\ParentModels\Schemas\ParentModelInfolist;
use App\Filament\Resources\ParentModels\Tables\ParentModelsTable;
use App\Models\ParentModel;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Namu\WireChat\Models\Conversation;
use Wirechat\Wirechat\Livewire\Chat\Chat;


class ParentModelResource extends Resource
{
    protected static ?string $model = ParentModel::class;
    protected static ?string $navigationLabel = 'Parents';
    protected static ?string $breadcrumb = 'Parents';
    protected static ?string $recordTitleAttribute = 'full_name';

    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Schema $schema): Schema
    {
        return ParentModelForm::configure($schema);
    }


    public function startChatWithTeacher($teacherUserId)
    {
        $conversation = auth()->user()->createConversationWith($teacherUserId);

        return redirect()->to(Chat::getUrl(['conversation' => $conversation->id]));
    }

    public static function infolist(Schema $schema): Schema
    {
        return ParentModelInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ParentModelsTable::configure($table);
    }

    public static function getRelations(): array
    {
        $currentPage = request()->route()?->getAction('controller');
        if (str_contains($currentPage, 'ViewParentModel')) {
            return [];
        }
        return [
            RelationManagers\StudentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListParentModels::route('/'),
            'create' => CreateParentModel::route('/create'),
            'view' => ViewParentModel::route('/{record}'),
            'edit' => EditParentModel::route('/{record}/edit'),
        ];
    }
}
