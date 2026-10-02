<?php

namespace App\Filament\Resources\ParentModels\Pages;

use App\Filament\Resources\ParentModels\ParentModelResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditParentModel extends EditRecord
{
    protected static string $resource = ParentModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->label('Delete Parent')->icon('heroicon-o-trash')
        ];
    }

    public function getTitle(): string
    {
        return "Edit Parent Details";
    }
}
