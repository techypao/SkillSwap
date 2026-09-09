<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditTransaction extends Model
{
    public const REASON_SESSION_COMPLETED = 'session_completed';

    protected $fillable = [
        'user_id',
        'skill_session_id',
        'amount',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function skillSession(): BelongsTo
    {
        return $this->belongsTo(SkillSession::class);
    }
}
