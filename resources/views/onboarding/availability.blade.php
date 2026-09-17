<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Availability | SkillSwap</title>
        <meta name="description" content="Choose when you are available for SkillSwap sessions.">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#070b18] text-white antialiased">
        <div class="landing-grid onboarding-shell relative isolate min-h-screen lg:grid lg:grid-cols-[280px_minmax(0,1fr)]">
            <div class="pointer-events-none fixed -right-40 top-20 size-[30rem] rounded-full bg-indigo-500/12 blur-[140px]"></div>

            @include('onboarding.partials.progress', ['currentStep' => 5])

            <main class="relative px-5 py-10 sm:px-8 lg:px-12 lg:py-14">
                <section class="mx-auto w-full max-w-4xl">
                    <div class="mb-8">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-violet-400">One last step</p>
                        <h1 class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">When are you available?</h1>
                        <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-400">Choose the days and time periods when you’re usually open to a SkillSwap session.</p>
                    </div>

                    @if (session('success'))
                        <div class="mb-6 rounded-2xl border border-emerald-400/20 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-200" role="status">{{ session('success') }}</div>
                    @endif

                    @error('availability')
                        <div class="mb-6 rounded-2xl border border-rose-400/20 bg-rose-400/10 px-4 py-3 text-sm text-rose-200" role="alert">{{ $message }}</div>
                    @enderror

                    @php
                        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                        $timePeriods = [
                            'morning' => ['Morning', 'Before 12 PM'],
                            'afternoon' => ['Afternoon', '12–5 PM'],
                            'evening' => ['Evening', 'After 5 PM'],
                        ];
                    @endphp

                    <form action="{{ route('onboarding.availability.store') }}" method="POST" class="rounded-3xl border border-white/10 bg-[#11182d]/85 p-5 shadow-2xl shadow-black/25 backdrop-blur-xl sm:p-8">
                        @csrf

                        <div class="overflow-x-auto rounded-2xl border border-white/8">
                            <div class="min-w-[540px]">
                            <div class="grid grid-cols-[minmax(7rem,1fr)_repeat(3,minmax(5.5rem,0.72fr))] bg-white/[0.035]">
                                <div class="px-4 py-4 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Day</div>
                                @foreach ($timePeriods as [$label, $description])
                                    <div class="border-l border-white/8 px-2 py-3 text-center">
                                        <span class="block text-xs font-semibold text-slate-300 sm:text-sm">{{ $label }}</span>
                                        <span class="mt-0.5 hidden text-[10px] text-slate-600 sm:block">{{ $description }}</span>
                                    </div>
                                @endforeach
                            </div>

                            @foreach ($days as $day)
                                <div class="grid grid-cols-[minmax(7rem,1fr)_repeat(3,minmax(5.5rem,0.72fr))] border-t border-white/8 transition hover:bg-white/[0.025]">
                                    <div class="flex items-center px-4 py-4 text-sm font-medium text-slate-300">{{ ucfirst($day) }}</div>

                                    @foreach ($timePeriods as $timePeriod => [$label])
                                        @php
                                            $key = $day.'_'.$timePeriod;
                                            $oldValue = old("availability.$day.$timePeriod", in_array($key, $selectedAvailability));
                                        @endphp

                                        <label class="flex cursor-pointer items-center justify-center border-l border-white/8 px-2 py-4 transition hover:bg-violet-400/5" aria-label="{{ ucfirst($day) }} {{ strtolower($label) }}">
                                            <input type="checkbox" name="availability[{{ $day }}][{{ $timePeriod }}]" value="1" class="size-5 cursor-pointer accent-violet-500" @checked($oldValue)>
                                        </label>
                                    @endforeach
                                </div>
                            @endforeach
                            </div>
                        </div>

                        <div class="mt-5 flex gap-3 rounded-2xl border border-indigo-400/10 bg-indigo-400/5 px-4 py-3.5">
                            <svg class="mt-0.5 size-5 shrink-0 text-indigo-300" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.7"/><path d="M12 11v5m0-8v.01" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                            <p class="text-sm leading-6 text-slate-400">You don’t need exact times yet. Choose the periods when you’re generally available.</p>
                        </div>

                        <div class="mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                            <a href="{{ route('onboarding.skills.learn') }}" class="inline-flex items-center justify-center rounded-xl border border-white/10 px-5 py-3 text-sm font-semibold text-slate-300 transition hover:border-white/20 hover:bg-white/5 hover:text-white sm:w-32">Back</a>
                            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-linear-to-r from-indigo-500 to-violet-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-violet-950/40 transition hover:-translate-y-0.5 hover:from-indigo-400 hover:to-violet-500 focus:outline-none focus:ring-4 focus:ring-violet-500/25 sm:min-w-48">
                                Finish setup
                                <svg class="size-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M5 10.5 8.2 14 15.5 6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                        </div>
                    </form>
                </section>
            </main>
        </div>
    </body>
</html>
