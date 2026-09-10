<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Propose Skill Swap Session - SkillSwap</title>

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

        .navbar-right a {
            color: #374151;
            text-decoration: none;
        }

        .logout-button {
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: white;
            cursor: pointer;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 30px 20px 60px;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #2563eb;
            text-decoration: none;
        }

        .card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 24px;
            margin-bottom: 20px;
        }

        .card h1,
        .card h2 {
            margin-top: 0;
        }

        .exchange-grid,
        .availability-grid,
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
        }

        .exchange-item,
        .availability-card {
            background: #f9fafb;
            border-radius: 10px;
            padding: 16px;
        }

        .exchange-item strong,
        .availability-card strong {
            display: block;
            margin-bottom: 8px;
        }

        .availability-card ul {
            margin: 0;
            padding-left: 20px;
        }

        .muted {
            color: #6b7280;
        }

        .alert-error {
            background: #fef2f2;
            border-radius: 10px;
            color: #b91c1c;
            margin-bottom: 20px;
            padding: 14px 16px;
        }

        .alert-error ul {
            margin: 0;
            padding-left: 20px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label,
        .form-label {
            display: block;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 7px;
        }

        .form-control {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 10px 12px;
            background: white;
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        .radio-options {
            display: flex;
            gap: 20px;
        }

        .btn {
            border: none;
            border-radius: 8px;
            padding: 11px 17px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
        }

        .btn-primary {
            background: #2563eb;
            color: white;
        }

        @media (max-width: 700px) {
            .navbar {
                padding: 15px 18px;
            }

            .navbar-right span {
                display: none;
            }

            .exchange-grid,
            .availability-grid,
            .form-grid {
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

            <a href="{{ route('dashboard') }}">Dashboard</a>

            <span>{{ auth()->user()->name }}</span>

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

        <a
            href="{{ route('swap-requests.chat', $swapRequest) }}"
            class="back-link"
        >
            ← Back to Swap Chat
        </a>

        @if ($errors->any())

            <div class="alert-error">

                <ul>

                    @foreach ($errors->all() as $error)

                        <li>{{ $error }}</li>

                    @endforeach

                </ul>

            </div>

        @endif

        <section class="card">

            <h1>Skill Swap Session</h1>

            <p class="muted">
                Propose a time for your accepted exchange with {{ $otherUser->name }}.
            </p>

            <div class="exchange-grid">

                <div class="exchange-item">
                    <strong>You teach</strong>
                    {{ $skillYouTeach->name }}
                </div>

                <div class="exchange-item">
                    <strong>You learn</strong>
                    {{ $skillYouLearn->name }}
                </div>

            </div>

        </section>

        <section class="card">

            <h2>Availability Guidance</h2>

            <p class="muted">
                These broad availability periods are informational and do not restrict scheduling.
            </p>

            <div class="availability-grid">

                <div class="availability-card">

                    <strong>Your availability</strong>

                    @if ($currentUser->availabilities->isNotEmpty())

                        <ul>

                            @foreach ($currentUser->availabilities as $availability)

                                <li>
                                    {{ ucfirst($availability->day) }}
                                    —
                                    {{ ucfirst($availability->time_period) }}
                                </li>

                            @endforeach

                        </ul>

                    @else

                        <span class="muted">No availability provided.</span>

                    @endif

                </div>

                <div class="availability-card">

                    <strong>{{ $otherUser->name }}'s availability</strong>

                    @if ($otherUser->availabilities->isNotEmpty())

                        <ul>

                            @foreach ($otherUser->availabilities as $availability)

                                <li>
                                    {{ ucfirst($availability->day) }}
                                    —
                                    {{ ucfirst($availability->time_period) }}
                                </li>

                            @endforeach

                        </ul>

                    @else

                        <span class="muted">No availability provided.</span>

                    @endif

                </div>

            </div>

        </section>

        <section class="card">

            <h2>Propose a Session</h2>

            <form
                method="POST"
                action="{{ route('skill-sessions.store', $swapRequest) }}"
            >
                @csrf

                <div class="form-grid">

                    <div class="form-group">

                        <label for="date">Date</label>

                        <input
                            id="date"
                            type="date"
                            name="date"
                            value="{{ old('date') }}"
                            min="{{ now()->format('Y-m-d') }}"
                            class="form-control"
                            required
                        >

                    </div>

                    <div class="form-group">

                        <label for="time">Time</label>

                        <input
                            id="time"
                            type="time"
                            name="time"
                            value="{{ old('time') }}"
                            class="form-control"
                            required
                        >

                    </div>

                </div>

                <div class="form-group">

                    <label for="duration_minutes">Duration (minutes)</label>

                    <input
                        type="number"
                        id="duration_minutes"
                        name="duration_minutes"
                        class="form-control"
                        value="{{ old('duration_minutes', 60) }}"
                        min="{{ \App\Models\SkillSession::MIN_DURATION_MINUTES }}"
                        max="{{ \App\Models\SkillSession::MAX_DURATION_MINUTES }}"
                        step="1"
                        list="duration_suggestions"
                        required
                    >

                    <datalist id="duration_suggestions">
                        @foreach ($suggestedDurations as $duration)
                            <option value="{{ $duration }}">{{ $duration }} minutes</option>
                        @endforeach
                    </datalist>

                    <small class="muted">
                        Any length from {{ \App\Models\SkillSession::MIN_DURATION_MINUTES }}
                        to {{ \App\Models\SkillSession::MAX_DURATION_MINUTES }} minutes.
                    </small>

                </div>

                <div class="form-group">

                    <span class="form-label">Meeting type</span>

                    <div class="radio-options">

                        <label>
                            <input
                                type="radio"
                                name="meeting_type"
                                value="online"
                                @checked(old('meeting_type', 'online') === 'online')
                            >
                            Online
                        </label>

                        <label>
                            <input
                                type="radio"
                                name="meeting_type"
                                value="in_person"
                                @checked(old('meeting_type') === 'in_person')
                            >
                            In Person
                        </label>

                    </div>

                </div>

                <div class="form-group">

                    <label for="meeting_details">
                        Meeting link, location, or details (optional)
                    </label>

                    <textarea
                        id="meeting_details"
                        name="meeting_details"
                        class="form-control"
                        maxlength="1000"
                        placeholder="Google Meet link or FEU Tech Library"
                    >{{ old('meeting_details') }}</textarea>

                </div>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Send Proposal
                </button>

            </form>

        </section>

    </main>

</body>

</html>
