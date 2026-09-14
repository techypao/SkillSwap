{{-- The logged-in user's own exchanges. Links are navigation only; every target route enforces its own policy. --}}
<aside class="swaps-sidebar" aria-label="Your swaps" data-workspace-panel="swaps">
    <div class="swaps-header">
        <h2>Swaps</h2>
    </div>

    <nav class="swaps-scroll" aria-label="Swap conversations">
        <section class="swap-group" data-swap-group="active">
            <h3 class="swap-group-label">Active <span class="swap-count">{{ $swapSidebar['active']->count() }}</span></h3>

            @forelse ($swapSidebar['active'] as $item)
                <a
                    href="{{ $item['url'] }}"
                    class="swap-item {{ $item['selected'] ? 'is-selected' : '' }}"
                    data-swap-id="{{ $item['swap']->id }}"@if ($item['selected']) aria-current="page"@endif
                >
                    <span class="swap-avatar" aria-hidden="true">
                        {{ mb_strtoupper(mb_substr($item['other']->name, 0, 1)) }}
                        <span class="swap-status-dot stage-dot-{{ $item['stage']['key'] }}"></span>
                    </span>
                    <span class="swap-item-body">
                        <span class="swap-item-name">{{ $item['other']->name }}</span>
                        <span class="swap-item-meta">{{ $item['teach']->name }} ↔ {{ $item['learn']->name }}</span>
                        <span class="swap-item-status">{{ $item['stage']['label'] }}</span>
                    </span>
                </a>
            @empty
                <div class="swaps-empty">
                    <p><strong>No active swaps yet.</strong></p>
                    <p>Find someone to exchange skills with.</p>
                    <a href="{{ route('discover.index') }}" class="btn btn-primary btn-block">Discover People</a>
                </div>
            @endforelse
        </section>

        @if ($swapSidebar['pending']->isNotEmpty())
            <section class="swap-group" data-swap-group="pending">
                <h3 class="swap-group-label">Pending <span class="swap-count">{{ $swapSidebar['pending']->count() }}</span></h3>

                @foreach ($swapSidebar['pending'] as $item)
                    <a href="{{ $item['url'] }}" class="swap-item is-pending" data-pending-swap-id="{{ $item['swap']->id }}">
                        <span class="swap-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($item['other']->name, 0, 1)) }}</span>
                        <span class="swap-item-body">
                            <span class="swap-item-name">{{ $item['other']->name }}</span>
                            <span class="swap-item-meta">{{ $item['teach']->name }} ↔ {{ $item['learn']->name }}</span>
                            <span class="swap-item-status">{{ $item['label'] }}</span>
                        </span>
                    </a>
                @endforeach
            </section>
        @endif

        @if ($swapSidebar['history']->isNotEmpty())
            <details class="swap-group" data-swap-group="history" @if ($swapSidebar['history']->contains('selected', true)) open @endif>
                <summary class="swap-group-label">History <span class="swap-count">{{ $swapSidebar['history']->count() }}</span></summary>

                @foreach ($swapSidebar['history'] as $item)
                    <a
                        href="{{ $item['url'] }}"
                        class="swap-item is-history {{ $item['selected'] ? 'is-selected' : '' }}"
                        data-swap-id="{{ $item['swap']->id }}"@if ($item['selected']) aria-current="page"@endif
                    >
                        <span class="swap-avatar" aria-hidden="true">✓</span>
                        <span class="swap-item-body">
                            <span class="swap-item-name">{{ $item['other']->name }}</span>
                            <span class="swap-item-meta">{{ $item['teach']->name }} ↔ {{ $item['learn']->name }}</span>
                        </span>
                    </a>
                @endforeach
            </details>
        @endif
    </nav>
</aside>
