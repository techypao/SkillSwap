<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SwapRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'sender_id',
        'recipient_id',
        'offered_skill_id',
        'requested_skill_id',
        'message',
        'status',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'responded_at' => 'datetime',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function offeredSkill(): BelongsTo
    {
        return $this->belongsTo(Skill::class, 'offered_skill_id');
    }

    public function requestedSkill(): BelongsTo
    {
        return $this->belongsTo(Skill::class, 'requested_skill_id');
    }

    public function skillSession(): HasOne
    {
        return $this->hasOne(SkillSession::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SwapMessage::class);
    }
}
