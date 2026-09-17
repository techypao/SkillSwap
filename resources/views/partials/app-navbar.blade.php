@php
    $activeTab = $active ?? null;
@endphp

<nav class="navbar app-navbar" aria-label="Primary navigation">
    <a href="{{ route('dashboard') }}" class="brand" aria-label="SkillSwap dashboard">
        <img
            src="{{ asset('images/skillswap-mark.png') }}"
            class="brand-mark"
            alt=""
            width="42"
            height="42"
        >
        <span class="brand-name">SkillSwap</span>
    </a>

    <div class="navbar-right">
        <a href="{{ route('dashboard') }}" class="navbar-link {{ $activeTab === 'dashboard' ? 'active' : '' }}" @if ($activeTab === 'dashboard') aria-current="page" @endif>Dashboard</a>
        <a href="{{ route('discover.index') }}" class="navbar-link {{ $activeTab === 'discover' ? 'active' : '' }}" @if ($activeTab === 'discover') aria-current="page" @endif>Discover</a>
        <a href="{{ route('profile.show') }}" class="navbar-link {{ $activeTab === 'profile' ? 'active' : '' }}" @if ($activeTab === 'profile') aria-current="page" @endif>Profile</a>
        <a href="{{ route('settings.edit') }}" class="navbar-link {{ $activeTab === 'settings' ? 'active' : '' }}" @if ($activeTab === 'settings') aria-current="page" @endif>Settings</a>

        @include('partials.current-session-link')

        <a
            href="{{ route('profile.show') }}" data-profile-link
            class="navbar-user {{ $activeTab === 'profile' ? 'active' : '' }}"
            aria-label="View your profile"
            title="View your profile"
            @if ($activeTab === 'profile') aria-current="page" @endif
        >
            {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
        </a>

        @include('partials.notification-bell', ['active' => $activeTab === 'notifications'])

        <form method="POST" action="{{ route('logout') }}" class="navbar-logout">
            @csrf
            <button type="submit" class="logout-button">Logout</button>
        </form>
    </div>
</nav>
