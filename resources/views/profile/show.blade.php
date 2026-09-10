<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>My Profile - SkillSwap</title>

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

        .card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 25px;
            margin-bottom: 25px;
        }

        .card h2 {
            margin: 0 0 16px;
            font-size: 18px;
        }

        .card h3 {
            margin: 0 0 12px;
            font-size: 16px;
        }

        .muted {
            color: #6b7280;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .avatar {
            width: 88px;
            height: 88px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid #e5e7eb;
        }

        .avatar-placeholder {
            width: 88px;
            height: 88px;
            border-radius: 50%;
            background: #e0e7ff;
            color: #3730a3;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 32px;
            font-weight: bold;
        }

        .profile-header h1 {
            margin: 0 0 6px;
            font-size: 26px;
        }

        .profile-header p {
            margin: 0 0 4px;
        }

        .header-actions {
            margin-left: auto;
            display: flex;
            gap: 10px;
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

        .btn-secondary {
            background: white;
            color: #374151;
            border: 1px solid #d1d5db;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }

        .credits {
            font-size: 40px;
            font-weight: bold;
            color: #2563eb;
            margin: 6px 0;
        }

        .rating-summary {
            font-size: 20px;
            color: #f59e0b;
            margin: 6px 0;
        }

        .skills {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .skill-tag {
            background: #eef2ff;
            color: #3730a3;
            border-radius: 999px;
            padding: 6px 12px;
            font-size: 13px;
        }

        .skill-tag small {
            color: #6366f1;
        }

        .availability-tag {
            background: #f3f4f6;
            color: #374151;
            border-radius: 999px;
            padding: 6px 12px;
            font-size: 13px;
            text-transform: capitalize;
        }

        .review-item {
            border-top: 1px solid #e5e7eb;
            padding: 16px 0;
        }

        .review-item:first-of-type {
            border-top: none;
            padding-top: 0;
        }

        .review-item p {
            margin: 6px 0;
        }

        .breakdown-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 6px;
            font-size: 13px;
        }

        .breakdown-bar {
            flex: 1;
            height: 8px;
            background: #e5e7eb;
            border-radius: 999px;
            overflow: hidden;
        }

        .breakdown-fill {
            height: 8px;
            background: #f59e0b;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .table th,
        .table td {
            text-align: left;
            padding: 10px 8px;
            border-bottom: 1px solid #e5e7eb;
        }

        .table th {
            color: #6b7280;
            font-weight: bold;
        }

        .amount-positive {
            color: #166534;
            font-weight: bold;
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


    <div class="container">


        <!-- PROFILE HEADER -->

        <section class="card">

            <div class="profile-header">

                @if ($user->profile_picture)
                    <img
                        src="{{ asset('storage/'.$user->profile_picture) }}"
                        alt="{{ $user->name }}"
                        class="avatar"
                    >
                @else
                    <div class="avatar-placeholder">
                        {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                    </div>
                @endif


                <div>
                    <h1>
                        {{ $user->name }}
                    </h1>

                    <p class="muted">
                        {{ $user->email }}
                    </p>

                    <p class="muted">
                        {{ $user->program?->name ?: 'Program not set' }}
                        @if ($user->year_level)
                            &middot; Year {{ $user->year_level }}
                        @endif
                    </p>

                    <p class="muted">
                        {{ $user->school_organization ?: 'School not set' }}
                    </p>
                </div>


                <div class="header-actions">
                    <a
                        href="{{ route('settings.edit') }}"
                        class="btn btn-primary"
                    >
                        Edit Profile
                    </a>

                    <a
                        href="{{ route('settings.skills.edit') }}"
                        class="btn btn-secondary"
                    >
                        Edit Skills
                    </a>
                </div>

            </div>


            <h3 style="margin-top: 25px;">
                About Me
            </h3>

            <p class="muted">
                {{ $user->bio ?: 'You have not written a bio yet.' }}
            </p>

        </section>


        <div class="grid">


            <!-- SKILL CREDITS -->

            <div class="card">

                <h2>
                    Skill Credits
                </h2>

                <div class="credits">
                    {{ $user->skill_credits }}
                </div>

                <p class="muted">
                    {{ $creditsEarned }} credit{{ $creditsEarned === 1 ? '' : 's' }} earned from completed sessions.
                </p>

            </div>


            <!-- RATING -->

            <div class="card">

                <h2>
                    Rating
                </h2>

                @if ($user->reviews_received_count > 0)

                    @php
                        $averageRating = round((float) $user->reviews_received_avg_rating, 1);
                    @endphp

                    <div class="rating-summary">
                        {{ str_repeat('★', (int) round($averageRating)) }}{{ str_repeat('☆', 5 - (int) round($averageRating)) }}
                    </div>

                    <p>
                        <strong>{{ number_format($averageRating, 1) }}</strong> out of 5
                        <span class="muted">
                            ({{ $user->reviews_received_count }} review{{ $user->reviews_received_count === 1 ? '' : 's' }})
                        </span>
                    </p>

                    @foreach ([5, 4, 3, 2, 1] as $star)
                        @php
                            $starCount = $ratingBreakdown[$star] ?? 0;
                            $starPercent = (int) round($starCount / $user->reviews_received_count * 100);
                        @endphp

                        <div class="breakdown-row">
                            <span>{{ $star }} ★</span>

                            <span class="breakdown-bar">
                                <span
                                    class="breakdown-fill"
                                    style="width: {{ $starPercent }}%;"
                                ></span>
                            </span>

                            <span class="muted">{{ $starCount }}</span>
                        </div>
                    @endforeach

                @else

                    <p class="muted">
                        No reviews yet. Complete a session to get your first review.
                    </p>

                @endif

            </div>


            <!-- AVAILABILITY -->

            <div class="card">

                <h2>
                    Availability
                </h2>

                @if ($user->availabilities->isNotEmpty())

                    <div class="skills">
                        @foreach ($user->availabilities as $availability)
                            <span class="availability-tag">
                                {{ $availability->day }} {{ $availability->time_period }}
                            </span>
                        @endforeach
                    </div>

                @else

                    <p class="muted">
                        No availability set.
                    </p>

                @endif

            </div>

        </div>


        <!-- SKILLS -->

        <div class="grid">

            <div class="card">

                <h2>
                    Skills I Can Teach
                </h2>

                @if ($user->teachingSkills->isNotEmpty())

                    <div class="skills">
                        @foreach ($user->teachingSkills as $skill)
                            <span class="skill-tag">
                                {{ $skill->name }}
                                <small>{{ $skill->category?->name ?? 'Other' }}</small>
                            </span>
                        @endforeach
                    </div>

                @else

                    <p class="muted">
                        You have not added any teaching skills yet.
                    </p>

                @endif

            </div>


            <div class="card">

                <h2>
                    Skills I Want To Learn
                </h2>

                @if ($user->learningSkills->isNotEmpty())

                    <div class="skills">
                        @foreach ($user->learningSkills as $skill)
                            <span class="skill-tag">
                                {{ $skill->name }}
                                <small>{{ $skill->category?->name ?? 'Other' }}</small>
                            </span>
                        @endforeach
                    </div>

                @else

                    <p class="muted">
                        You have not added any learning goals yet.
                    </p>

                @endif

            </div>

        </div>


        <!-- CREDIT HISTORY -->

        <section class="card">

            <h2>
                Recent Credit Activity
            </h2>

            @if ($user->creditTransactions->isNotEmpty())

                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Reason</th>
                            <th>Credits</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($user->creditTransactions as $creditTransaction)
                            <tr>
                                <td>
                                    {{ $creditTransaction->created_at->format('F j, Y') }}
                                </td>

                                <td>
                                    {{ ucfirst(str_replace('_', ' ', $creditTransaction->reason)) }}
                                </td>

                                <td class="{{ $creditTransaction->amount > 0 ? 'amount-positive' : '' }}">
                                    {{ $creditTransaction->amount > 0 ? '+' : '' }}{{ $creditTransaction->amount }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

            @else

                <p class="muted">
                    You have not earned any skill credits yet.
                </p>

            @endif

        </section>


        <!-- REVIEWS -->

        <section class="card">

            <h2>
                Reviews About Me
            </h2>

            @forelse ($user->reviewsReceived as $review)

                <article class="review-item">

                    <strong>
                        {{ $review->reviewer->name }}
                    </strong>

                    <p class="rating-summary">
                        {{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}
                    </p>

                    @if ($review->comment)
                        <p>
                            {{ $review->comment }}
                        </p>
                    @endif

                    <span class="muted">
                        {{ $review->created_at->format('F j, Y') }}
                    </span>

                </article>

            @empty

                <p class="muted">
                    No one has reviewed you yet.
                </p>

            @endforelse

        </section>


    </div>

</body>

</html>
