<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Skill extends Model
{
    protected $fillable = [
        'name',
        'is_approved',
        'created_by',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function usersWhoTeach(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_skills')
            ->wherePivot('type', 'teach')
            ->withTimestamps();
    }

    public function usersWhoLearn(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_skills')
            ->wherePivot('type', 'learn')
            ->withTimestamps();
    }
}