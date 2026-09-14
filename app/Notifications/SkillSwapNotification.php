<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Notification;

/**
 * Base in-app notification for SkillSwap activity.
 *
 * Only stores references and a short summary; never message bodies,
 * meeting links, or locations. Delivery is database-only for now, but the
 * payload shape is broadcast-friendly for a future real-time channel.
 */
abstract class SkillSwapNotification extends Notification
{
    public function __construct(
        public User $actor,
        public int $swapRequestId,
    ) {}

    /**
     * Stable machine-readable notification type, e.g. "swap_request.received".
     */
    abstract public function type(): string;

    /**
     * Short human-readable summary shown in the bell dropdown.
     */
    abstract public function message(): string;

    /**
     * Relative destination path; the target route still enforces its own policy.
     */
    public function url(): string
    {
        return route('swap-requests.chat', $this->swapRequestId, absolute: false);
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function databaseType(object $notifiable): string
    {
        return $this->type();
    }

    /**
     * @return array{type: string, actor_id: int, actor_name: string, message: string, swap_request_id: int, url: string}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->type(),
            'actor_id' => $this->actor->id,
            'actor_name' => $this->actor->name,
            'message' => $this->message(),
            'swap_request_id' => $this->swapRequestId,
            'url' => $this->url(),
        ];
    }
}
