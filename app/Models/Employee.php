<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'branch_id',
        'employee_id',
        'salary',
        'joining_date',
        'designation',
        'type'
    ];

    protected static function booted()
    {
        static::saved(function ($employee) {
            if ($employee->type === 'teacher') {
                \App\Models\Teacher::updateOrCreate(
                    ['employee_id' => $employee->id],
                    ['specialization' => 'General']
                );
            }
        });
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class, 'employee_id');
    }

    public function branch(): BelongsTo {
        return $this->belongsTo(Branch::class);
    }
}
