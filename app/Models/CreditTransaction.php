<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditTransaction extends Model
{
    public const REASON_WELCOME_BONUS = 'welcome_bonus';

    public const REASON_SESSION_COMPLETED = 'session_completed';

    public const REASON_SESSION_TAUGHT = 'session_taught';

    public const REASON_SESSION_LEARNED = 'session_learned';

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

    protected function displayLabel(): Attribute
    {
        return Attribute::get(function (): string {
            $skillName = $this->skillSession?->taughtSkill?->name;

            return match ($this->reason) {
                self::REASON_WELCOME_BONUS => 'Welcome to SkillSwap',
                self::REASON_SESSION_TAUGHT => $skillName === null
                    ? 'Skill teaching session'
                    : 'Taught '.$skillName,
                self::REASON_SESSION_LEARNED => $skillName === null
                    ? 'Skill learning session'
                    : 'Learned '.$skillName,
                self::REASON_SESSION_COMPLETED => 'Session completed',
                default => 'Skill Credit transaction',
            };
        });
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
