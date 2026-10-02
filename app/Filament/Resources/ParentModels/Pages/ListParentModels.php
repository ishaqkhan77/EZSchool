<?php

namespace App\Filament\Resources\ParentModels\Pages;

use App\Filament\Resources\ParentModels\ParentModelResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListParentModels extends ListRecords
{
    protected static string $resource = ParentModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label("Add New Parent"),
        ];
    }

    public function getTitle(): string
    {
        return "Parents";
    }
}
