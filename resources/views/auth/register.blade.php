<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Create an account | SkillSwap</title>
        <meta name="description" content="Join SkillSwap and start exchanging skills with your community.">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#070b18] text-white antialiased">
        <div class="landing-grid relative isolate min-h-screen overflow-hidden">
            <div class="pointer-events-none absolute -left-40 bottom-0 size-96 rounded-full bg-indigo-600/20 blur-[120px]"></div>
            <div class="pointer-events-none absolute -right-32 top-24 size-[30rem] rounded-full bg-violet-500/15 blur-[140px]"></div>

            <header class="relative z-20">
                <nav class="mx-auto flex max-w-7xl items-center justify-between px-5 py-5 sm:px-8 lg:px-10" aria-label="Authentication navigation">
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

                    <a href="{{ url('/') }}" class="group inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-medium text-slate-400 transition hover:bg-white/5 hover:text-white">
                        <svg class="size-4 transition-transform group-hover:-translate-x-1" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m12.5 15-5-5 5-5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <span class="hidden sm:inline">Back to home</span>
                    </a>
                </nav>
            </header>

            <main class="relative mx-auto grid min-h-[calc(100vh-80px)] max-w-6xl items-center gap-12 px-5 pb-14 sm:px-8 lg:grid-cols-[0.82fr_1.18fr] lg:px-10 lg:pb-20">
                <section class="relative hidden min-h-[560px] items-center justify-center lg:flex" aria-label="SkillSwap community message">
                    <div class="absolute left-1/2 top-1/2 size-[350px] -translate-x-1/2 -translate-y-1/2 rounded-full border border-violet-400/10"></div>
                    <div class="absolute left-1/2 top-1/2 size-[240px] -translate-x-1/2 -translate-y-1/2 rounded-full border border-dashed border-indigo-400/15"></div>
                    <span class="absolute left-[20%] top-[28%] size-7 rounded-full bg-violet-500/40 shadow-[0_0_40px_rgba(139,92,246,0.5)]"></span>
                    <span class="absolute right-[17%] top-[39%] size-3 rounded-full bg-amber-300 shadow-[0_0_22px_rgba(252,211,77,0.45)]"></span>
                    <span class="absolute bottom-[20%] left-[30%] size-3 rounded-full bg-indigo-400"></span>

                    <div class="relative max-w-xs text-center">
                        <span class="mx-auto flex size-14 items-center justify-center rounded-2xl border border-violet-400/20 bg-violet-400/10 text-violet-300 shadow-xl shadow-violet-950/40">
                            <svg class="size-7" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M16 19c0-2.2-1.8-4-4-4H7a4 4 0 0 0-4 4m18 0v-1a4 4 0 0 0-3-3.87M9.5 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm7-1a3.5 3.5 0 0 0 0-6.83" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                        <p class="mt-6 text-3xl leading-tight font-semibold tracking-tight">Join a community that values <span class="hero-gradient">curiosity.</span></p>
                        <p class="mt-4 text-sm leading-6 text-slate-500">What you know can help someone grow. What they know can do the same for you.</p>
                    </div>
                </section>

                <section class="mx-auto w-full max-w-[520px]">
                    <div class="rounded-3xl border border-white/10 bg-[#11182d]/85 p-6 shadow-2xl shadow-black/35 backdrop-blur-xl sm:p-9">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-violet-400">Join the community</p>
                            <h1 class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">Create your account</h1>
                            <p class="mt-3 text-sm leading-6 text-slate-400">Start sharing what you know and learning what you love.</p>
                        </div>

                        @if ($errors->any())
                            <div class="mt-6 rounded-2xl border border-rose-400/20 bg-rose-400/10 px-4 py-3 text-sm text-rose-200" role="alert">
                                <p class="font-medium">Please check your details.</p>
                                <ul class="mt-1 list-inside list-disc space-y-1 text-rose-200/80">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('register.store') }}" class="mt-7 grid gap-5 sm:grid-cols-2">
                            @csrf

                            <div class="sm:col-span-2">
                                <label for="name" class="mb-2 block text-sm font-medium text-slate-200">Full name</label>
                                <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="Jane Doe" class="w-full rounded-xl border border-white/10 bg-[#0b1120]/80 px-4 py-3 text-sm text-white outline-none transition placeholder:text-slate-600 hover:border-white/20 focus:border-violet-400/60 focus:ring-4 focus:ring-violet-500/10">
                            </div>

                            <div class="sm:col-span-2">
                                <label for="email" class="mb-2 block text-sm font-medium text-slate-200">Email address</label>
                                <input type="email" id="email" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="you@example.com" class="w-full rounded-xl border border-white/10 bg-[#0b1120]/80 px-4 py-3 text-sm text-white outline-none transition placeholder:text-slate-600 hover:border-white/20 focus:border-violet-400/60 focus:ring-4 focus:ring-violet-500/10">
                            </div>

                            <div>
                                <label for="password" class="mb-2 block text-sm font-medium text-slate-200">Password</label>
                                <div class="relative">
                                    <input type="password" id="password" name="password" required autocomplete="new-password" placeholder="At least 8 characters" class="w-full rounded-xl border border-white/10 bg-[#0b1120]/80 py-3 pr-12 pl-4 text-sm text-white outline-none transition placeholder:text-slate-600 hover:border-white/20 focus:border-violet-400/60 focus:ring-4 focus:ring-violet-500/10">
                                    <button type="button" data-password-toggle aria-controls="password" aria-label="Show password" class="absolute inset-y-0 right-0 flex w-12 items-center justify-center text-slate-500 transition hover:text-violet-300 focus:outline-none">
                                        <svg class="size-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="1.6"/><circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="1.6"/></svg>
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label for="password_confirmation" class="mb-2 block text-sm font-medium text-slate-200">Confirm password</label>
                                <div class="relative">
                                    <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password" placeholder="Repeat password" class="w-full rounded-xl border border-white/10 bg-[#0b1120]/80 py-3 pr-12 pl-4 text-sm text-white outline-none transition placeholder:text-slate-600 hover:border-white/20 focus:border-violet-400/60 focus:ring-4 focus:ring-violet-500/10">
                                    <button type="button" data-password-toggle aria-controls="password_confirmation" aria-label="Show password" class="absolute inset-y-0 right-0 flex w-12 items-center justify-center text-slate-500 transition hover:text-violet-300 focus:outline-none">
                                        <svg class="size-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="1.6"/><circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="1.6"/></svg>
                                    </button>
                                </div>
                            </div>

                            <button type="submit" class="w-full rounded-xl bg-linear-to-r from-indigo-500 to-violet-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-violet-950/40 transition hover:-translate-y-0.5 hover:from-indigo-400 hover:to-violet-500 focus:outline-none focus:ring-4 focus:ring-violet-500/25 sm:col-span-2">Create account</button>
                        </form>

                        <p class="mt-7 text-center text-sm text-slate-500">
                            Already have an account?
                            <a href="{{ route('login') }}" class="font-semibold text-violet-300 transition hover:text-violet-200">Log in</a>
                        </p>
                    </div>
                </section>
            </main>
        </div>
    </body>
</html>
