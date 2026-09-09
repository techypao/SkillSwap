<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review {{ $reviewee->name }} - SkillSwap</title>

    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #f5f7fb; color: #1f2937; }
        .navbar { background: white; border-bottom: 1px solid #e5e7eb; padding: 16px 30px; display: flex; align-items: center; justify-content: space-between; }
        .brand, .back-link { color: #2563eb; text-decoration: none; }
        .brand { font-size: 22px; font-weight: bold; }
        .logout-button { padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; background: white; cursor: pointer; }
        .container { max-width: 720px; margin: 0 auto; padding: 30px 20px 60px; }
        .back-link { display: inline-block; margin-bottom: 18px; }
        .card { background: white; border: 1px solid #e5e7eb; border-radius: 14px; padding: 24px; }
        .card h1 { margin-top: 0; }
        .muted { color: #6b7280; }
        .exchange { display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; margin: 20px 0; }
        .exchange div { background: #f9fafb; border-radius: 10px; padding: 14px; }
        .form-group { margin: 20px 0; }
        .form-label { display: block; font-weight: bold; margin-bottom: 9px; }
        .rating-options { display: flex; flex-wrap: wrap; gap: 10px; }
        .rating-option { border: 1px solid #d1d5db; border-radius: 8px; padding: 9px 11px; }
        .stars { color: #d97706; letter-spacing: 1px; }
        textarea { border: 1px solid #d1d5db; border-radius: 8px; min-height: 120px; padding: 11px; resize: vertical; width: 100%; }
        .btn { border: 0; border-radius: 8px; background: #2563eb; color: white; cursor: pointer; font-size: 14px; font-weight: bold; padding: 11px 17px; }
        .alert-error { background: #fef2f2; border-radius: 10px; color: #b91c1c; margin-bottom: 18px; padding: 14px 16px; }
        @media (max-width: 600px) { .navbar { padding: 15px 18px; } .exchange { grid-template-columns: 1fr; } }
    </style>
</head>

<body>
    <nav class="navbar">
        <a href="{{ route('dashboard') }}" class="brand">SkillSwap</a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="logout-button">Logout</button>
        </form>
    </nav>

    <main class="container">
        <a href="{{ route('swap-requests.chat', $skillSession->swap_request_id) }}" class="back-link">← Back to Conversation</a>

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
            <h1>Review Your Skill Swap</h1>
            <p>How was your session with <strong>{{ $reviewee->name }}</strong>?</p>

            <div class="exchange">
                <div><strong>You taught</strong><br>{{ $skillYouTaught->name }}</div>
                <div><strong>You learned</strong><br>{{ $skillYouLearned->name }}</div>
            </div>

            <form method="POST" action="{{ route('reviews.store', $skillSession) }}">
                @csrf

                <div class="form-group">
                    <span class="form-label">Rating</span>
                    <div class="rating-options">
                        @foreach (range(1, 5) as $rating)
                            <label class="rating-option">
                                <input type="radio" name="rating" value="{{ $rating }}" @checked((int) old('rating') === $rating) required>
                                <span class="stars">{{ str_repeat('★', $rating) }}</span>
                                {{ $rating }}
                            </label>
                        @endforeach
                    </div>
                    <p class="muted">1 = Poor · 5 = Excellent</p>
                </div>

                <div class="form-group">
                    <label for="comment" class="form-label">Comment (optional)</label>
                    <textarea id="comment" name="comment" maxlength="1000" placeholder="Share what made this skill swap helpful.">{{ old('comment') }}</textarea>
                </div>

                <button type="submit" class="btn">Submit Review</button>
            </form>
        </section>
    </main>
</body>

</html>
