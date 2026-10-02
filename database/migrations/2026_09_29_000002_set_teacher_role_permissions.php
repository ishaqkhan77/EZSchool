<?php

use App\Models\Role;
use App\Services\TeacherRolePermissions;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Role::query()->where('name', 'Teacher')->each(
            fn (Role $role) => TeacherRolePermissions::sync($role)
        );
    }

    public function down(): void
    {
        // Permission assignments are intentionally not restored to the former over-broad state.
    }
};