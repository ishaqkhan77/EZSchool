<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classes extends Model
{
    use HasUuids;
    protected $fillable = ['branch_id', 'name', 'section','is_active'];

    public function branch(): BelongsTo {
        return $this->belongsTo(Branch::class);
    }

    public function students(): BelongsToMany {
        return $this->belongsToMany(Student::class, 'class_student', 'class_id', 'student_id');
    }

    public function teachers(): BelongsToMany {
        return $this->belongsToMany(Teacher::class, 'class_teacher', 'class_id', 'teacher_id')
            ->withPivot(['academic_year', 'is_main_teacher'])
            ->withTimestamps();
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'class_subject', 'class_id', 'subject_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'class_id');
    }
}
