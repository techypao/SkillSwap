@php
    $steps = [
        1 => ['Welcome', 'Let’s get started'],
        2 => ['Your profile', 'Tell us about you'],
        3 => ['What you teach', 'Share your strengths'],
        4 => ['What you learn', 'Follow your curiosity'],
        5 => ['Availability', 'Choose your schedule'],
    ];
@endphp

<aside class="lg:sticky lg:top-0 lg:flex lg:min-h-screen lg:flex-col lg:border-r lg:border-white/8 lg:bg-[#0a0f1e]/70 lg:px-7 lg:py-8 lg:backdrop-blur-xl">
    <a href="{{ url('/') }}" class="group flex w-fit items-center gap-3" aria-label="SkillSwap home">
        <span class="flex size-10 items-center justify-center rounded-xl bg-linear-to-br from-indigo-500 to-violet-600 shadow-lg shadow-violet-950/40 transition-transform group-hover:-rotate-6">
            <svg class="size-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M7.25 7.5A4.75 4.75 0 0 1 12 2.75h4.25l-2.1 2.1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M16.75 16.5A4.75 4.75 0 0 1 12 21.25H7.75l2.1-2.1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="m14.15 2.75 2.1 2.1-2.1 2.1M9.85 21.25l-2.1-2.1 2.1-2.1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M8.5 12h7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </span>
        <span class="text-lg font-semibold tracking-tight">Skill<span class="text-violet-400">Swap</span></span>
    </a>

    <div class="mt-7 lg:hidden">
        <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-[0.18em]">
            <span class="text-violet-300">Step {{ $currentStep }} of 5</span>
            <span class="text-slate-600">{{ $steps[$currentStep][0] }}</span>
        </div>
        <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-white/8">
            <div class="h-full rounded-full bg-linear-to-r from-indigo-500 to-violet-500 transition-all" style="width: {{ $currentStep * 20 }}%"></div>
        </div>
    </div>

    <nav class="mt-16 hidden lg:block" aria-label="Onboarding progress">
        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-600">Set up your account</p>
        <ol class="mt-6 space-y-1">
            @foreach ($steps as $number => [$label, $description])
                <li class="relative flex gap-4 rounded-2xl px-3 py-3 {{ $number === $currentStep ? 'bg-violet-400/8' : '' }}">
                    @if (! $loop->last)
                        <span class="absolute top-10 left-[1.65rem] h-6 w-px {{ $number < $currentStep ? 'bg-violet-500/60' : 'bg-white/10' }}" aria-hidden="true"></span>
                    @endif

                    <span class="relative z-10 flex size-8 shrink-0 items-center justify-center rounded-full border text-xs font-semibold {{ $number < $currentStep ? 'border-violet-500 bg-violet-500 text-white' : ($number === $currentStep ? 'border-violet-400 bg-violet-400/15 text-violet-200 shadow-[0_0_20px_rgba(139,92,246,0.25)]' : 'border-white/10 bg-[#0b1120] text-slate-600') }}">
                        @if ($number < $currentStep)
                            <svg class="size-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m5 10 3 3 7-7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        @else
                            {{ $number }}
                        @endif
                    </span>
                    <span>
                        <span class="block text-sm font-semibold {{ $number === $currentStep ? 'text-white' : ($number < $currentStep ? 'text-slate-300' : 'text-slate-600') }}">{{ $label }}</span>
                        <span class="mt-0.5 block text-xs {{ $number === $currentStep ? 'text-slate-400' : 'text-slate-700' }}">{{ $description }}</span>
                    </span>
                </li>
            @endforeach
        </ol>
    </nav>

    <div class="mt-auto hidden rounded-2xl border border-violet-400/10 bg-violet-400/5 p-4 lg:block">
        <p class="text-sm font-medium text-slate-300">A brighter you starts with curiosity.</p>
        <p class="mt-1 text-xs leading-5 text-slate-600">Your details help us find skill matches that make sense.</p>
    </div>
</aside>
