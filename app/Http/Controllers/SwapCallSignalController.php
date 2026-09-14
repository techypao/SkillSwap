<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCallSignalRequest;
use App\Models\CallSignal;
use App\Models\SwapRequest;
use App\Services\SwapCallPresence;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

/**
 * HTTP signaling relay for 1-to-1 WebRTC calls between the two participants of a swap.
 */
class SwapCallSignalController extends Controller
{
    /**
     * Signals from the other participant newer than `after`, plus whether they currently have the call open.
     */
    public function index(Request $request, SwapRequest $swapRequest, SwapCallPresence $presence): JsonResponse
    {
        Gate::authorize('call', $swapRequest);

        $validated = $request->validate([
            'after' => ['nullable', 'integer', 'min:0'],
            'in_call' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();
        $after = (int) ($validated['after'] ?? 0);

        if ($request->boolean('in_call')) {
            $presence->markPresent($swapRequest, $user);
        }

        $signals = $swapRequest->callSignals()
            ->where('sender_id', '!=', $user->id)
            ->where('id', '>', $after)
            ->orderBy('id')
            ->limit(CallSignal::POLL_LIMIT)
            ->get(['id', 'type', 'payload', 'created_at']);

        return response()->json([
            'signals' => $signals->map(fn (CallSignal $signal): array => [
                'id' => $signal->id,
                'type' => $signal->type,
                'payload' => $signal->payload,
                'created_at' => $signal->created_at->toIso8601String(),
            ]),
            'cursor' => $signals->last()?->id ?? $after,
            'peer_in_call' => $presence->isPresent($swapRequest, $swapRequest->otherParticipantFor($user)),
        ]);
    }

    /**
     * Relay one signal to the other participant.
     */
    public function store(StoreCallSignalRequest $request, SwapRequest $swapRequest, SwapCallPresence $presence): JsonResponse
    {
        $user = $request->user();
        $type = $request->validated('type');

        if ($type === CallSignal::TYPE_JOIN) {
            $swapRequest->callSignals()
                ->where('created_at', '<', now()->subMinutes((int) config('webrtc.signal_retention_minutes')))
                ->delete();
            $presence->markPresent($swapRequest, $user);
        }

        if ($type === CallSignal::TYPE_LEAVE) {
            $presence->markAbsent($swapRequest, $user);
        }

        $signal = $swapRequest->callSignals()->create([
            'sender_id' => $user->id,
            'type' => $type,
            'payload' => $this->payloadFor($type, $request->validated('payload') ?? []),
        ]);

        return response()->json([
            'id' => $signal->id,
            // The joiner only needs signals sent after this point.
            'cursor' => $signal->id,
            'peer_in_call' => $presence->isPresent($swapRequest, $swapRequest->otherParticipantFor($user)),
        ], 201);
    }

    /**
     * Keep only the fields each signal type needs.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    private function payloadFor(string $type, array $payload): ?array
    {
        return match ($type) {
            CallSignal::TYPE_OFFER, CallSignal::TYPE_ANSWER => Arr::only($payload, ['call_id', 'sdp']),
            CallSignal::TYPE_CANDIDATE => [
                'call_id' => $payload['call_id'],
                'candidate' => Arr::only($payload['candidate'], ['candidate', 'sdpMid', 'sdpMLineIndex', 'usernameFragment']),
            ],
            CallSignal::TYPE_MEDIA => [
                'audio' => (bool) $payload['audio'],
                'video' => (bool) $payload['video'],
            ],
            default => null,
        };
    }
}
