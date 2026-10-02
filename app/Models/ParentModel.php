<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ParentModel extends Model
{
    protected $table = 'parents';
    use HasUuids;
    protected $fillable = ['branch_id', 'user_id', 'full_name', 'phone', 'address'];

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'parent_student', 'parent_id', 'student_id')
            ->withPivot('relationship_type')
            ->withTimestamps();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getPrimaryRelationshipAttribute(): ?string
    {
        return $this->students->first()?->pivot?->relationship_type;
    }

    public function getFullNameWithPhoneAttribute(): string
    {
        return "{$this->full_name} ({$this->phone})";
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
