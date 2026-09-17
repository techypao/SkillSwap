@once
    <style>
        .nb { position: relative; }
        .nb > summary { list-style: none; cursor: pointer; position: relative; display: flex; align-items: center; padding: 6px; border-radius: 8px; color: #374151; }
        .nb > summary::-webkit-details-marker { display: none; }
        .nb > summary:hover, .nb[open] > summary { background: #f3f4f6; }
        .nb-badge { position: absolute; top: 0; right: 0; transform: translate(35%, -25%); min-width: 18px; height: 18px; padding: 0 5px; border-radius: 9px; background: #dc2626; color: white; font-size: 11px; font-weight: bold; line-height: 18px; text-align: center; }
        .nb-panel { position: absolute; right: 0; top: calc(100% + 10px); width: 320px; max-width: calc(100vw - 24px); background: white; border: 1px solid #e5e7eb; border-radius: 12px; box-shadow: 0 10px 25px rgba(0, 0, 0, .08); z-index: 50; overflow: hidden; text-align: left; }
        .nb-header { padding: 12px 14px; border-bottom: 1px solid #e5e7eb; font-weight: bold; font-size: 14px; color: #1f2937; }
        .nb-list { max-height: 360px; overflow-y: auto; }
        .nb-item form { margin: 0; }
        .nb-item button { display: block; width: 100%; text-align: left; background: white; border: 0; border-bottom: 1px solid #f3f4f6; padding: 11px 14px; cursor: pointer; font: inherit; color: #1f2937; }
        .nb-item button:hover { background: #f9fafb; }
        .nb-item.is-unread button { background: #eff6ff; box-shadow: inset 3px 0 0 #2563eb; }
        .nb-item.is-unread .nb-message { font-weight: bold; }
        .nb-message { font-size: 14px; }
        .nb-time { color: #6b7280; font-size: 12px; margin-top: 3px; }
        .nb-empty { padding: 18px 14px; color: #6b7280; font-size: 14px; }
        .nb-footer { display: flex; justify-content: space-between; align-items: center; gap: 10px; padding: 10px 14px; font-size: 13px; }
        .nb-footer form { margin: 0; }
        .navbar .nb-footer a, .nb-footer button { color: #2563eb; background: none; border: 0; cursor: pointer; font-size: 13px; padding: 0; text-decoration: none; }
    </style>
@endonce

<details class="nb {{ ($active ?? false) ? 'is-active' : '' }}">
    <summary aria-label="Notifications{{ $unreadNotificationCount > 0 ? " ({$unreadNotificationCount} unread)" : '' }}">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"></path>
            <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"></path>
        </svg>

        @if ($unreadNotificationCount > 0)
            <b class="nb-badge" data-unread-badge>{{ $unreadNotificationCount > 9 ? '9+' : $unreadNotificationCount }}</b>
        @endif
    </summary>

    <div class="nb-panel">
        <div class="nb-header">Notifications</div>

        <div class="nb-list">
            @forelse ($recentNotifications as $notification)
                <div class="nb-item {{ $notification->read_at ? 'is-read' : 'is-unread' }}">
                    <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit">
                            <div class="nb-message">{{ $notification->data['message'] ?? 'New activity' }}</div>
                            <div class="nb-time">{{ $notification->created_at->diffForHumans() }}</div>
                        </button>
                    </form>
                </div>
            @empty
                <div class="nb-empty">You're all caught up.</div>
            @endforelse
        </div>

        <div class="nb-footer">
            <a href="{{ route('notifications.index') }}">View all</a>

            @if ($unreadNotificationCount > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit">Mark all as read</button>
                </form>
            @endif
        </div>
    </div>
</details>
