<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    use HasUuids;
    protected $fillable = [
        'plan_id',
        'name',
        'slug',
        'logo',
        'is_active',
    ];
}
