<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - SkillSwap</title>

    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #f5f7fb; color: #1f2937; }
        .navbar { background: white; border-bottom: 1px solid #e5e7eb; padding: 16px 30px; display: flex; align-items: center; justify-content: space-between; }
        .brand, .back-link { color: #2563eb; text-decoration: none; }
        .brand { font-size: 22px; font-weight: bold; }
        .navbar-right { display: flex; align-items: center; gap: 20px; }
        .navbar a { text-decoration: none; color: #374151; }
        .navbar a.brand { color: #2563eb; }
        .logout-button { padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; background: white; cursor: pointer; }
        .container { max-width: 720px; margin: 0 auto; padding: 30px 20px 60px; }
        .back-link { display: inline-block; margin-bottom: 18px; }
        .card { background: white; border: 1px solid #e5e7eb; border-radius: 14px; overflow: hidden; }
        .card-header { display: flex; justify-content: space-between; align-items: center; padding: 18px 22px; border-bottom: 1px solid #e5e7eb; }
        .card-header h1 { font-size: 20px; margin: 0; }
        .card-header form { margin: 0; }
        .link-button { background: none; border: 0; color: #2563eb; cursor: pointer; font-size: 14px; padding: 0; }
        .notification form { margin: 0; }
        .notification button { display: block; width: 100%; text-align: left; background: white; border: 0; border-bottom: 1px solid #f3f4f6; padding: 14px 22px; cursor: pointer; font: inherit; color: #1f2937; }
        .notification button:hover { background: #f9fafb; }
        .notification.is-unread button { background: #eff6ff; box-shadow: inset 3px 0 0 #2563eb; }
        .notification.is-unread .notification-message { font-weight: bold; }
        .notification-time { color: #6b7280; font-size: 13px; margin-top: 4px; }
        .empty { color: #6b7280; padding: 22px; }
        .pagination { padding: 14px 22px; }
        .pagination nav ul { display: flex; gap: 14px; list-style: none; margin: 0; padding: 0; }
        .alert-success { background: #dcfce7; border-radius: 10px; color: #166534; margin-bottom: 16px; padding: 13px 16px; }
        @media (max-width: 700px) { .navbar { padding: 15px 18px; } .navbar-right { gap: 10px; } }
    </style>
</head>

<body>
    <nav class="navbar">
        <a href="{{ route('dashboard') }}" class="brand">SkillSwap</a>
        <div class="navbar-right">
            <a href="{{ route('dashboard') }}">Dashboard</a>
            <a href="{{ route('discover.index') }}">Discover</a>
            @include('partials.current-session-link')
            @include('partials.notification-bell')
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="logout-button">Logout</button>
            </form>
        </div>
    </nav>

    <main class="container">
        <a href="{{ route('dashboard') }}" class="back-link">← Back to Dashboard</a>

        <div class="card">
            <div class="card-header">
                <h1>Notifications</h1>

                @if (auth()->user()->unreadNotifications()->exists())
                    <form method="POST" action="{{ route('notifications.read-all') }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="link-button">Mark all as read</button>
                    </form>
                @endif
            </div>

            @forelse ($notifications as $notification)
                <div class="notification {{ $notification->read_at ? 'is-read' : 'is-unread' }}">
                    <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit">
                            <div class="notification-message">{{ $notification->data['message'] ?? 'New activity' }}</div>
                            <div class="notification-time">{{ $notification->created_at->diffForHumans() }}</div>
                        </button>
                    </form>
                </div>
            @empty
                <div class="empty">You have no notifications yet.</div>
            @endforelse

            @if ($notifications->hasPages())
                <div class="pagination">
                    {{ $notifications->links('pagination::simple-default') }}
                </div>
            @endif
        </div>
    </main>
</body>

</html>
