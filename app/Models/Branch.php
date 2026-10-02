<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Branch extends Model
{
    use HasUuids;
    protected $fillable = [
        'school_id',
        'name',
        'location',
        'phone',
    ];

    public function getRouteKeyName(): string
    {
        return 'id';
    }

    protected static function booted()
    {
        static::created(function ($branch) {
            $defaults = ['Super Admin','Admin', 'Teacher', 'Parent'];

            foreach ($defaults as $roleName) {
                \Spatie\Permission\Models\Role::create([
                    'name' => $roleName,
                    'guard_name' => 'web',
                    'branch_id' => $branch->id,
                ]);
            }

            \App\Services\TeacherRolePermissions::sync(
                \App\Models\Role::where('name', 'Teacher')->where('branch_id', $branch->id)->firstOrFail()
            );
        });
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function parents()
    {
        return $this->hasMany(ParentModel::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

}
