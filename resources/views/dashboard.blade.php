<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Dashboard - SkillSwap</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f5f7fb;
            color: #1f2937;
        }

        .navbar {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 16px 30px;

            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .brand {
            font-size: 22px;
            font-weight: bold;
            color: #2563eb;
            text-decoration: none;
        }

        .navbar-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .navbar a {
            text-decoration: none;
            color: #374151;
        }

        .logout-button {
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: white;
            cursor: pointer;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 30px 20px 60px;
        }

        .welcome {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 25px;

            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;

            margin-bottom: 25px;
        }

        .welcome h1 {
            margin: 0 0 8px;
        }

        .welcome p {
            margin: 0;
            color: #6b7280;
        }

        .btn {
            display: inline-block;
            padding: 11px 16px;
            border-radius: 8px;
            border: none;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
            cursor: pointer;
        }

        .btn-primary {
            background: #2563eb;
            color: white;
        }

        .btn-danger {
            background: #dc2626;
            color: white;
        }

        .alert {
            border-radius: 10px;
            margin-bottom: 20px;
            padding: 14px 16px;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
        }

        .alert-info {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }

        .card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 22px;
        }

        .card h2,
        .card h3 {
            margin-top: 0;
        }

        .credits {
            font-size: 38px;
            font-weight: bold;
            margin: 10px 0;
        }

        .muted {
            color: #6b7280;
        }

        .skills {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .skill {
            background: #eff6ff;
            color: #1d4ed8;

            padding: 7px 11px;
            border-radius: 20px;

            font-size: 13px;
        }

        .skill-learning {
            background: #f0fdf4;
            color: #15803d;
        }

        .skill-details {
            display: inline-flex;
            flex-direction: column;
            gap: 2px;
            border-radius: 12px;
        }

        .skill-details small {
            color: #6b7280;
            font-size: 11px;
        }

        .section {
            margin-bottom: 25px;
        }

        .section h2 {
            font-size: 20px;
            margin-bottom: 12px;
        }

        .empty {
            color: #6b7280;
            padding: 10px 0;
        }

        .request {
            border-bottom: 1px solid #e5e7eb;
            padding: 12px 0;
        }

        .request:last-child {
            border-bottom: none;
        }

        .request-actions {
            display: flex;
            gap: 10px;
            margin-top: 14px;
        }

        .request-actions form {
            margin: 0;
        }

        @media (max-width: 700px) {

            .navbar {
                padding: 15px 18px;
            }

            .navbar-right {
                gap: 10px;
            }

            .navbar-right span {
                display: none;
            }

            .welcome {
                flex-direction: column;
                align-items: flex-start;
            }

            .grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

</head>

<body>


    <nav class="navbar">

        <a
            href="{{ route('dashboard') }}"
            class="brand"
        >
            SkillSwap
        </a>


        <div class="navbar-right">

            <a href="{{ route('dashboard') }}">
                Dashboard
            </a>

            <a href="{{ route('discover.index') }}">
                Discover
            </a>

            <a href="{{ route('profile.show') }}">
                Profile
            </a>

            <a href="{{ route('settings.edit') }}">
                Settings
            </a>

            <span>
                {{ $user->name }}
            </span>


            <form
                method="POST"
                action="{{ route('logout') }}"
            >
                @csrf

                <button
                    type="submit"
                    class="logout-button"
                >
                    Logout
                </button>

            </form>

        </div>

    </nav>


    <main class="container">


        @if (session('success'))

            <div class="alert alert-success">

                {{ session('success') }}

            </div>

        @endif

        @if (session('info'))

            <div class="alert alert-info">

                {{ session('info') }}

            </div>

        @endif


        <section class="welcome">

            <div>

                <h1>
                    Welcome back, {{ $user->name }}!
                </h1>

                <p>
                    Discover people and exchange skills with the SkillSwap community.
                </p>

            </div>


            <a
                href="{{ route('discover.index') }}"
                class="btn btn-primary"
            >
                Discover People
            </a>

        </section>


        <section class="section">

            <h2>
                Sent Swap Requests
            </h2>


            <div class="card">

                @forelse ($sentSwapRequests as $sentSwapRequest)

                    <div class="request">

                        <strong>
                            With {{ $sentSwapRequest->recipient->name }}
                        </strong>

                        <p>
                            You will teach:
                            {{ $sentSwapRequest->offeredSkill->name }}
                        </p>

                        <p>
                            You want to learn:
                            {{ $sentSwapRequest->requestedSkill->name }}
                        </p>

                        <p>
                            Status: {{ ucfirst($sentSwapRequest->status) }}
                        </p>

                        @if ($sentSwapRequest->message)

                            <p>
                                Message: {{ $sentSwapRequest->message }}
                            </p>

                        @endif

                    </div>

                @empty

                    <div class="empty">
                        No sent requests.
                    </div>

                @endforelse

            </div>

        </section>


        <section class="section">

            <h2>
                Active Swaps
            </h2>


            <div class="card">

                @forelse ($acceptedSwapRequests as $acceptedSwapRequest)

                    <div class="request">

                        <strong>
                            {{ $acceptedSwapRequest->sender->name }}
                            ↔
                            {{ $acceptedSwapRequest->recipient->name }}
                        </strong>

                        <p>
                            {{ $acceptedSwapRequest->sender->name }} teaches:
                            {{ $acceptedSwapRequest->offeredSkill->name }}
                        </p>

                        <p>
                            {{ $acceptedSwapRequest->recipient->name }} teaches:
                            {{ $acceptedSwapRequest->requestedSkill->name }}
                        </p>

                        <p>
                            Status: Accepted
                        </p>

                        @if (! $acceptedSwapRequest->skillSession || $acceptedSwapRequest->skillSession->status === \App\Models\SkillSession::STATUS_CANCELLED)

                            <p>
                                <strong>Discussing schedule</strong>
                            </p>

                        @elseif ($acceptedSwapRequest->skillSession->status === \App\Models\SkillSession::STATUS_PROPOSED)

                            <p>
                                <strong>
                                    {{ $acceptedSwapRequest->skillSession->scheduled_by === $user->id ? 'Schedule proposal pending' : 'Schedule proposal needs your response' }}
                                </strong>
                            </p>

                        @elseif ($acceptedSwapRequest->skillSession->status === \App\Models\SkillSession::STATUS_CONFIRMED)

                            <p>
                                <strong>Session confirmed</strong>
                                —
                                {{ $acceptedSwapRequest->skillSession->scheduled_at->format('F j, Y') }}
                                at
                                {{ $acceptedSwapRequest->skillSession->scheduled_at->format('g:i A') }}
                            </p>

                        @endif

                        <a
                            href="{{ route('swap-requests.chat', $acceptedSwapRequest) }}"
                            class="btn btn-primary"
                        >
                            Open Chat
                        </a>

                    </div>

                @empty

                    <div class="empty">
                        No active swaps yet.
                    </div>

                @endforelse

            </div>

        </section>


        <div class="grid">


            <div class="card">

                <h3>
                    Skill Credits
                </h3>

                <div class="credits">
                    {{ $user->skill_credits }}
                </div>

                <p class="muted">
                    Earn credits by completing SkillSwap sessions.
                </p>

            </div>


            <div class="card">

                <h3>
                    Profile
                </h3>

                <p>
                    <strong>School / Organization</strong>
                </p>

                <p class="muted">
                    {{ $user->school_organization ?: 'School not set' }}
                </p>

                <p>
                    <strong>Program</strong>
                </p>

                <p class="muted">
                    {{ $user->program?->abbreviation ?? $user->program?->name ?? 'Program not set' }}
                </p>

                <p>
                    <strong>Year Level</strong>
                </p>

                <p class="muted">
                    {{ $user->year_level ? $user->year_level.($user->year_level === 1 ? 'st' : ($user->year_level === 2 ? 'nd' : ($user->year_level === 3 ? 'rd' : 'th'))).' Year' : 'Year level not set' }}
                </p>


                <p>
                    <strong>Bio</strong>
                </p>

                <p class="muted">
                    {{ $user->bio }}
                </p>

            </div>


        </div>


        <div class="grid">


            <div class="card">

                <h3>
                    Skills I Can Teach
                </h3>

                <div class="skills">

                    @forelse ($user->teachingSkills as $skill)

                        <span class="skill skill-details">
                            <strong>{{ $skill->name }}</strong>
                            <small>{{ $skill->category?->name ?? 'Uncategorized' }}</small>
                        </span>

                    @empty

                        <p class="muted">
                            No teaching skills added.
                        </p>

                    @endforelse

                </div>

            </div>


            <div class="card">

                <h3>
                    Skills I Want to Learn
                </h3>

                <div class="skills">

                    @forelse ($user->learningSkills as $skill)

                        <span class="skill skill-learning skill-details">
                            <strong>{{ $skill->name }}</strong>
                            <small>{{ $skill->category?->name ?? 'Uncategorized' }}</small>
                        </span>

                    @empty

                        <p class="muted">
                            No learning skills added.
                        </p>

                    @endforelse

                </div>

            </div>


        </div>


        <section class="section">

            <h2>
                Availability
            </h2>


            <div class="card">

                <div class="skills">

                    @forelse ($user->availabilities as $availability)

                        <span class="skill">

                            {{ ucfirst($availability->day) }}

                            -

                            {{ ucfirst($availability->time_period) }}

                        </span>

                    @empty

                        <p class="muted">
                            No availability set.
                        </p>

                    @endforelse

                </div>

            </div>

        </section>


        <section class="section">

            <h2>
                Recommended Matches
            </h2>


            <div class="card">

                @forelse ($recommendedMatches as $match)

                    <p>
                        {{ $match->name }}
                    </p>

                @empty

                    <div class="empty">

                        No recommended matches yet.

                    </div>

                @endforelse

            </div>

        </section>


        <div class="grid">


            <section>

                <h2>
                    Incoming Swap Requests
                </h2>


                <div class="card">

                    @forelse ($swapRequests as $swapRequest)

                        <div class="request">

                            @if (
                                isset($swapRequest->sender) &&
                                isset($swapRequest->offeredSkill) &&
                                isset($swapRequest->requestedSkill)
                            )

                                <strong>
                                    {{ $swapRequest->sender->name }} wants to swap skills with you.
                                </strong>

                                <p>
                                    {{ $swapRequest->sender->name }} will teach you:
                                    {{ $swapRequest->offeredSkill->name }}
                                </p>

                                <p>
                                    You will teach {{ $swapRequest->sender->name }}:
                                    {{ $swapRequest->requestedSkill->name }}
                                </p>

                                <p>
                                    Status: {{ ucfirst($swapRequest->status) }}
                                </p>

                                @if ($swapRequest->message)

                                    <p>
                                        Message: {{ $swapRequest->message }}
                                    </p>

                                @endif

                                <div class="request-actions">

                                    <form
                                        method="POST"
                                        action="{{ route('swap-requests.reject', $swapRequest) }}"
                                    >
                                        @csrf
                                        @method('PATCH')

                                        <button
                                            type="submit"
                                            class="btn btn-danger"
                                        >
                                            Reject
                                        </button>

                                    </form>

                                    <form
                                        method="POST"
                                        action="{{ route('swap-requests.accept', $swapRequest) }}"
                                    >
                                        @csrf
                                        @method('PATCH')

                                        <button
                                            type="submit"
                                            class="btn btn-primary"
                                        >
                                            Accept
                                        </button>

                                    </form>

                                </div>

                            @else

                                <p>
                                    You have a pending swap request.
                                </p>

                            @endif

                        </div>

                    @empty

                        <div class="empty">
                            No pending swap requests.
                        </div>

                    @endforelse

                </div>

            </section>


            <section>

                <h2>
                    Upcoming Sessions
                </h2>


                <div class="card">

                    @forelse ($upcomingSessions as $session)

                        <div class="request">

                            @if ($session->swapRequest->sender_id === $user->id)

                                <strong>
                                    With {{ $session->swapRequest->recipient->name }}
                                </strong>

                                <p>
                                    You teach: {{ $session->swapRequest->offeredSkill->name }}
                                </p>

                                <p>
                                    You learn: {{ $session->swapRequest->requestedSkill->name }}
                                </p>

                            @else

                                <strong>
                                    With {{ $session->swapRequest->sender->name }}
                                </strong>

                                <p>
                                    You teach: {{ $session->swapRequest->requestedSkill->name }}
                                </p>

                                <p>
                                    You learn: {{ $session->swapRequest->offeredSkill->name }}
                                </p>

                            @endif

                            <p>
                                {{ $session->scheduled_at->format('F j, Y') }}
                                at
                                {{ $session->scheduled_at->format('g:i A') }}
                            </p>

                            <p>
                                {{ $session->duration_minutes }} minutes
                            </p>

                            <p>
                                {{ ucwords(str_replace('_', ' ', $session->meeting_type)) }}
                            </p>

                            @if ($session->meeting_details)

                                <p>
                                    Meeting details: {{ $session->meeting_details }}
                                </p>

                            @endif

                            <p>
                                Status: {{ ucfirst($session->status) }}
                            </p>

                        </div>

                    @empty

                        <div class="empty">
                            No upcoming sessions.
                        </div>

                    @endforelse

                </div>

            </section>


        </div>

        <section class="section">

            <h2>
                Awaiting Completion
            </h2>

            <div class="card">

                @forelse ($awaitingCompletionSessions as $session)

                    <div class="request">

                        <strong>
                            With {{ $session->swapRequest->sender_id === $user->id ? $session->swapRequest->recipient->name : $session->swapRequest->sender->name }}
                        </strong>

                        <p>
                            Session started {{ $session->scheduled_at->format('F j, Y') }}
                            at {{ $session->scheduled_at->format('g:i A') }}.
                        </p>

                        <p>
                            @if ($session->swapRequest->sender_id === $user->id && $session->sender_confirmed_at)
                                You confirmed completion. Waiting for the other participant.
                            @elseif ($session->swapRequest->recipient_id === $user->id && $session->recipient_confirmed_at)
                                You confirmed completion. Waiting for the other participant.
                            @elseif ($session->sender_confirmed_at || $session->recipient_confirmed_at)
                                The other participant confirmed. Your confirmation is needed.
                            @else
                                Both completion confirmations are still needed.
                            @endif
                        </p>

                        <a
                            href="{{ route('swap-requests.chat', $session->swapRequest) }}"
                            class="btn btn-primary"
                        >
                            Open Chat
                        </a>

                    </div>

                @empty

                    <div class="empty">
                        No sessions awaiting completion.
                    </div>

                @endforelse

            </div>

        </section>

        <section class="section">

            <h2>
                Completed Swaps
            </h2>

            <div class="card">

                @forelse ($completedSwapRequests as $completedSwapRequest)

                    <div class="request">

                        @if ($completedSwapRequest->sender_id === $user->id)

                            <strong>With {{ $completedSwapRequest->recipient->name }}</strong>

                            <p>You taught: {{ $completedSwapRequest->offeredSkill->name }}</p>
                            <p>You learned: {{ $completedSwapRequest->requestedSkill->name }}</p>

                        @else

                            <strong>With {{ $completedSwapRequest->sender->name }}</strong>

                            <p>You taught: {{ $completedSwapRequest->requestedSkill->name }}</p>
                            <p>You learned: {{ $completedSwapRequest->offeredSkill->name }}</p>

                        @endif

                        <p>
                            Completed: {{ $completedSwapRequest->skillSession->completed_at->format('F j, Y') }}
                        </p>

                        <p><strong>✓ Completed</strong></p>
                        <p>+1 Skill Credit earned</p>

                        @if ($completedSwapRequest->skillSession->reviews->isNotEmpty())

                            <p><strong>✓ Review Submitted</strong></p>
                            <p>
                                Your rating:
                                <span style="color: #d97706;">
                                    {{ str_repeat('★', $completedSwapRequest->skillSession->reviews->first()->rating) }}{{ str_repeat('☆', 5 - $completedSwapRequest->skillSession->reviews->first()->rating) }}
                                </span>
                            </p>

                        @else

                            <a
                                href="{{ route('reviews.create', $completedSwapRequest->skillSession) }}"
                                class="btn btn-primary"
                            >
                                Leave Review
                            </a>

                        @endif

                        <a
                            href="{{ route('swap-requests.chat', $completedSwapRequest) }}"
                            class="btn btn-primary"
                        >
                            View Conversation
                        </a>

                    </div>

                @empty

                    <div class="empty">
                        No completed swaps yet.
                    </div>

                @endforelse

            </div>

        </section>


    </main>

</body>

</html>
