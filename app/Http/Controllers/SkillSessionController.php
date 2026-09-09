<?php

namespace App\Http\Controllers;

use App\Models\SkillSession;
use App\Models\SwapRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SkillSessionController extends Controller
{
    public function create(Request $request, SwapRequest $swapRequest): View|RedirectResponse
    {
        Gate::authorize('schedule', $swapRequest);
        abort_unless($swapRequest->status === SwapRequest::STATUS_ACCEPTED, 403);

        $skillSession = $swapRequest->skillSession;

        if ($skillSession && $skillSession->status !== SkillSession::STATUS_CANCELLED) {
            return redirect()
                ->route('swap-requests.chat', $swapRequest)
                ->with('info', 'This swap already has an active session proposal.');
        }

        $swapRequest->load([
            'sender.availabilities',
            'recipient.availabilities',
            'offeredSkill',
            'requestedSkill',
        ]);

        $currentUserIsSender = $request->user()->id === $swapRequest->sender_id;
        $currentUser = $currentUserIsSender ? $swapRequest->sender : $swapRequest->recipient;
        $otherUser = $currentUserIsSender ? $swapRequest->recipient : $swapRequest->sender;
        $skillYouTeach = $currentUserIsSender
            ? $swapRequest->offeredSkill
            : $swapRequest->requestedSkill;
        $skillYouLearn = $currentUserIsSender
            ? $swapRequest->requestedSkill
            : $swapRequest->offeredSkill;
        $allowedDurations = SkillSession::ALLOWED_DURATIONS;

        return view('skill-sessions.create', compact(
            'swapRequest',
            'currentUser',
            'otherUser',
            'skillYouTeach',
            'skillYouLearn',
            'allowedDurations'
        ));
    }

    public function store(Request $request, SwapRequest $swapRequest): RedirectResponse
    {
        Gate::authorize('schedule', $swapRequest);
        abort_unless($swapRequest->status === SwapRequest::STATUS_ACCEPTED, 403);

        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'date_format:H:i'],
            'duration_minutes' => ['required', 'integer', Rule::in(SkillSession::ALLOWED_DURATIONS)],
            'meeting_type' => [
                'required',
                Rule::in([
                    SkillSession::MEETING_TYPE_ONLINE,
                    SkillSession::MEETING_TYPE_IN_PERSON,
                ]),
            ],
            'meeting_details' => ['nullable', 'string', 'max:1000'],
        ]);

        $scheduledAt = Carbon::createFromFormat(
            'Y-m-d H:i',
            $validated['date'].' '.$validated['time'],
            (string) config('app.timezone')
        );

        if (! $scheduledAt->isFuture()) {
            throw ValidationException::withMessages([
                'date' => 'The session must be scheduled in the future.',
            ]);
        }

        $proposalCreated = DB::transaction(function () use ($request, $swapRequest, $scheduledAt, $validated): bool {
            $skillSession = SkillSession::query()
                ->where('swap_request_id', $swapRequest->id)
                ->lockForUpdate()
                ->first();

            if ($skillSession && $skillSession->status !== SkillSession::STATUS_CANCELLED) {
                return false;
            }

            $proposalAttributes = [
                'scheduled_by' => $request->user()->id,
                'scheduled_at' => $scheduledAt,
                'duration_minutes' => $validated['duration_minutes'],
                'meeting_type' => $validated['meeting_type'],
                'meeting_details' => $validated['meeting_details'] ?? null,
                'status' => SkillSession::STATUS_PROPOSED,
                'confirmed_at' => null,
                'sender_confirmed_at' => null,
                'recipient_confirmed_at' => null,
                'completed_at' => null,
            ];

            if ($skillSession) {
                $skillSession->update($proposalAttributes);

                return true;
            }

            return SkillSession::firstOrCreate(
                ['swap_request_id' => $swapRequest->id],
                $proposalAttributes
            )->wasRecentlyCreated;
        });

        if (! $proposalCreated) {
            return redirect()
                ->route('swap-requests.chat', $swapRequest)
                ->with('info', 'This swap already has an active session proposal.');
        }

        return redirect()
            ->route('swap-requests.chat', $swapRequest)
            ->with('success', 'Session proposal sent!');
    }

    public function agree(SkillSession $skillSession): RedirectResponse
    {
        Gate::authorize('respond', $skillSession);

        $updated = SkillSession::query()
            ->whereKey($skillSession->id)
            ->where('status', SkillSession::STATUS_PROPOSED)
            ->update([
                'status' => SkillSession::STATUS_CONFIRMED,
                'confirmed_at' => now(),
            ]);

        return redirect()
            ->route('swap-requests.chat', $skillSession->swap_request_id)
            ->with($updated === 1 ? 'success' : 'info', $updated === 1
                ? 'Session confirmed!'
                : 'This session proposal is no longer awaiting a response.');
    }

    public function decline(SkillSession $skillSession): RedirectResponse
    {
        Gate::authorize('respond', $skillSession);

        $updated = SkillSession::query()
            ->whereKey($skillSession->id)
            ->where('status', SkillSession::STATUS_PROPOSED)
            ->update([
                'status' => SkillSession::STATUS_CANCELLED,
                'confirmed_at' => null,
            ]);

        return redirect()
            ->route('swap-requests.chat', $skillSession->swap_request_id)
            ->with($updated === 1 ? 'success' : 'info', $updated === 1
                ? 'Session proposal declined. You can discuss another time in chat.'
                : 'This session proposal is no longer awaiting a response.');
    }
}
