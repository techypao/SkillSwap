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

        .empty-state {
            text-align: center;
            padding: 40px 20px;
        }

        .empty-state h3 {
            margin-top: 0;
        }

        @media (max-width: 750px) {

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


        <section class="card search-card">

            <form
                method="GET"
                action="{{ route('discover.index') }}"
                class="search-form"
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

                    <a href="{{ route('discover.index') }}">
                        Clear Search
                    </a>

                </div>

            @endif

        </section>


        <div class="results-header">

            <h2>
                People
            </h2>

            <span class="muted">

                {{ $users->count() }}

                {{ $users->count() === 1 ? 'user' : 'users' }}

            </span>

        </div>


        <section class="user-list">

            @forelse ($users as $user)


                <article class="user-card">


                    <div class="user-top">


                        <div>

                            <h3 class="user-name">
                                {{ $user->name }}
                            </h3>

                            <p class="user-school">

                                {{ $user->school_organization }}

                            </p>

                        </div>


                        <a
                            href="{{ route('matches.show', $user) }}"
                            class="btn btn-primary"
                        >
                            Check Match
                        </a>


                    </div>


                    @if ($user->bio)

                        <p class="bio">
                            {{ $user->bio }}
                        </p>

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

        </section>


    </main>

</body>

</html>