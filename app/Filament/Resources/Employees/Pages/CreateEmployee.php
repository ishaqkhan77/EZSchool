<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Resources\Employees\EmployeeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function handleRecordCreation(array $data) : \Illuminate\Database\Eloquent\Model
    {
        $userData = $data['user'];
        unset($data['user']);

        $user = \App\Models\User::create($userData);

        $data['user_id'] = $user->id;
        return static::getModel()::create($data);
    }

    protected function afterCreate(): void
    {
        $record = $this->record;
        $roles = $this->data['assigned_roles'] ?? [];

        if ($record->user) {
            $record->user->branches()->syncWithoutDetaching([$record->branch_id]);

            if (empty($roles)) {
                return;
            }

            $record->user->roles()->syncWithPivotValues(
                $roles,
                ['branch_id' => $record->branch_id]
            );
        }
    }
}
