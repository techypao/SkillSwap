<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>SkillSwap — Learn together. Grow together.</title>
        <meta name="description" content="Exchange skills with curious people in your community. Teach what you know and learn what you love.">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#070b18] text-white antialiased">
        <div class="landing-grid relative isolate min-h-screen overflow-hidden">
            <div class="pointer-events-none absolute left-[-12rem] top-32 h-96 w-96 rounded-full bg-violet-600/20 blur-[120px]"></div>
            <div class="pointer-events-none absolute right-[-8rem] top-0 h-[32rem] w-[32rem] rounded-full bg-indigo-500/15 blur-[140px]"></div>

            <header class="relative z-20 border-b border-white/5">
                <nav class="mx-auto flex max-w-7xl items-center justify-between px-5 py-5 sm:px-8 lg:px-10" aria-label="Main navigation">
                    <a href="{{ url('/') }}" class="group flex items-center gap-3" aria-label="SkillSwap home">
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

                    <div class="hidden items-center gap-8 text-sm text-slate-400 md:flex">
                        <a href="#how-it-works" class="transition-colors hover:text-white">How it works</a>
                        <a href="#popular-skills" class="transition-colors hover:text-white">Browse skills</a>
                        <a href="#community" class="transition-colors hover:text-white">Community</a>
                    </div>

                    <div class="flex items-center gap-2 sm:gap-3">
                        @auth
                            <a href="{{ route('dashboard') }}" class="rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-violet-100 sm:px-5">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="hidden rounded-xl px-4 py-2.5 text-sm font-medium text-slate-300 transition hover:bg-white/5 hover:text-white sm:block">Log in</a>
                            <a href="{{ route('register') }}" class="rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-violet-950/40 transition hover:bg-violet-500 sm:px-5">Sign up</a>
                        @endauth
                    </div>
                </nav>
            </header>

            <main>
                <section class="relative mx-auto grid max-w-7xl items-center gap-14 px-5 pb-20 pt-16 sm:px-8 sm:pt-24 lg:min-h-[720px] lg:grid-cols-[1.02fr_0.98fr] lg:px-10 lg:pb-28 lg:pt-20">
                    <div class="relative z-10 max-w-2xl">
                        <div class="mb-7 inline-flex items-center gap-2 rounded-full border border-violet-400/20 bg-violet-400/8 px-3.5 py-2 text-xs font-medium text-violet-200">
                            <span class="relative flex size-2">
                                <span class="absolute inline-flex size-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex size-2 rounded-full bg-emerald-400"></span>
                            </span>
                            A community built on shared knowledge
                        </div>

                        <h1 class="text-5xl leading-[0.95] font-semibold tracking-[-0.055em] text-balance sm:text-6xl lg:text-7xl xl:text-[5.4rem]">
                            Learn. Teach.<br>
                            Grow <span class="hero-gradient">together.</span>
                        </h1>

                        <p class="mt-7 max-w-xl text-base leading-7 text-slate-400 sm:text-lg">
                            Everyone has something worth sharing. Exchange skills with real people, build meaningful connections, and grow without spending a peso.
                        </p>

                        <div class="mt-9 flex flex-col gap-3 sm:flex-row sm:items-center">
                            @auth
                                <a href="{{ route('dashboard') }}" class="group inline-flex items-center justify-center gap-2 rounded-xl bg-linear-to-r from-indigo-500 to-violet-600 px-6 py-3.5 text-sm font-semibold shadow-xl shadow-violet-950/50 transition hover:-translate-y-0.5 hover:shadow-violet-900/50">
                                    Go to dashboard
                                    <svg class="size-4 transition-transform group-hover:translate-x-1" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4.167 10h11.666m-5-5 5 5-5 5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </a>
                            @else
                                <a href="{{ route('register') }}" class="group inline-flex items-center justify-center gap-2 rounded-xl bg-linear-to-r from-indigo-500 to-violet-600 px-6 py-3.5 text-sm font-semibold shadow-xl shadow-violet-950/50 transition hover:-translate-y-0.5 hover:shadow-violet-900/50">
                                    Start swapping skills
                                    <svg class="size-4 transition-transform group-hover:translate-x-1" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4.167 10h11.666m-5-5 5 5-5 5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </a>
                            @endauth
                            <a href="#how-it-works" class="inline-flex items-center justify-center gap-3 rounded-xl px-5 py-3.5 text-sm font-medium text-slate-300 transition hover:bg-white/5 hover:text-white">
                                <span class="flex size-8 items-center justify-center rounded-full border border-violet-400/40 bg-violet-400/10 text-violet-300">
                                    <svg class="ml-0.5 size-3" viewBox="0 0 12 12" fill="currentColor" aria-hidden="true"><path d="m9.5 6-6 3.5v-7L9.5 6Z"/></svg>
                                </span>
                                See how it works
                            </a>
                        </div>

                        <dl class="mt-12 grid max-w-lg grid-cols-3 divide-x divide-white/10 border-t border-white/10 pt-6">
                            <div><dt class="text-xs text-slate-500">Learners</dt><dd class="mt-1 text-xl font-semibold">10K+</dd></div>
                            <div class="pl-6 sm:pl-8"><dt class="text-xs text-slate-500">Skills exchanged</dt><dd class="mt-1 text-xl font-semibold">5K+</dd></div>
                            <div class="pl-6 sm:pl-8"><dt class="text-xs text-slate-500">Community rating</dt><dd class="mt-1 text-xl font-semibold">4.9<span class="text-sm text-amber-300"> ★</span></dd></div>
                        </dl>
                    </div>

                    <div class="relative mx-auto h-[470px] w-full max-w-[570px] sm:h-[540px]" aria-label="A network of people exchanging skills">
                        <div class="absolute left-1/2 top-1/2 size-[250px] -translate-x-1/2 -translate-y-1/2 rounded-full border border-violet-400/15 sm:size-[330px]"></div>
                        <div class="absolute left-1/2 top-1/2 size-[380px] -translate-x-1/2 -translate-y-1/2 rounded-full border border-dashed border-indigo-400/15 sm:size-[490px]"></div>

                        <div class="hero-orb absolute left-1/2 top-1/2 flex size-28 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full border border-white/10 bg-[#11182d]/90 shadow-2xl shadow-violet-950/70 backdrop-blur-xl sm:size-36">
                            <div class="text-center">
                                <span class="mx-auto flex size-10 items-center justify-center rounded-xl bg-linear-to-br from-indigo-500 to-violet-600 text-lg font-semibold">S</span>
                                <p class="mt-2 text-[11px] font-medium text-slate-300">Swap. Learn. Grow.</p>
                            </div>
                        </div>

                        <article class="floating-card absolute left-0 top-8 w-52 rounded-2xl border border-white/10 bg-[#11182d]/90 p-3.5 shadow-2xl shadow-black/30 backdrop-blur-xl sm:left-5 sm:top-16">
                            <div class="flex items-center gap-3">
                                <div class="flex size-11 items-center justify-center rounded-full bg-linear-to-br from-amber-200 to-orange-400 text-sm font-semibold text-orange-950">MC</div>
                                <div><p class="text-sm font-semibold">Mia Cruz</p><p class="text-[11px] text-emerald-400">● Available to teach</p></div>
                            </div>
                            <div class="mt-3 flex items-center justify-between rounded-xl bg-white/4 px-3 py-2"><span class="text-[11px] text-slate-400">Teaches</span><span class="text-xs font-medium text-violet-300">UI/UX Design</span></div>
                        </article>

                        <article class="floating-card-delayed absolute right-0 top-10 w-48 rounded-2xl border border-white/10 bg-[#11182d]/90 p-3.5 shadow-2xl shadow-black/30 backdrop-blur-xl sm:right-4 sm:top-20">
                            <div class="flex items-center gap-3">
                                <div class="flex size-11 items-center justify-center rounded-full bg-linear-to-br from-cyan-200 to-blue-500 text-sm font-semibold text-blue-950">AL</div>
                                <div><p class="text-sm font-semibold">Alex Lim</p><p class="text-[11px] text-slate-400">Web developer</p></div>
                            </div>
                            <div class="mt-3 flex items-center gap-1 text-xs text-amber-300">★★★★★ <span class="text-slate-500">4.9</span></div>
                        </article>

                        <div class="floating-card-delayed absolute bottom-10 left-3 rounded-2xl border border-white/10 bg-[#11182d]/90 px-4 py-3 shadow-xl backdrop-blur-xl sm:bottom-14 sm:left-10">
                            <p class="text-[10px] uppercase tracking-[0.18em] text-slate-500">I want to learn</p>
                            <div class="mt-2 flex items-center gap-2 text-sm font-medium"><span class="flex size-7 items-center justify-center rounded-lg bg-pink-400/15">📷</span> Photography</div>
                        </div>

                        <div class="floating-card absolute bottom-4 right-2 rounded-2xl border border-white/10 bg-[#11182d]/90 px-4 py-3 shadow-xl backdrop-blur-xl sm:bottom-20 sm:right-4">
                            <p class="text-[10px] uppercase tracking-[0.18em] text-slate-500">Match found</p>
                            <div class="mt-2 flex items-center gap-2">
                                <div class="flex -space-x-2"><span class="flex size-7 items-center justify-center rounded-full border-2 border-[#11182d] bg-violet-500 text-[9px] font-semibold">JL</span><span class="flex size-7 items-center justify-center rounded-full border-2 border-[#11182d] bg-cyan-500 text-[9px] font-semibold">SK</span></div>
                                <span class="text-sm font-medium">Let's swap!</span>
                            </div>
                        </div>

                        <span class="absolute left-[16%] top-[48%] size-2 rounded-full bg-violet-400 shadow-[0_0_18px_4px_rgba(167,139,250,0.45)]"></span>
                        <span class="absolute right-[14%] top-[52%] size-2 rounded-full bg-indigo-400 shadow-[0_0_18px_4px_rgba(129,140,248,0.4)]"></span>
                    </div>
                </section>

                <section id="popular-skills" class="border-y border-white/5 bg-white/[0.015] py-7">
                    <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-center gap-x-3 gap-y-3 px-5 sm:px-8 lg:justify-between lg:px-10">
                        <span class="mr-2 text-xs font-medium uppercase tracking-[0.2em] text-slate-500">Popular now</span>
                        @foreach (['Web Development', 'Graphic Design', 'Photography', 'Public Speaking', 'Data Science', 'Music'] as $skill)
                            <span class="rounded-full border border-white/8 bg-white/4 px-4 py-2 text-xs text-slate-300">{{ $skill }}</span>
                        @endforeach
                    </div>
                </section>

                <section id="how-it-works" class="relative mx-auto max-w-7xl px-5 py-24 sm:px-8 lg:px-10 lg:py-32">
                    <div class="mx-auto max-w-2xl text-center">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-violet-400">Simple by design</p>
                        <h2 class="mt-4 text-3xl font-semibold tracking-tight sm:text-5xl">Your next skill is one swap away.</h2>
                        <p class="mt-5 text-base leading-7 text-slate-400">No complicated courses or expensive subscriptions. Just people helping people get better.</p>
                    </div>

                    <div class="mt-14 grid gap-5 md:grid-cols-3">
                        @php
                            $steps = [
                                ['01', 'Share what you know', 'Build your profile and list the skills you can confidently teach.', 'M8 12h8M12 8v8'],
                                ['02', 'Find your skill match', 'Discover people who want to learn from you and can teach you in return.', 'M7.5 10a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Zm9 7a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5ZM3 18.5c.5-3 2-4.5 4.5-4.5s4 1.5 4.5 4.5m1.5 0c.3-1.5 1.3-2.5 3-2.5 1.8 0 3 1 3.5 2.5'],
                                ['03', 'Learn and grow together', 'Meet, exchange knowledge, and build connections that go beyond a lesson.', 'm7 12 3 3 7-7'],
                            ];
                        @endphp

                        @foreach ($steps as [$number, $title, $description, $iconPath])
                            <article class="group relative overflow-hidden rounded-3xl border border-white/8 bg-white/[0.035] p-7 transition duration-300 hover:-translate-y-1 hover:border-violet-400/25 hover:bg-white/[0.055] sm:p-8">
                                <span class="absolute right-6 top-5 text-5xl font-semibold text-white/[0.035]">{{ $number }}</span>
                                <span class="flex size-12 items-center justify-center rounded-2xl border border-violet-400/20 bg-violet-400/10 text-violet-300 transition group-hover:bg-violet-500 group-hover:text-white">
                                    <svg class="size-6" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="{{ $iconPath }}" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </span>
                                <h3 class="mt-6 text-lg font-semibold">{{ $title }}</h3>
                                <p class="mt-3 text-sm leading-6 text-slate-400">{{ $description }}</p>
                            </article>
                        @endforeach
                    </div>
                </section>

                <section id="community" class="px-5 pb-24 sm:px-8 lg:px-10 lg:pb-32">
                    <div class="relative mx-auto max-w-7xl overflow-hidden rounded-[2rem] border border-violet-400/15 bg-linear-to-br from-indigo-500/15 via-[#10162a] to-violet-600/15 px-6 py-14 text-center shadow-2xl shadow-black/20 sm:px-12 sm:py-20">
                        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_50%_0%,rgba(139,92,246,0.22),transparent_48%)]"></div>
                        <div class="relative mx-auto max-w-2xl">
                            <div class="mx-auto flex w-fit -space-x-2">
                                @foreach ([['JM', 'bg-fuchsia-500'], ['AR', 'bg-cyan-500'], ['KL', 'bg-amber-400'], ['NS', 'bg-violet-500']] as [$initials, $color])
                                    <span class="flex size-10 items-center justify-center rounded-full border-2 border-[#10162a] {{ $color }} text-[10px] font-semibold">{{ $initials }}</span>
                                @endforeach
                            </div>
                            <h2 class="mt-6 text-3xl font-semibold tracking-tight sm:text-5xl">Bring your skill. Leave with another.</h2>
                            <p class="mx-auto mt-5 max-w-xl text-base leading-7 text-slate-400">Join a growing community that believes knowledge becomes more valuable when it is shared.</p>
                            <a href="{{ route('register') }}" class="mt-8 inline-flex items-center gap-2 rounded-xl bg-white px-6 py-3.5 text-sm font-semibold text-slate-950 transition hover:-translate-y-0.5 hover:bg-violet-100">
                                Join SkillSwap
                                <svg class="size-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4.167 10h11.666m-5-5 5 5-5 5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </a>
                        </div>
                    </div>
                </section>
            </main>

            <footer class="border-t border-white/5">
                <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-5 py-7 text-xs text-slate-500 sm:flex-row sm:px-8 lg:px-10">
                    <p>© {{ date('Y') }} SkillSwap. Learn freely, grow together.</p>
                    <div class="flex items-center gap-5">
                        <a href="#how-it-works" class="transition hover:text-slate-300">How it works</a>
                        <a href="{{ route('login') }}" class="transition hover:text-slate-300">Log in</a>
                    </div>
                </div>
            </footer>
        </div>
    </body>
</html>
