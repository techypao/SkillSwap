<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
    <title>Swap Chat with {{ $otherUser->name }} - SkillSwap</title>

    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #f5f7fb; color: #1f2937; }
        .navbar { background: white; border-bottom: 1px solid #e5e7eb; padding: 16px 30px; display: flex; align-items: center; justify-content: space-between; }
        .brand, .back-link { color: #2563eb; text-decoration: none; }
        .brand { font-size: 22px; font-weight: bold; }
        .navbar-right, .actions { display: flex; align-items: center; gap: 10px; }
        .logout-button, .btn { border: 0; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: bold; padding: 10px 15px; text-decoration: none; }
        .logout-button { border: 1px solid #d1d5db; background: white; }
        .btn-primary { background: #2563eb; color: white; }
        .btn-success { background: #15803d; color: white; }
        .btn-danger { background: #dc2626; color: white; }
        .container { max-width: 900px; margin: 0 auto; padding: 30px 20px 60px; }
        .back-link { display: inline-block; margin-bottom: 18px; }
        .card { background: white; border: 1px solid #e5e7eb; border-radius: 14px; margin-bottom: 20px; padding: 22px; }
        .card h1, .card h2 { margin-top: 0; }
        .muted { color: #6b7280; }
        .alert { border-radius: 10px; margin-bottom: 16px; padding: 13px 16px; }
        .alert-success { background: #dcfce7; color: #166534; }
        .alert-info { background: #eff6ff; color: #1d4ed8; }
        .alert-error { background: #fef2f2; color: #b91c1c; }
        .session-details { background: #f9fafb; border-radius: 10px; padding: 15px; }
        .session-details p { margin: 7px 0; }
        .messages { display: flex; flex-direction: column; gap: 12px; max-height: 480px; overflow-y: auto; padding: 4px; }
        .message { align-self: flex-start; background: #f3f4f6; border-radius: 12px; max-width: 76%; padding: 11px 14px; }
        .message.mine { align-self: flex-end; background: #dbeafe; }
        .message p { margin: 5px 0; overflow-wrap: anywhere; white-space: pre-wrap; }
        .message-meta { color: #6b7280; font-size: 12px; }
        .stars { color: #d97706; letter-spacing: 1px; }
        textarea { border: 1px solid #d1d5db; border-radius: 8px; min-height: 90px; padding: 11px; resize: vertical; width: 100%; }
        .message-form button { margin-top: 10px; }
        @media (max-width: 700px) { .navbar { padding: 15px 18px; } .navbar-right span { display: none; } .message { max-width: 90%; } }
    </style>
</head>

<body>
    <nav class="navbar">
        <a href="{{ route('dashboard') }}" class="brand">SkillSwap</a>
        <div class="navbar-right">
            <span>{{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="logout-button">Logout</button>
            </form>
        </div>
    </nav>

    <main class="container">
        <a href="{{ route('dashboard') }}" class="back-link">← Back to Dashboard</a>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if (session('info'))
            <div class="alert alert-info">{{ session('info') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-error">{{ $errors->first() }}</div>
        @endif

        <section class="card">
            <h1>{{ $swapRequest->sender->name }} ↔ {{ $swapRequest->recipient->name }}</h1>
            <p><strong>Skill Swap</strong></p>
            <p class="muted">You teach {{ $skillYouTeach->name }} · You learn {{ $skillYouLearn->name }}</p>
            <p>
                Status:
                {{ $swapRequest->skillSession?->status === \App\Models\SkillSession::STATUS_COMPLETED ? 'Completed' : 'Active' }}
            </p>
        </section>

        <section class="card">
            <h2>Session</h2>

            @if ($swapRequest->skillSession?->status === \App\Models\SkillSession::STATUS_COMPLETED)
                <div class="session-details">
                    <p><strong>✓ SKILL SWAP COMPLETED</strong></p>
                    <p>Both participants confirmed completion.</p>
                    <p>{{ $swapRequest->skillSession->scheduled_at->format('F j, Y') }}</p>
                    <p>+1 Skill Credit earned</p>
                    <p><strong>This conversation is closed.</strong></p>
                </div>

                @if ($currentUserReview)
                    <p><strong>✓ Review Submitted</strong></p>
                    <p class="stars">
                        {{ str_repeat('★', $currentUserReview->rating) }}{{ str_repeat('☆', 5 - $currentUserReview->rating) }}
                    </p>
                @else
                    <a href="{{ route('reviews.create', $swapRequest->skillSession) }}" class="btn btn-primary">Leave Review</a>
                @endif
            @elseif (! $swapRequest->skillSession || $swapRequest->skillSession->status === \App\Models\SkillSession::STATUS_CANCELLED)
                <p class="muted">Discuss a time that works for both of you, then send a session proposal.</p>
                <a href="{{ route('skill-sessions.create', $swapRequest) }}" class="btn btn-primary">Propose Session</a>
            @else
                <div class="session-details">
                    <p><strong>{{ $swapRequest->skillSession->status === \App\Models\SkillSession::STATUS_CONFIRMED ? 'SESSION CONFIRMED' : 'SESSION PROPOSAL' }}</strong></p>
                    <p>{{ $swapRequest->skillSession->scheduled_at->format('F j, Y') }} at {{ $swapRequest->skillSession->scheduled_at->format('g:i A') }}</p>
                    <p>{{ $swapRequest->skillSession->duration_minutes }} minutes · {{ ucwords(str_replace('_', ' ', $swapRequest->skillSession->meeting_type)) }}</p>
                    @if ($swapRequest->skillSession->meeting_details)
                        <p>Meeting details: {{ $swapRequest->skillSession->meeting_details }}</p>
                    @endif
                    <p>Proposed by {{ $swapRequest->skillSession->scheduledBy->name }}</p>
                </div>

                @if ($swapRequest->skillSession->status === \App\Models\SkillSession::STATUS_PROPOSED)
                    @if ($swapRequest->skillSession->scheduled_by === auth()->id())
                        <p class="muted">Waiting for {{ $otherUser->name }} to agree or decline.</p>
                    @else
                        <div class="actions">
                            <form method="POST" action="{{ route('skill-sessions.agree', $swapRequest->skillSession) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-success">Agree</button>
                            </form>
                            <form method="POST" action="{{ route('skill-sessions.decline', $swapRequest->skillSession) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-danger">Decline</button>
                            </form>
                        </div>
                    @endif
                @elseif ($swapRequest->skillSession->scheduled_at->isFuture())
                    <p class="muted">Completion can be confirmed after the session starts.</p>
                @else
                    @if ($currentUserConfirmedAt)
                        <p><strong>✓ You confirmed completion.</strong></p>
                        <p class="muted">Waiting for {{ $otherUser->name }} to confirm.</p>
                    @else
                        @if ($otherUserConfirmedAt)
                            <p>{{ $otherUser->name }} has confirmed completion.</p>
                        @endif

                        <form method="POST" action="{{ route('skill-sessions.completion.store', $swapRequest->skillSession) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-success">Confirm Session Completed</button>
                        </form>
                    @endif
                @endif
            @endif
        </section>

        <section class="card">
            <h2>Messages</h2>
            <div class="messages">
                @forelse ($swapRequest->messages as $message)
                    <article class="message {{ $message->sender_id === auth()->id() ? 'mine' : '' }}">
                        <strong>{{ $message->sender->name }}</strong>
                        <p>{{ $message->message }}</p>
                        <span class="message-meta">{{ $message->created_at->format('M j, Y g:i A') }}</span>
                    </article>
                @empty
                    <p class="muted">No messages yet. Start discussing your swap.</p>
                @endforelse
            </div>
        </section>

        @if ($swapRequest->skillSession?->status !== \App\Models\SkillSession::STATUS_COMPLETED)
            <section class="card">
                <form method="POST" action="{{ route('swap-requests.messages.store', $swapRequest) }}" class="message-form">
                    @csrf
                    <label for="message"><strong>Send a message</strong></label>
                    <textarea id="message" name="message" maxlength="2000" required>{{ old('message') }}</textarea>
                    <button type="submit" class="btn btn-primary">Send Message</button>
                </form>
            </section>
        @endif
    </main>
</body>

</html>
