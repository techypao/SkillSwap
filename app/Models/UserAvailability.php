<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAvailability extends Model
{
    protected $fillable = [
        'user_id',
        'day',
        'time_period',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}