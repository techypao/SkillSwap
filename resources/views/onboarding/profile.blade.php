<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Set up your profile | SkillSwap</title>
        <meta name="description" content="Complete your SkillSwap community profile.">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#070b18] text-white antialiased">
        <div class="landing-grid onboarding-shell relative isolate min-h-screen lg:grid lg:grid-cols-[280px_minmax(0,1fr)]">
            <div class="pointer-events-none fixed -right-40 top-20 size-[30rem] rounded-full bg-indigo-500/12 blur-[140px]"></div>

            @include('onboarding.partials.progress', ['currentStep' => 2])

            <main class="relative px-5 py-10 sm:px-8 lg:px-12 lg:py-14">
                <section class="mx-auto w-full max-w-3xl">
                    <div class="mb-8">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-violet-400">Make it yours</p>
                        <h1 class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">Set up your profile</h1>
                        <p class="mt-3 max-w-xl text-sm leading-6 text-slate-400">A little context helps your future matches know who they’ll be learning with.</p>
                    </div>

                    @if (session('success'))
                        <div class="mb-6 rounded-2xl border border-emerald-400/20 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-200" role="status">{{ session('success') }}</div>
                    @endif

                    <form action="{{ route('onboarding.profile.store') }}" method="POST" enctype="multipart/form-data" class="rounded-3xl border border-white/10 bg-[#11182d]/85 p-6 shadow-2xl shadow-black/25 backdrop-blur-xl sm:p-8">
                        @csrf

                        <div class="mb-7">
                            <label for="profile_picture" class="mb-2 block text-sm font-medium text-slate-200">Profile picture <span class="font-normal text-slate-600">(optional)</span></label>
                            <input type="file" name="profile_picture" id="profile_picture" accept="image/png,image/jpeg,image/webp" class="block w-full cursor-pointer rounded-xl border border-white/10 bg-[#0b1120]/80 text-sm text-slate-400 file:mr-4 file:border-0 file:bg-violet-500/15 file:px-4 file:py-3 file:font-semibold file:text-violet-200 hover:border-white/20">
                            <p class="mt-2 text-xs text-slate-600">JPG, PNG or WEBP. Maximum file size: 2 MB.</p>
                            @error('profile_picture')
                                <p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
                            @enderror
                        </div>

                        @include('partials.school-picker', ['user' => $user, 'selectedSchool' => $selectedSchool, 'theme' => 'dark'])
                        @include('partials.program-picker', ['user' => $user, 'programs' => $programs, 'theme' => 'dark'])

                        <div class="mb-7">
                            <label for="year_level" class="mb-2 block text-sm font-medium text-slate-200">Year level</label>
                            <select name="year_level" id="year_level" class="w-full rounded-xl border border-white/10 bg-[#0b1120]/80 px-4 py-3 text-sm text-white outline-none transition hover:border-white/20 focus:border-violet-400/60 focus:ring-4 focus:ring-violet-500/10" required>
                                <option value="">Select your year level</option>
                                @foreach ([1, 2, 3, 4, 5] as $yearLevel)
                                    <option value="{{ $yearLevel }}" @selected((string) old('year_level', $user->year_level) === (string) $yearLevel)>
                                        {{ $yearLevel }}{{ $yearLevel === 1 ? 'st' : ($yearLevel === 2 ? 'nd' : ($yearLevel === 3 ? 'rd' : 'th')) }} Year
                                    </option>
                                @endforeach
                            </select>
                            @error('year_level')
                                <p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <div class="mb-2 flex items-center justify-between gap-3">
                                <label for="bio" class="block text-sm font-medium text-slate-200">Bio</label>
                                <span class="text-xs text-slate-600">Maximum 500 characters</span>
                            </div>
                            <textarea name="bio" id="bio" rows="5" maxlength="500" placeholder="Tell people about yourself, your interests, or what you hope to learn..." class="w-full resize-y rounded-xl border border-white/10 bg-[#0b1120]/80 px-4 py-3 text-sm leading-6 text-white outline-none transition placeholder:text-slate-600 hover:border-white/20 focus:border-violet-400/60 focus:ring-4 focus:ring-violet-500/10" required>{{ old('bio', $user->bio) }}</textarea>
                            @error('bio')
                                <p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                            <a href="{{ route('onboarding.welcome') }}" class="inline-flex items-center justify-center rounded-xl border border-white/10 px-5 py-3 text-sm font-semibold text-slate-300 transition hover:border-white/20 hover:bg-white/5 hover:text-white sm:w-32">Back</a>
                            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-linear-to-r from-indigo-500 to-violet-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-violet-950/40 transition hover:-translate-y-0.5 hover:from-indigo-400 hover:to-violet-500 focus:outline-none focus:ring-4 focus:ring-violet-500/25 sm:min-w-48">
                                Save & continue
                                <svg class="size-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4.167 10h11.666m-5-5 5 5-5 5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                        </div>
                    </form>
                </section>
            </main>
        </div>
    </body>
</html>
