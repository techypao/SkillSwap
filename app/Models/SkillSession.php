<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class SkillSession extends Model
{
    public const TEACHING_SIDE_SENDER = 'sender';

    public const TEACHING_SIDE_RECIPIENT = 'recipient';

    public const STATUS_PROPOSED = 'proposed';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const MEETING_TYPE_ONLINE = 'online';

    public const MEETING_TYPE_IN_PERSON = 'in_person';

    public const MIN_DURATION_MINUTES = 5;

    public const MAX_DURATION_MINUTES = 480;

    /**
     * Quick-pick suggestions offered alongside the free-form duration input.
     *
     * @var list<int>
     */
    public const SUGGESTED_DURATIONS = [30, 45, 60, 90, 120, 180];

    protected $fillable = [
        'swap_request_id',
        'scheduled_by',
        'teaching_side',
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

    protected static function booted(): void
    {
        static::saving(function (SkillSession $session): void {
            if ($session->isDirty('swap_request_id')) {
                $session->unsetRelation('swapRequest');
            }

            $isNewProposal = ! $session->exists
                || ($session->status === self::STATUS_PROPOSED
                    && $session->isDirty(['status', 'teaching_side', 'swap_request_id']));

            if ($isNewProposal || $session->isDirty(['teaching_side', 'swap_request_id'])) {
                if (! $session->hasResolvedRoles()) {
                    throw ValidationException::withMessages([
                        'teaching_side' => 'Choose a teacher from two different swap participants.',
                    ]);
                }
            }

            if ($isNewProposal && (int) $session->learner?->fresh()?->skill_credits < 1) {
                throw ValidationException::withMessages([
                    'teaching_side' => 'The learner needs at least 1 Skill Credit before this session can be proposed.',
                ]);
            }
        });
    }

    /**
     * Historical sessions have no recorded direction and must not be guessed.
     */
    public function hasResolvedRoles(): bool
    {
        return in_array($this->teaching_side, [self::TEACHING_SIDE_SENDER, self::TEACHING_SIDE_RECIPIENT], true)
            && $this->swapRequest !== null
            && $this->swapRequest->sender_id !== $this->swapRequest->recipient_id;
    }

    protected function teacher(): Attribute
    {
        return Attribute::get(fn (): ?User => $this->hasResolvedRoles()
            ? ($this->teaching_side === self::TEACHING_SIDE_SENDER ? $this->swapRequest->sender : $this->swapRequest->recipient)
            : null)->withoutObjectCaching();
    }

    protected function learner(): Attribute
    {
        return Attribute::get(fn (): ?User => $this->hasResolvedRoles()
            ? ($this->teaching_side === self::TEACHING_SIDE_SENDER ? $this->swapRequest->recipient : $this->swapRequest->sender)
            : null)->withoutObjectCaching();
    }

    protected function taughtSkill(): Attribute
    {
        return Attribute::get(fn (): ?Skill => $this->hasResolvedRoles()
            ? ($this->teaching_side === self::TEACHING_SIDE_SENDER ? $this->swapRequest->offeredSkill : $this->swapRequest->requestedSkill)
            : null)->withoutObjectCaching();
    }

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

    /**
     * The scheduled finish, or null when the stored schedule cannot be trusted.
     */
    public function endsAt(): ?CarbonInterface
    {
        $scheduledAt = $this->getAttributes()['scheduled_at'] ?? null;
        $durationMinutes = filter_var($this->getAttributes()['duration_minutes'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => self::MIN_DURATION_MINUTES, 'max_range' => self::MAX_DURATION_MINUTES],
        ]);

        if (! is_string($scheduledAt) || $durationMinutes === false) {
            return null;
        }

        try {
            $startsAt = $this->scheduled_at;
        } catch (InvalidArgumentException) {
            return null;
        }

        if ($startsAt === null || $startsAt->format($this->getDateFormat()) !== $scheduledAt) {
            return null;
        }

        return $startsAt->copy()->addMinutes($durationMinutes);
    }

    public function hasStarted(): bool
    {
        return $this->endsAt() !== null && ! $this->scheduled_at->isFuture();
    }

    public function hasEnded(): bool
    {
        $endsAt = $this->endsAt();

        return $endsAt !== null && ! $endsAt->isFuture();
    }

    /**
     * Whether the scheduled window is currently running.
     */
    public function isInProgress(): bool
    {
        return $this->hasStarted() && ! $this->hasEnded();
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
