<?php

namespace App\Http\Controllers;

use App\Models\SwapRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class SwapRequestController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'recipient_id' => ['required', 'integer', 'exists:users,id'],
            'offered_skill_id' => ['required', 'integer', 'exists:skills,id'],
            'requested_skill_id' => ['required', 'integer', 'exists:skills,id'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $sender = $request->user();
        $recipient = User::findOrFail($validated['recipient_id']);
        $offeredSkillId = (int) $validated['offered_skill_id'];
        $requestedSkillId = (int) $validated['requested_skill_id'];

        if ($sender->is($recipient)) {
            throw ValidationException::withMessages([
                'recipient_id' => 'You cannot send a swap request to yourself.',
            ]);
        }

        if ($recipient->role !== 'user' || ! $recipient->onboarding_completed) {
            throw ValidationException::withMessages([
                'recipient_id' => 'This user cannot receive swap requests.',
            ]);
        }

        if (! $sender->teachingSkills()->whereKey($offeredSkillId)->exists()) {
            throw ValidationException::withMessages([
                'offered_skill_id' => 'You can only offer a skill that you teach.',
            ]);
        }

        if (! $recipient->learningSkills()->whereKey($offeredSkillId)->exists()) {
            throw ValidationException::withMessages([
                'offered_skill_id' => 'The recipient must want to learn the skill you offer.',
            ]);
        }

        if (! $sender->learningSkills()->whereKey($requestedSkillId)->exists()) {
            throw ValidationException::withMessages([
                'requested_skill_id' => 'You can only request a skill that you want to learn.',
            ]);
        }

        if (! $recipient->teachingSkills()->whereKey($requestedSkillId)->exists()) {
            throw ValidationException::withMessages([
                'requested_skill_id' => 'The recipient must teach the skill you request.',
            ]);
        }

        $swapRequest = SwapRequest::firstOrCreate(
            [
                'sender_id' => $sender->id,
                'recipient_id' => $recipient->id,
                'offered_skill_id' => $offeredSkillId,
                'requested_skill_id' => $requestedSkillId,
                'status' => SwapRequest::STATUS_PENDING,
            ],
            [
                'message' => $validated['message'] ?? null,
            ]
        );

        if (! $swapRequest->wasRecentlyCreated) {
            return redirect()
                ->route('matches.show', $recipient)
                ->with('info', 'You already have a pending swap request for this skill exchange.');
        }

        return redirect()
            ->route('matches.show', $recipient)
            ->with('success', 'Swap request sent successfully!');
    }

    public function accept(SwapRequest $swapRequest): RedirectResponse
    {
        Gate::authorize('respond', $swapRequest);

        return $this->respond(
            $swapRequest,
            SwapRequest::STATUS_ACCEPTED,
            'Swap request accepted!'
        );
    }

    public function reject(SwapRequest $swapRequest): RedirectResponse
    {
        Gate::authorize('respond', $swapRequest);

        return $this->respond(
            $swapRequest,
            SwapRequest::STATUS_REJECTED,
            'Swap request rejected.'
        );
    }

    private function respond(
        SwapRequest $swapRequest,
        string $status,
        string $successMessage
    ): RedirectResponse {
        $updated = SwapRequest::query()
            ->whereKey($swapRequest->id)
            ->where('status', SwapRequest::STATUS_PENDING)
            ->update([
                'status' => $status,
                'responded_at' => now(),
            ]);

        if ($updated === 0) {
            return redirect()
                ->route('dashboard')
                ->with('info', 'This swap request has already been responded to.');
        }

        return redirect()
            ->route('dashboard')
            ->with('success', $successMessage);
    }
}
