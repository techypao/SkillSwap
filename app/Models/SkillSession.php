<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SkillSession extends Model
{
    public const STATUS_PROPOSED = 'proposed';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const MEETING_TYPE_ONLINE = 'online';

    public const MEETING_TYPE_IN_PERSON = 'in_person';

    public const ALLOWED_DURATIONS = [30, 60, 90, 120];

    protected $fillable = [
        'swap_request_id',
        'scheduled_by',
        'scheduled_at',
        'duration_minutes',
        'meeting_type',
        'meeting_details',
        'status',
        'confirmed_at',
        'sender_confirmed_at',
        'recipient_confirmed_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'duration_minutes' => 'integer',
            'confirmed_at' => 'datetime',
            'sender_confirmed_at' => 'datetime',
            'recipient_confirmed_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function swapRequest(): BelongsTo
    {
        return $this->belongsTo(SwapRequest::class);
    }

    public function scheduledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scheduled_by');
    }

    public function creditTransactions(): HasMany
    {
        return $this->hasMany(CreditTransaction::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
