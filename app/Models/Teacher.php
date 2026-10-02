<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Teacher extends Model
{

    use HasUuids;

    protected $fillable = [
        'employee_id',
        'specialization',
    ];

    public function employee(): BelongsTo {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function branch(): HasOneThrough
    {
        return $this->hasOneThrough(
            \App\Models\Branch::class,
            \App\Models\Employee::class,
            'id',           // Foreign key on employees (Teacher's employee_id)
            'id',           // Foreign key on branches (Employee's branch_id)
            'employee_id',  // Local key on teachers
            'branch_id'    // Local key on employees
        );
    }

    public function user(): HasOneThrough
    {
        return $this->hasOneThrough(
            \App\Models\User::class,
            \App\Models\Employee::class,
            'id',
            'id',
            'employee_id',
            'user_id'
        );
    }

    public function classes(): BelongsToMany {
        return $this->belongsToMany(Classes::class, 'class_teacher', 'teacher_id', 'class_id')
            ->withPivot(['academic_year', 'is_main_teacher'])
            ->withTimestamps();
    }
}
