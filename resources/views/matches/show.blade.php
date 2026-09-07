<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Match with {{ $user->name }} - SkillSwap</title>

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

        /* =========================
           NAVBAR
        ========================= */

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

        /* =========================
           GENERAL
        ========================= */

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

            font-size: 14px;
            font-weight: 500;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 24px;
        }

        .muted {
            color: #6b7280;
        }

        /* =========================
           USER HEADER
        ========================= */

        .profile-header {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 14px;

            padding: 26px;
            margin-bottom: 20px;

            display: flex;
            justify-content: space-between;
            align-items: center;

            gap: 20px;
        }

        .profile-header h1 {
            margin: 0 0 7px;
            font-size: 27px;
        }

        .school {
            margin: 0 0 10px;
            color: #6b7280;
            font-size: 14px;
        }

        .bio {
            margin: 0;
            color: #4b5563;
            line-height: 1.6;
        }

        .match-badge {
            white-space: nowrap;

            padding: 9px 14px;
            border-radius: 20px;

            font-size: 13px;
            font-weight: bold;
        }

        .match-success {
            background: #dcfce7;
            color: #15803d;
        }

        .match-none {
            background: #f3f4f6;
            color: #4b5563;
        }

        /* =========================
           SCORE
        ========================= */

        .score-card {
            text-align: center;
            margin-bottom: 20px;
        }

        .score {
            font-size: 48px;
            font-weight: bold;
            color: #2563eb;

            margin-bottom: 5px;
        }

        .score-label {
            color: #6b7280;
            font-size: 14px;
        }

        /* =========================
           SKILL EXCHANGE
        ========================= */

        .exchange-title {
            margin: 30px 0 15px;
            font-size: 20px;
        }

        .exchange-grid {
            display: grid;
            grid-template-columns: 1fr 60px 1fr;
            gap: 15px;
            align-items: stretch;
        }

        .exchange-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 22px;
        }

        .exchange-card h3 {
            margin: 0 0 7px;
            font-size: 18px;
        }

        .exchange-card p {
            margin: 0 0 15px;
            font-size: 13px;
            color: #6b7280;
        }

        .exchange-icon {
            display: flex;
            justify-content: center;
            align-items: center;

            font-size: 30px;
            color: #9ca3af;
        }

        /* =========================
           SKILL BADGES
        ========================= */

        .skills {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .skill {
            display: inline-block;

            padding: 7px 11px;
            border-radius: 20px;

            font-size: 13px;

            background: #eff6ff;
            color: #1d4ed8;
        }

        .skill-learning {
            background: #f0fdf4;
            color: #15803d;
        }

        /* =========================
           READY TO SWAP
        ========================= */

        .swap-card {
            margin-top: 25px;
        }

        .swap-card h2 {
            margin-top: 0;
            font-size: 20px;
        }

        .swap-card p {
            color: #6b7280;
            line-height: 1.6;
        }

        .btn {
            display: inline-block;

            border: none;
            border-radius: 8px;

            padding: 11px 17px;

            font-size: 14px;
            font-weight: bold;

            text-decoration: none;
            cursor: pointer;
        }

        .btn-primary {
            background: #2563eb;
            color: white;
        }

        .btn-disabled {
            background: #e5e7eb;
            color: #9ca3af;
            cursor: not-allowed;
        }

        /* =========================
           NON-MATCH
        ========================= */

        .reason-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;

            margin-top: 20px;
        }

        .reason-card h3 {
            margin-top: 0;
            font-size: 17px;
        }

        .reason-message {
            margin-top: 20px;
        }

        .reason-message h3 {
            margin-top: 0;
        }

        .reason-message p {
            margin-bottom: 0;
            color: #6b7280;
            line-height: 1.6;
        }

        /* =========================
           RESPONSIVE
        ========================= */

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

            .profile-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .exchange-grid {
                grid-template-columns: 1fr;
            }

            .exchange-icon {
                transform: rotate(90deg);
                min-height: 40px;
            }

            .reason-grid {
                grid-template-columns: 1fr;
            }
        }

    </style>

</head>

<body>


    <!-- NAVBAR -->

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


    <!-- MAIN CONTENT -->

    <main class="container">


        <a
            href="{{ route('discover.index') }}"
            class="back-link"
        >
            ← Back to Discover
        </a>


        <!-- USER INFORMATION -->

        <section class="profile-header">

            <div>

                <h1>
                    {{ $user->name }}
                </h1>


                <p class="school">
                    {{ $user->school_organization }}
                </p>


                @if ($user->bio)

                    <p class="bio">
                        {{ $user->bio }}
                    </p>

                @endif

            </div>


            @if ($isMutualMatch)

                <span class="match-badge match-success">
                    ✓ Mutual Match
                </span>

            @else

                <span class="match-badge match-none">
                    Not a Mutual Match
                </span>

            @endif

        </section>



        @if ($isMutualMatch)


            <!-- MATCH SCORE -->

            <section class="card score-card">

                <div class="score">
                    {{ $matchScore }}%
                </div>

                <div class="score-label">
                    Skill Match Score
                </div>

            </section>



            <!-- SKILL EXCHANGE -->

            <h2 class="exchange-title">
                Your Skill Exchange
            </h2>


            <div class="exchange-grid">


                <!-- LEARN -->

                <div class="exchange-card">

                    <h3>
                        You Can Learn
                    </h3>

                    <p>
                        Skills {{ $user->name }} can teach you
                    </p>


                    <div class="skills">

                        @foreach ($skillsYouCanLearn as $skill)

                            <span class="skill skill-learning">
                                {{ $skill->name }}
                            </span>

                        @endforeach

                    </div>

                </div>



                <!-- SWAP ICON -->

                <div class="exchange-icon">
                    ⇄
                </div>



                <!-- TEACH -->

                <div class="exchange-card">

                    <h3>
                        You Can Teach
                    </h3>

                    <p>
                        Skills {{ $user->name }} wants to learn
                    </p>


                    <div class="skills">

                        @foreach ($skillsYouCanTeach as $skill)

                            <span class="skill">
                                {{ $skill->name }}
                            </span>

                        @endforeach

                    </div>

                </div>


            </div>



            <!-- NEXT ACTION -->

            <section class="card swap-card">

                <h2>
                    Ready to Swap?
                </h2>

                <p>
                    You and {{ $user->name }} have skills that match
                    each other's learning goals.
                </p>

                <p>
                    You will be able to send a swap request in the
                    next feature.
                </p>


                <button
                    type="button"
                    class="btn btn-disabled"
                    disabled
                >
                    Send Swap Request
                </button>

            </section>



        @else


            <!-- NOT A MUTUAL MATCH -->

            <div class="reason-grid">


                <div class="card reason-card">

                    <h3>
                        What You Can Learn
                    </h3>


                    @if ($skillsYouCanLearn->isNotEmpty())

                        <div class="skills">

                            @foreach ($skillsYouCanLearn as $skill)

                                <span class="skill skill-learning">
                                    {{ $skill->name }}
                                </span>

                            @endforeach

                        </div>

                    @else

                        <p class="muted">
                            {{ $user->name }} does not currently teach
                            any skill you want to learn.
                        </p>

                    @endif

                </div>



                <div class="card reason-card">

                    <h3>
                        What You Can Teach
                    </h3>


                    @if ($skillsYouCanTeach->isNotEmpty())

                        <div class="skills">

                            @foreach ($skillsYouCanTeach as $skill)

                                <span class="skill">
                                    {{ $skill->name }}
                                </span>

                            @endforeach

                        </div>

                    @else

                        <p class="muted">
                            You do not currently teach any skill that
                            {{ $user->name }} wants to learn.
                        </p>

                    @endif

                </div>


            </div>



            <section class="card reason-message">

                <h3>
                    Why isn't this a mutual match?
                </h3>

                <p>
                    A mutual SkillSwap match happens when you can
                    teach something the other person wants to learn,
                    and they can also teach something you want to learn.
                </p>

            </section>


        @endif


    </main>

</body>

</html>