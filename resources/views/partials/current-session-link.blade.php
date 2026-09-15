@once
    <style>
        .navbar a.current-session-link {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: #15803d;
            font-weight: bold;
            white-space: nowrap;
        }

        .current-session-link::before {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #16a34a;
            content: '';
        }
    </style>
@endonce

@if ($currentSkillSession)
    <a
        href="{{ route('swap-requests.chat', [
            'swapRequest' => $currentSkillSession->swap_request_id,
            'panel' => $currentSkillSession->meeting_type === \App\Models\SkillSession::MEETING_TYPE_ONLINE ? 'call' : 'session',
        ]) }}"
        class="current-session-link"
        aria-label="Open current session"
    >Session</a>
@endif
