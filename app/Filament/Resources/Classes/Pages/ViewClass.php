<?php

namespace App\Filament\Resources\Classes\Pages;

use App\Filament\Resources\Classes\ClassResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewClass extends ViewRecord
{
    protected static string $resource = ClassResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('enter-marks')
                ->label('Manage Marks')
                ->color('success')
                ->url(fn () => $this->getResource()::getUrl('enter-marks', ['record' => $this->record])),
        ];
    }
}
