<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Student extends Model
{
    use HasUuids;
    protected $fillable = ['branch_id','parent_id','first_name', 'last_name', 'roll_number', 'branch_id', 'date_of_birth', 'status'];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(
            ParentModel::class,
            'parent_student',
            'student_id',
            'parent_id')
            ->withPivot('relationship_type')
            ->withTimestamps();
    }

    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(Classes::class, 'class_student', 'student_id', 'class_id')
            ->withPivot('academic_year')
            ->withTimestamps();
    }


    public function classTeacher()
    {
        $currentClass = $this->classes()
            ->wherePivot('academic_year', '2025-2026')
            ->first();

        if (!$currentClass) return null;

        return $currentClass->teachers()
            ->wherePivot('is_main_teacher', 1)
            ->first();
    }

    public function parentModels(): BelongsToMany
    {
        return $this->parents();
    }
}
