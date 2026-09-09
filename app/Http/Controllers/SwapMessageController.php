<?php

namespace App\Http\Controllers;

use App\Models\SkillSession;
use App\Models\SwapRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SwapMessageController extends Controller
{
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

        return redirect()->route('swap-requests.chat', $swapRequest);
    }
}
