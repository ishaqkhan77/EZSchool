<?php

namespace App\Filament\Resources\ParentModels\Pages;

use App\Filament\Resources\ParentModels\ParentModelResource;
use Filament\Resources\Pages\CreateRecord;

class CreateParentModel extends CreateRecord
{
    protected static string $resource = ParentModelResource::class;

    public function getTitle(): string
    {
        return "Add New Parent";
    }

    protected function afterCreate(): void
    {
        $parent = $this->record;
        $user = $parent->user;
        $plainPassword = $this->data['user']['password'] ?? null;

        if ($user) {
            $user->branches()->syncWithoutDetaching([$parent->branch_id]);

            $role = \Spatie\Permission\Models\Role::where([
                'name' => 'Parent',
                'branch_id' => $parent->branch_id,
            ])->first();

            if ($role) {
                $user->roles()->syncWithoutDetaching([
                    $role->id => ['branch_id' => $parent->branch_id]
                ]);
            }

            $plainPassword = $this->data['user']['password'] ?? null;
            if ($plainPassword) {
                // NTD
                // Now you can safely send the email
                // Mail::to($user->email)->send(new ParentWelcomeMail($user, $plainPassword));

                // For testing, you could log it:
                // \Log::info("Parent created: {$user->email} with password: {$plainPassword}");
            }
        }
    }
}
