<?php

namespace App\Filament\Developer\Resources\Branches\Pages;

use App\Filament\Developer\Resources\Branches\BranchResource;
use App\Models\Role;
use Filament\Resources\Pages\CreateRecord;

class CreateBranch extends CreateRecord
{
    protected static string $resource = BranchResource::class;

    protected function afterCreate(): void
    {
        $branch = $this->record;
        $userIds = $this->data['users'] ?? [];

        foreach ($userIds as $userId) {
            $user = \App\Models\User::find($userId);

            if ($user) {
                $adminRole = Role::where([
                    'name' => 'Super Admin',
                    'branch_id' => $branch->id,
                ])->first();

                if ($adminRole) {
                    $user->roles()->attach($adminRole->id, ['branch_id' => $branch->id]);
                }
            }
        }
    }
}
