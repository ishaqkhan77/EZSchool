<?php

namespace App\Services;

use App\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class TeacherRolePermissions
{
    public const PERMISSIONS = [
        'ViewAny:Attendance',
        'View:Attendance',
        'Create:Attendance',
        'Update:Attendance',
        'ViewAny:Classes',
        'View:Classes',
        'ViewAny:Student',
        'View:Student',
        'ViewAny:Exam',
        'View:Exam',
        'ViewAny:Subject',
        'View:Subject',
        'View:EnterMarks',
    ];

    public static function sync(Role $role): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($role->branch_id);

        $permissions = collect(self::PERMISSIONS)
            ->map(fn (string $name) => Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]));

        $role->syncPermissions($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}