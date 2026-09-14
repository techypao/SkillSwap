<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'email',
    'password',
    'role',
    'profile_picture',
    'school_organization',
    'school_id',
    'program_id',
    'program_name',
    'year_level',
    'bio',
    'onboarding_completed',
    'skill_credits',
])]
#[Hidden([
    'password',
    'remember_token',
])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::created(function (User $user): void {
            $user->creditTransactions()->create([
                'amount' => 1,
                'reason' => CreditTransaction::REASON_WELCOME_BONUS,
            ]);

            $user->increment('skill_credits');
        });
    }

    /**
     * Keep a new account and its welcome credit in the same transaction.
     *
     * @param  array<string, mixed>  $options
     */
    public function save(array $options = []): bool
    {
        if ($this->exists) {
            return parent::save($options);
        }

        return $this->getConnection()->transaction(fn (): bool => parent::save($options));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'onboarding_completed' => 'boolean',
            'skill_credits' => 'integer',
            'year_level' => 'integer',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Canonical school name when one is linked, else the free-text value.
     */
    protected function schoolDisplayName(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->school?->name ?? $this->school_organization);
    }

    /**
     * Canonical program name when one is linked, else the free-text value.
     */
    protected function programDisplayName(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->program?->name ?? $this->program_name);
    }

    public function teachingSkills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'user_skills')
            ->wherePivot('type', 'teach')
            ->withTimestamps();
    }

    public function learningSkills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'user_skills')
            ->wherePivot('type', 'learn')
            ->withTimestamps();
    }

    public function createdSkills(): HasMany
    {
        return $this->hasMany(Skill::class, 'created_by');
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(UserAvailability::class);
    }

    public function sentSwapRequests(): HasMany
    {
        return $this->hasMany(SwapRequest::class, 'sender_id');
    }

    public function receivedSwapRequests(): HasMany
    {
        return $this->hasMany(SwapRequest::class, 'recipient_id');
    }

    public function creditTransactions(): HasMany
    {
        return $this->hasMany(CreditTransaction::class);
    }

    public function reviewsReceived(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewee_id');
    }

    public function reviewsWritten(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }
}
