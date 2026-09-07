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
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
        }

        .btn-primary {
            background: #2563eb;
            color: white;
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

            <div class="card" style="margin-bottom: 20px;">

                {{ session('success') }}

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
                    {{ $user->school_organization }}
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

                        <span class="skill">
                            {{ $skill->name }}
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

                        <span class="skill skill-learning">
                            {{ $skill->name }}
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
                    Swap Requests
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
                                    {{ $swapRequest->sender->name }}
                                </strong>

                                <p>
                                    They can teach:
                                    {{ $swapRequest->offeredSkill->name }}
                                </p>

                                <p>
                                    They want to learn:
                                    {{ $swapRequest->requestedSkill->name }}
                                </p>

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

                        <p>
                            Upcoming session
                        </p>

                    @empty

                        <div class="empty">
                            No upcoming sessions.
                        </div>

                    @endforelse

                </div>

            </section>


        </div>


    </main>

</body>

</html>