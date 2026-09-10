@php
    $startsAt = $skillSession->scheduled_at;
    $endsAt = $skillSession->endsAt();
    $inProgress = $skillSession->isInProgress();
    $hasEnded = $skillSession->hasEnded();

    $state = $hasEnded ? 'ended' : ($inProgress ? 'live' : 'upcoming');
    $target = $inProgress ? $endsAt : $startsAt;
@endphp

<div
    class="countdown countdown-{{ $state }}"
    data-session-countdown
    data-state="{{ $state }}"
    data-starts-at="{{ $startsAt->toIso8601String() }}"
    data-ends-at="{{ $endsAt->toIso8601String() }}"
    data-server-now="{{ now()->toIso8601String() }}"
>
    <div class="countdown-label" data-countdown-label>
        @if ($hasEnded)
            Session ended
        @elseif ($inProgress)
            In session &middot; time remaining
        @else
            Starts in
        @endif
    </div>

    <div class="countdown-time" data-countdown-time>
        @if ($hasEnded)
            &mdash;
        @else
            {{ $target->diffForHumans(now(), ['syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE, 'parts' => 2]) }}
        @endif
    </div>

    <div class="countdown-meta">
        {{ $startsAt->format('F j, Y') }} &middot;
        {{ $startsAt->format('g:i A') }} &ndash; {{ $endsAt->format('g:i A') }}
        ({{ $skillSession->duration_minutes }} minutes)
    </div>
</div>

<script>
    (() => {
        const card = document.querySelector('[data-session-countdown]');

        if (! card) {
            return;
        }

        const startsAt = new Date(card.dataset.startsAt).getTime();
        const endsAt = new Date(card.dataset.endsAt).getTime();
        // Anchor to the server clock so a skewed device clock cannot mislead.
        const drift = new Date(card.dataset.serverNow).getTime() - Date.now();

        const label = card.querySelector('[data-countdown-label]');
        const time = card.querySelector('[data-countdown-time]');

        const format = (ms) => {
            const total = Math.max(0, Math.floor(ms / 1000));
            const days = Math.floor(total / 86400);
            const hours = Math.floor((total % 86400) / 3600);
            const minutes = Math.floor((total % 3600) / 60);
            const seconds = total % 60;

            if (days > 0) {
                return `${days}d ${hours}h ${minutes}m`;
            }

            if (hours > 0) {
                return `${hours}h ${minutes}m ${seconds}s`;
            }

            return `${minutes}m ${seconds}s`;
        };

        const tick = () => {
            const now = Date.now() + drift;

            if (now >= endsAt) {
                card.className = 'countdown countdown-ended';
                label.textContent = 'Session ended';
                time.innerHTML = '&mdash;';

                return true;
            }

            if (now >= startsAt) {
                card.className = 'countdown countdown-live';
                label.innerHTML = 'In session &middot; time remaining';
                time.textContent = format(endsAt - now);

                return false;
            }

            card.className = 'countdown countdown-upcoming';
            label.textContent = 'Starts in';
            time.textContent = format(startsAt - now);

            return false;
        };

        if (! tick()) {
            const timer = setInterval(() => {
                if (tick()) {
                    clearInterval(timer);
                }
            }, 1000);
        }
    })();
</script>
