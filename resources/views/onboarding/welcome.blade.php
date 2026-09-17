<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Welcome | SkillSwap</title>
        <meta name="description" content="Set up your SkillSwap profile and find your next skill exchange.">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#070b18] text-white antialiased">
        <div class="landing-grid onboarding-shell relative isolate min-h-screen overflow-hidden lg:grid lg:grid-cols-[280px_minmax(0,1fr)]">
            <div class="pointer-events-none absolute -right-40 top-12 size-[32rem] rounded-full bg-violet-600/15 blur-[140px]"></div>
            <div class="pointer-events-none absolute bottom-0 left-1/3 size-80 rounded-full bg-indigo-500/10 blur-[120px]"></div>

            @include('onboarding.partials.progress', ['currentStep' => 1])

            <main class="relative flex min-h-[calc(100vh-104px)] items-center justify-center px-5 py-12 sm:px-8 lg:min-h-screen lg:px-12">
                <section class="w-full max-w-3xl text-center">
                    <div class="relative mx-auto flex size-24 items-center justify-center rounded-[2rem] border border-violet-400/20 bg-[#11182d]/85 shadow-2xl shadow-violet-950/50 backdrop-blur-xl sm:size-28">
                        <div class="absolute inset-0 rounded-[2rem] bg-[radial-gradient(circle_at_50%_10%,rgba(139,92,246,0.25),transparent_70%)]"></div>
                        <svg class="relative size-11 text-violet-300" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M8 11.5 11 14l5-6M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>

                    <p class="mt-8 text-xs font-semibold uppercase tracking-[0.24em] text-violet-400">Welcome to the community</p>
                    <h1 class="mt-4 text-4xl font-semibold tracking-[-0.04em] text-balance sm:text-6xl">Hey, {{ auth()->user()->name }}. Let’s build your <span class="hero-gradient">SkillSwap profile.</span></h1>
                    <p class="mx-auto mt-6 max-w-xl text-base leading-7 text-slate-400 sm:text-lg">Tell us what you know, what you’re curious about, and when you’re free. We’ll use it to find people you’ll genuinely enjoy learning with.</p>

                    <div class="mx-auto mt-8 grid max-w-2xl gap-3 text-left sm:grid-cols-3">
                        @foreach ([['01', 'Build your profile'], ['02', 'Choose your skills'], ['03', 'Meet your matches']] as [$number, $label])
                            <div class="rounded-2xl border border-white/8 bg-white/[0.035] p-4">
                                <span class="text-xs font-semibold text-violet-400">{{ $number }}</span>
                                <p class="mt-2 text-sm font-medium text-slate-300">{{ $label }}</p>
                            </div>
                        @endforeach
                    </div>

                    <a href="{{ route('onboarding.profile') }}" class="group mx-auto mt-9 inline-flex w-full max-w-sm items-center justify-center gap-2 rounded-xl bg-linear-to-r from-indigo-500 to-violet-600 px-6 py-3.5 text-sm font-semibold text-white shadow-xl shadow-violet-950/40 transition hover:-translate-y-0.5 hover:from-indigo-400 hover:to-violet-500 focus:outline-none focus:ring-4 focus:ring-violet-500/25">
                        Get started
                        <svg class="size-4 transition-transform group-hover:translate-x-1" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4.167 10h11.666m-5-5 5 5-5 5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                    <p class="mt-4 text-xs text-slate-600">It only takes a few minutes.</p>
                </section>
            </main>
        </div>
    </body>
</html>
