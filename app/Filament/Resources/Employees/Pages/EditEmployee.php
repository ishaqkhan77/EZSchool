<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Resources\Employees\EmployeeResource;
use Illuminate\Database\Eloquent\Model;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $userData = $data['user'] ?? [];
        unset($data['user']);

        if ($record->user && $userData !== []) {
            $record->user->update($userData);
        }

        $record->update($data);

        return $record;
    }

    protected function afterSave(): void
    {
        $record = $this->record;
        $roles = $this->data['assigned_roles'] ?? [];

        if ($record->user) {
            $record->user->roles()->syncWithPivotValues(
                $roles,
                ['branch_id' => filament()->getTenant()->id]
            );
        }
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['assigned_roles'] = $this->record->user?->roles()
            ->pluck('id')
            ->toArray();
        $data['user'] = $this->record->user?->toArray() ?? [];
        return $data;
    }
}
