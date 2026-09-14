<?php

namespace App\Http\Controllers;

use App\Models\SkillSession;
use App\Models\SwapMessage;
use App\Models\SwapRequest;
use App\Notifications\SwapMessageReceived;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SwapMessageController extends Controller
{
    /**
     * Maximum number of messages returned per polling request.
     */
    public const POLL_LIMIT = 50;

    /**
     * Return messages newer than the given message ID for live chat polling.
     */
    public function index(Request $request, SwapRequest $swapRequest): JsonResponse
    {
        Gate::authorize('chat', $swapRequest);

        $validated = $request->validate([
            'after' => ['nullable', 'integer', 'min:0'],
        ]);

        $userId = $request->user()->id;

        $messages = $swapRequest->messages()
            ->with('sender:id,name')
            ->where('id', '>', (int) ($validated['after'] ?? 0))
            ->orderBy('id')
            ->limit(self::POLL_LIMIT)
            ->get()
            ->map(fn (SwapMessage $message): array => [
                'id' => $message->id,
                'sender_id' => $message->sender_id,
                'sender_name' => $message->sender->name,
                'is_mine' => $message->sender_id === $userId,
                'message' => $message->message,
                'created_at' => $message->created_at->toIso8601String(),
                'created_at_label' => $message->created_at->format('M j, g:i A'),
            ]);

        return response()->json([
            'messages' => $messages,
            'closed' => $swapRequest->skillSession()->where('status', SkillSession::STATUS_COMPLETED)->exists(),
        ]);
    }

    public function store(Request $request, SwapRequest $swapRequest): RedirectResponse
    {
        Gate::authorize('chat', $swapRequest);

        if ($swapRequest->skillSession?->status === SkillSession::STATUS_COMPLETED) {
            return redirect()
                ->route('swap-requests.chat', $swapRequest)
                ->with('info', 'This conversation is closed.');
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $swapRequest->messages()->create([
            'sender_id' => $request->user()->id,
            'message' => trim($validated['message']),
        ]);

        $swapRequest->otherParticipantFor($request->user())
            ->notify(new SwapMessageReceived($request->user(), $swapRequest->id));

        return redirect()->route('swap-requests.chat', $swapRequest);
    }
}
