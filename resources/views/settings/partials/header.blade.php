<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div class="flex items-center gap-4">
        <a
            href="{{ route('dashboard') }}"
            class="text-sm font-semibold text-gray-600 hover:text-gray-900"
        >
            &larr; Back to dashboard
        </a>

        <a
            href="{{ route('profile.show') }}"
            class="text-sm font-semibold text-gray-600 hover:text-gray-900"
        >
            View my profile
        </a>
    </div>

    <nav class="flex items-center gap-2">
        <a
            href="{{ route('settings.edit') }}"
            @class([
                'px-4 py-2 rounded-xl text-sm font-semibold transition',
                'bg-gray-900 text-white' => $active === 'profile',
                'bg-white text-gray-700 hover:bg-gray-200' => $active !== 'profile',
            ])
        >
            Profile
        </a>

        <a
            href="{{ route('settings.skills.edit') }}"
            @class([
                'px-4 py-2 rounded-xl text-sm font-semibold transition',
                'bg-gray-900 text-white' => $active === 'skills',
                'bg-white text-gray-700 hover:bg-gray-200' => $active !== 'skills',
            ])
        >
            Skills
        </a>
    </nav>
</div>
