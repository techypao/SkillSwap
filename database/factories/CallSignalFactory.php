<?php

namespace Database\Factories;

use App\Models\CallSignal;
use App\Models\SwapRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Callers must provide the swap and sender, e.g. via {@see self::between()}.
 *
 * @extends Factory<CallSignal>
 */
class CallSignalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => CallSignal::TYPE_JOIN,
            'payload' => null,
        ];
    }

    public function between(SwapRequest $swapRequest, User $sender): static
    {
        return $this->state(fn (array $attributes): array => [
            'swap_request_id' => $swapRequest->id,
            'sender_id' => $sender->id,
        ]);
    }

    public function offer(string $callId = 'call-1'): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => CallSignal::TYPE_OFFER,
            'payload' => ['call_id' => $callId, 'sdp' => "v=0\r\no=- 1 2 IN IP4 127.0.0.1\r\n"],
        ]);
    }
}
