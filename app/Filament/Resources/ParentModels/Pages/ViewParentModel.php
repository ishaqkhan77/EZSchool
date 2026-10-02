<?php

namespace App\Filament\Resources\ParentModels\Pages;

use App\Filament\Resources\ParentModels\ParentModelResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewParentModel extends ViewRecord
{
    protected static string $resource = ParentModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('Manage Profile & Students'),
        ];
    }


    public function getTitle(): string
    {
        return "Parent Details";
    }
}
