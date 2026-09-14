<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Discover - SkillSwap</title>

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
            text-decoration: none;
            color: #374151;
        }

        .navbar-right a.active {
            color: #2563eb;
            font-weight: bold;
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

        .page-header {
            margin-bottom: 25px;
        }

        .page-header h1 {
            margin: 0 0 8px;
            font-size: 30px;
        }

        .page-header p {
            margin: 0;
            color: #6b7280;
        }

        .card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 22px;
        }

        .search-card {
            margin-bottom: 25px;
        }

        .search-form {
            display: grid;
            grid-template-columns: 1fr 220px auto;
            gap: 12px;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .form-group label {
            font-size: 13px;
            font-weight: bold;
        }

        .form-control {
            width: 100%;
            padding: 11px 12px;

            border: 1px solid #d1d5db;
            border-radius: 8px;

            font-size: 14px;
            background: white;
        }

        .form-control:focus {
            outline: none;
            border-color: #2563eb;
        }

        .btn {
            display: inline-block;

            padding: 11px 16px;

            border: none;
            border-radius: 8px;

            font-size: 14px;
            font-weight: bold;

            text-decoration: none;
            cursor: pointer;
        }

        .btn-primary {
            background: #2563eb;
            color: white;
        }

        .btn-primary:hover {
            background: #1d4ed8;
        }

        .btn-secondary {
            background: white;
            color: #374151;
            border: 1px solid #d1d5db;
        }

        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;

            margin-bottom: 15px;
        }

        .results-header h2 {
            margin: 0;
            font-size: 20px;
        }

        .muted {
            color: #6b7280;
        }

        .search-info {
            margin-top: 15px;
            font-size: 14px;
        }

        .search-info a {
            color: #2563eb;
            text-decoration: none;
            margin-left: 8px;
        }

        .user-list {
            display: grid;
            gap: 18px;
        }

        .user-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 22px;
        }

        .user-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
        }

        .user-name {
            margin: 0 0 6px;
            font-size: 20px;
        }

        .user-school {
            margin: 0;
            color: #6b7280;
            font-size: 14px;
        }

        .rating {
            color: #b45309;
            font-size: 14px;
            margin: 7px 0 0;
        }

        .bio {
            margin: 16px 0;
            color: #4b5563;
            line-height: 1.6;
        }

        .skills-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;

            margin-top: 18px;
        }

        .skill-section h4 {
            margin: 0 0 10px;
            font-size: 14px;
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

        .skill small {
            color: #6b7280;
            display: block;
            margin-top: 2px;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
        }

        .empty-state h3 {
            margin-top: 0;
        }

        .match-toggle {
            text-align: center;
            margin-bottom: 25px;
        }

        .btn-check-match {
            padding: 16px 38px;
            border-radius: 999px;
            font-size: 18px;
            box-shadow: 0 8px 20px rgba(37, 99, 235, .25);
            user-select: none;
        }

        /* Matches Only is a CSS-only toggle: no reload and no JavaScript. */
        .matches-toggle-input {
            position: absolute;
            width: 1px;
            height: 1px;
            opacity: 0;
            pointer-events: none;
        }

        .matches-toggle-input:focus-visible ~ .match-toggle .btn-check-match {
            outline: 3px solid #93c5fd;
            outline-offset: 3px;
        }

        .when-matches,
        .matches-banner,
        .compatibility,
        .matches-empty {
            display: none;
        }

        .matches-toggle-input:checked ~ .match-toggle .btn-check-match,
        .matches-toggle-input:checked ~ .match-toggle .btn-check-match:hover {
            background: white;
            color: #2563eb;
            box-shadow: inset 0 0 0 2px #2563eb;
        }

        .matches-toggle-input:checked ~ * .when-everyone {
            display: none;
        }

        .matches-toggle-input:checked ~ * .when-matches {
            display: inline;
        }

        .matches-toggle-input:checked ~ .matches-banner {
            display: flex;
        }

        .matches-toggle-input:checked ~ .user-list .user-card:not(.is-match) {
            display: none;
        }

        .matches-toggle-input:checked ~ .user-list .compatibility {
            display: grid;
        }

        .matches-toggle-input:checked ~ .user-list .matches-empty {
            display: block;
        }

        .matches-banner {
            justify-content: space-between;
            align-items: center;
            gap: 15px;

            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 14px;
            padding: 16px 20px;
            margin-bottom: 25px;
        }

        .matches-banner strong {
            color: #1d4ed8;
            font-size: 18px;
        }

        .matches-banner p {
            margin: 4px 0 0;
            color: #4b5563;
            font-size: 14px;
        }

        .compatibility {
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            align-items: start;

            background: #f9fafb;
            border-radius: 10px;
            padding: 14px 16px;
            margin-top: 18px;
        }

        @media (max-width: 750px) {

            .matches-banner {
                flex-direction: column;
                align-items: flex-start;
            }

            .compatibility {
                grid-template-columns: 1fr;
            }

            .navbar {
                padding: 15px 18px;
            }

            .navbar-right {
                gap: 10px;
            }

            .navbar-right span {
                display: none;
            }

            .search-form {
                grid-template-columns: 1fr;
            }

            .user-top {
                flex-direction: column;
            }

            .skills-grid {
                grid-template-columns: 1fr;
            }

            .user-top .btn {
                width: 100%;
                text-align: center;
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

            <a
                href="{{ route('discover.index') }}"
                class="active"
            >
                Discover
            </a>

            <span>
                {{ auth()->user()->name }}
            </span>

            @include('partials.notification-bell')


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


        <div class="page-header">

            <h1>
                Discover
            </h1>

            <p>
                Find people who can teach the skills you want to learn.
            </p>

        </div>


        {{-- Belongs to the search form, so searching keeps the current mode. --}}
        <input type="checkbox" id="matches-toggle" class="matches-toggle-input" name="mode" value="matches" form="discover-search" aria-label="Show only two-way matches" @checked($matchesOnly)>

        <div class="match-toggle">

            <label
                for="matches-toggle"
                class="btn btn-primary btn-check-match"
            >
                <span class="when-everyone">Check Match</span>
                <span class="when-matches">Show All</span>
            </label>

        </div>


        <div class="matches-banner">

            <div>
                <strong>
                    ♥ Matches Only
                </strong>

                <p>
                    {{ $matchCount }} {{ $matchCount === 1 ? 'match' : 'matches' }} found —
                    people who teach a skill you want to learn and want to learn a skill you teach.
                </p>
            </div>

        </div>


        <section class="card search-card">

            <form
                method="GET"
                action="{{ route('discover.index') }}"
                class="search-form"
                id="discover-search"
            >

                <div class="form-group">

                    <label for="search">
                        Search Skill
                    </label>

                    <input
                        type="text"
                        id="search"
                        name="search"
                        class="form-control"
                        value="{{ $search }}"
                        placeholder="Example: Laravel, UI/UX, Figma"
                    >

                </div>


                <div class="form-group">

                    <label for="type">
                        Search In
                    </label>

                    <select
                        id="type"
                        name="type"
                        class="form-control"
                    >

                        <option
                            value="teach"
                            {{ $type === 'teach' ? 'selected' : '' }}
                        >
                            Can Teach
                        </option>

                        <option
                            value="learn"
                            {{ $type === 'learn' ? 'selected' : '' }}
                        >
                            Wants to Learn
                        </option>

                        <option
                            value="all"
                            {{ $type === 'all' ? 'selected' : '' }}
                        >
                            All Skills
                        </option>

                    </select>

                </div>


                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Search
                </button>

            </form>


            @if ($search)

                <div class="search-info">

                    Showing results for:

                    <strong>
                        "{{ $search }}"
                    </strong>

                    <a href="{{ route('discover.index', $matchesOnly ? ['mode' => 'matches'] : []) }}">
                        Clear Search
                    </a>

                </div>

            @endif

        </section>


        <div class="results-header">

            <h2>
                <span class="when-everyone">People</span>
                <span class="when-matches">Your Matches</span>
            </h2>

            <span class="muted">
                <span class="when-everyone">{{ $users->count() }} {{ $users->count() === 1 ? 'user' : 'users' }}</span>
                <span class="when-matches">{{ $matchCount }} {{ $matchCount === 1 ? 'match' : 'matches' }}</span>
            </span>

        </div>


        <section class="user-list">

            @forelse ($users as $user)


                <article class="user-card{{ $compatibilities[$user->id]['isMutualMatch'] ? ' is-match' : '' }}">


                    <div class="user-top">


                        <div>

                            <h3 class="user-name">
                                {{ $user->name }}
                            </h3>

                            <p class="user-school">
                                {{ $user->program?->abbreviation ?? $user->program_display_name ?? 'Program not set' }}
                                ·
                                {{ $user->year_level ? $user->year_level.($user->year_level === 1 ? 'st' : ($user->year_level === 2 ? 'nd' : ($user->year_level === 3 ? 'rd' : 'th'))).' Year' : 'Year level not set' }}
                            </p>

                            <p class="user-school">
                                {{ $user->school_display_name ?: 'School not set' }}
                            </p>

                            <p class="rating">
                                @if ($user->reviews_received_count > 0)
                                    ★ {{ number_format((float) $user->reviews_received_avg_rating, 1) }}
                                    ({{ $user->reviews_received_count }} {{ $user->reviews_received_count === 1 ? 'review' : 'reviews' }})
                                @else
                                    No reviews yet
                                @endif
                            </p>

                        </div>


                        <a
                            href="{{ route('matches.show', $user) }}"
                            class="btn btn-primary"
                        >
                            View Profile
                        </a>


                    </div>


                    @if ($user->bio)

                        <p class="bio">
                            {{ $user->bio }}
                        </p>

                    @endif


                    @php
                        $compatibility = $compatibilities[$user->id];
                    @endphp

                    @if ($compatibility['isMutualMatch'])

                        <div class="compatibility">

                            <div class="skill-section">
                                <h4>You Can Learn</h4>
                                <div class="skills">
                                    @foreach ($compatibility['skillsYouCanLearn'] as $skill)
                                        <span class="skill">{{ $skill->name }}</span>
                                    @endforeach
                                </div>
                            </div>

                            <div class="skill-section">
                                <h4>You Can Teach</h4>
                                <div class="skills">
                                    @foreach ($compatibility['skillsYouCanTeach'] as $skill)
                                        <span class="skill skill-learning">{{ $skill->name }}</span>
                                    @endforeach
                                </div>
                            </div>

                        </div>

                    @endif


                    <div class="skills-grid">


                        <div class="skill-section">

                            <h4>
                                Can Teach
                            </h4>


                            <div class="skills">

                                @forelse ($user->teachingSkills as $skill)

                                    <span class="skill">
                                        {{ $skill->name }}
                                    </span>

                                @empty

                                    <span class="muted">
                                        No teaching skills listed.
                                    </span>

                                @endforelse

                            </div>

                        </div>


                        <div class="skill-section">

                            <h4>
                                Wants to Learn
                            </h4>


                            <div class="skills">

                                @forelse ($user->learningSkills as $skill)

                                    <span class="skill skill-learning">
                                        {{ $skill->name }}
                                    </span>

                                @empty

                                    <span class="muted">
                                        No learning skills listed.
                                    </span>

                                @endforelse

                            </div>

                        </div>


                    </div>


                </article>


            @empty


                <div class="card empty-state">

                    @if ($search)

                        <h3>
                            No users found
                        </h3>

                        <p class="muted">
                            We couldn't find users matching
                            "{{ $search }}".
                        </p>

                        <a
                            href="{{ route('discover.index') }}"
                            class="btn btn-secondary"
                        >
                            Clear Search
                        </a>

                    @else

                        <h3>
                            No users available
                        </h3>

                        <p class="muted">
                            There are no other SkillSwap users to discover yet.
                        </p>

                    @endif

                </div>


            @endforelse


            @if ($users->isNotEmpty() && $matchCount === 0)

                <div class="card empty-state matches-empty">

                    <h3>
                        No two-way matches found
                    </h3>

                    <p class="muted">
                        {{ $search ? 'No one matching "'.$search.'" is' : 'No one is' }}
                        both teaching a skill you want to learn and learning a skill you teach yet.
                    </p>

                    <label
                        for="matches-toggle"
                        class="btn btn-secondary"
                    >
                        Show All
                    </label>

                </div>

            @endif

        </section>


    </main>

</body>

</html>
