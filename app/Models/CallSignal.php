<?php

namespace App\Models;

use Database\Factories\CallSignalFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Short-lived WebRTC signaling message exchanged between the two participants of a swap call.
 *
 * Only call setup data (SDP, ICE candidates, join/leave, mic/camera state) is stored; never media.
 */
class CallSignal extends Model
{
    /** @use HasFactory<CallSignalFactory> */
    use HasFactory, MassPrunable;

    public const TYPE_JOIN = 'join';

    public const TYPE_LEAVE = 'leave';

    public const TYPE_OFFER = 'offer';

    public const TYPE_ANSWER = 'answer';

    public const TYPE_CANDIDATE = 'candidate';

    public const TYPE_MEDIA = 'media';

    /**
     * @var list<string>
     */
    public const TYPES = [
        self::TYPE_JOIN,
        self::TYPE_LEAVE,
        self::TYPE_OFFER,
        self::TYPE_ANSWER,
        self::TYPE_CANDIDATE,
        self::TYPE_MEDIA,
    ];

    /**
     * Signal types that carry a payload.
     *
     * @var list<string>
     */
    public const PAYLOAD_TYPES = [
        self::TYPE_OFFER,
        self::TYPE_ANSWER,
        self::TYPE_CANDIDATE,
        self::TYPE_MEDIA,
    ];

    public const MAX_SDP_LENGTH = 20000;

    /**
     * Maximum number of signals returned per polling request.
     */
    public const POLL_LIMIT = 100;

    protected $fillable = [
        'swap_request_id',
        'sender_id',
        'type',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    /**
     * Signaling is only needed while a call is being set up.
     *
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::query()->where(
            'created_at',
            '<',
            now()->subMinutes((int) config('webrtc.signal_retention_minutes'))
        );
    }

    public function swapRequest(): BelongsTo
    {
        return $this->belongsTo(SwapRequest::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
