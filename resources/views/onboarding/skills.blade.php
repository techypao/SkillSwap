<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title }} | SkillSwap</title>
        <meta name="description" content="Choose the skills for your SkillSwap profile.">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#070b18] text-white antialiased">
        <div class="landing-grid onboarding-shell relative isolate min-h-screen lg:grid lg:grid-cols-[280px_minmax(0,1fr)]">
            <div class="pointer-events-none fixed -right-40 top-20 size-[30rem] rounded-full bg-violet-500/12 blur-[140px]"></div>

            @include('onboarding.partials.progress', ['currentStep' => $step])

            <main class="relative px-5 py-10 sm:px-8 lg:px-12 lg:py-14">
                <section class="mx-auto w-full max-w-4xl">
                    <div class="mb-8">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-violet-400">{{ $step === 3 ? 'Share your strengths' : 'Follow your curiosity' }}</p>
                        <h1 class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">{{ $title }}</h1>
                        <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-400">{{ $intro }}</p>
                    </div>

                    <form action="{{ $formAction }}" method="POST" class="rounded-3xl border border-white/10 bg-[#11182d]/85 p-6 shadow-2xl shadow-black/25 backdrop-blur-xl sm:p-8">
                        @csrf

                        @include('partials.skill-picker', [
                            'group' => $group,
                            'heading' => $heading,
                            'skills' => $skills,
                            'categories' => $categories,
                            'selected' => $selected,
                            'theme' => 'dark',
                        ])

                        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                            <a href="{{ $backRoute }}" class="inline-flex items-center justify-center rounded-xl border border-white/10 px-5 py-3 text-sm font-semibold text-slate-300 transition hover:border-white/20 hover:bg-white/5 hover:text-white sm:w-32">Back</a>
                            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-linear-to-r from-indigo-500 to-violet-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-violet-950/40 transition hover:-translate-y-0.5 hover:from-indigo-400 hover:to-violet-500 focus:outline-none focus:ring-4 focus:ring-violet-500/25 sm:min-w-48">
                                Save & continue
                                <svg class="size-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4.167 10h11.666m-5-5 5 5-5 5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                        </div>
                    </form>
                </section>
            </main>
        </div>

        @stack('scripts')
    </body>
</html>
