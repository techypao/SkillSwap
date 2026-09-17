{{--
    Program picker.

    A catalog pick submits program_id. Any other typed value submits as a
    custom program_name, so students are not limited to the suggestion list.
--}}
@php
    $selectedProgramId = (string) old('program_id', $user->program_id);
    $selectedProgram = $programs->first(fn ($program): bool => (string) $program->id === $selectedProgramId);
    $customProgramName = $selectedProgram ? '' : old('program_name', $user->program_name);
    $programOptions = $programs->map(fn ($program): array => [
        'id' => $program->id,
        'name' => $program->name,
        'abbreviation' => $program->abbreviation,
    ])->values();
    $isDark = ($theme ?? null) === 'dark';
@endphp

<div class="mb-7" data-program-picker data-theme="{{ $isDark ? 'dark' : 'light' }}">
    <label for="program_search" class="mb-2 block text-sm font-medium {{ $isDark ? 'text-slate-200' : 'text-gray-700' }}">
        Program
    </label>

    <input type="hidden" name="program_id" value="{{ $selectedProgram?->id }}" data-program-id>

    <script type="application/json" data-program-options>@json($programOptions)</script>

    {{-- Selected program --}}
    <div data-program-selected @if (! $selectedProgram) hidden @endif>
        <div class="flex items-center justify-between gap-3 rounded-xl border px-4 py-3 {{ $isDark ? 'border-violet-400/20 bg-violet-400/8' : 'border-gray-300 bg-gray-50' }}">
            <div>
                <p class="font-semibold {{ $isDark ? 'text-slate-100' : 'text-gray-900' }}" data-program-selected-name>{{ $selectedProgram?->name }}</p>
                <p class="text-xs {{ $isDark ? 'text-slate-500' : 'text-gray-600' }}" data-program-selected-abbreviation>{{ $selectedProgram?->abbreviation }}</p>
            </div>

            <button type="button" class="shrink-0 text-sm font-semibold {{ $isDark ? 'text-violet-300 hover:text-violet-200' : 'text-gray-700 underline' }}" data-program-change>
                Change
            </button>
        </div>
    </div>

    {{-- Search --}}
    <div data-program-search-panel @if ($selectedProgram) hidden @endif>
        <div class="relative">
            <input
                type="text"
                name="program_name"
                id="program_search"
                value="{{ $customProgramName }}"
                autocomplete="off"
                placeholder="Search or enter your program..."
                maxlength="255"
                role="combobox"
                aria-autocomplete="list"
                aria-expanded="false"
                aria-controls="program_suggestions"
                class="w-full rounded-xl border px-4 py-3 text-sm outline-none transition {{ $isDark ? 'border-white/10 bg-[#0b1120]/80 text-white placeholder:text-slate-600 hover:border-white/20 focus:border-violet-400/60 focus:ring-4 focus:ring-violet-500/10' : 'border-gray-300 focus:ring-2 focus:ring-gray-900' }}"
                @disabled($selectedProgram)
                @required(! $selectedProgram)
                data-program-search
            >

            <div data-program-suggestions-wrapper hidden>
                <ul
                    id="program_suggestions"
                    role="listbox"
                    class="absolute z-20 mt-2 max-h-72 w-full overflow-y-auto rounded-xl border shadow-2xl {{ $isDark ? 'border-white/10 bg-[#11182d] shadow-black/40' : 'border-gray-200 bg-white shadow-lg' }}"
                    data-program-suggestions
                ></ul>
            </div>
        </div>

        <p class="mt-2 text-sm {{ $isDark ? 'text-slate-500' : 'text-gray-500' }}" data-program-empty hidden>
            No matching programs found. You can continue with the program name you entered.
        </p>
    </div>

    <p class="mt-2 text-sm {{ $isDark ? 'text-slate-500' : 'text-gray-600' }}" data-program-help @if ($selectedProgram) hidden @endif>
        Choose a suggestion or keep your typed program name.
    </p>

    @error('program_id')
        <p class="mt-2 text-sm {{ $isDark ? 'text-rose-300' : 'text-red-500' }}">{{ $message }}</p>
    @enderror

    @error('program_name')
        <p class="mt-2 text-sm {{ $isDark ? 'text-rose-300' : 'text-red-500' }}">{{ $message }}</p>
    @enderror
</div>

@once
    <script>
        document.querySelectorAll('[data-program-picker]').forEach((picker) => {
            const programs = JSON.parse(picker.querySelector('[data-program-options]').textContent);
            const programId = picker.querySelector('[data-program-id]');
            const selectedPanel = picker.querySelector('[data-program-selected]');
            const selectedName = picker.querySelector('[data-program-selected-name]');
            const selectedAbbreviation = picker.querySelector('[data-program-selected-abbreviation]');
            const searchPanel = picker.querySelector('[data-program-search-panel]');
            const search = picker.querySelector('[data-program-search]');
            const suggestionsWrapper = picker.querySelector('[data-program-suggestions-wrapper]');
            const suggestions = picker.querySelector('[data-program-suggestions]');
            const empty = picker.querySelector('[data-program-empty]');
            const help = picker.querySelector('[data-program-help]');
            const isDark = picker.dataset.theme === 'dark';

            let results = [];
            let activeIndex = -1;

            // Same tiers as school search: exact abbreviation, exact name,
            // name prefix, abbreviation prefix, name contains.
            const rankOf = (program, term) => {
                const name = program.name.toLowerCase();
                const abbreviation = (program.abbreviation ?? '').toLowerCase();

                if (abbreviation === term) return 1;
                if (name === term) return 2;
                if (name.startsWith(term)) return 3;
                if (abbreviation && abbreviation.startsWith(term)) return 4;
                if (name.includes(term)) return 5;

                return null;
            };

            const matchingPrograms = (term) => {
                if (term === '') {
                    return programs;
                }

                return programs
                    .map((program) => ({ program, rank: rankOf(program, term) }))
                    .filter((scored) => scored.rank !== null)
                    .sort((a, b) => a.rank - b.rank || a.program.name.localeCompare(b.program.name))
                    .map((scored) => scored.program);
            };

            const closeSuggestions = () => {
                results = [];
                activeIndex = -1;
                suggestions.replaceChildren();
                suggestionsWrapper.hidden = true;
                empty.hidden = true;
                search.setAttribute('aria-expanded', 'false');
            };

            const showSearch = () => {
                selectedPanel.hidden = true;
                searchPanel.hidden = false;
                help.hidden = false;
                programId.value = '';
                search.value = '';
                search.disabled = false;
                search.required = true;
                closeSuggestions();
                search.focus();
            };

            const selectProgram = (program) => {
                programId.value = program.id;
                selectedName.textContent = program.name;
                selectedAbbreviation.textContent = program.abbreviation ?? '';
                selectedPanel.hidden = false;
                searchPanel.hidden = true;
                help.hidden = true;
                search.value = '';
                search.disabled = true;
                search.required = false;
                closeSuggestions();
            };

            const highlight = (index) => {
                activeIndex = index;

                suggestions.querySelectorAll('[role="option"]').forEach((option, optionIndex) => {
                    const isActive = optionIndex === index;
                    option.setAttribute('aria-selected', isActive ? 'true' : 'false');
                    option.classList.toggle(isDark ? 'bg-violet-400/10' : 'bg-gray-100', isActive);

                    if (isActive) {
                        option.scrollIntoView({ block: 'nearest' });
                    }
                });
            };

            const renderSuggestions = () => {
                const term = search.value.trim().replace(/\s+/g, ' ').toLowerCase();

                closeSuggestions();
                results = matchingPrograms(term);

                if (! results.length) {
                    empty.hidden = false;

                    return;
                }

                results.forEach((program, index) => {
                    const option = document.createElement('li');
                    option.setAttribute('role', 'option');
                    option.setAttribute('aria-selected', 'false');
                    option.className = isDark
                        ? 'cursor-pointer px-4 py-2.5 hover:bg-violet-400/10'
                        : 'cursor-pointer px-4 py-2 hover:bg-gray-100';

                    const name = document.createElement('span');
                    name.className = isDark
                        ? 'block font-medium text-slate-100'
                        : 'block font-medium text-gray-900';
                    name.textContent = program.name;
                    option.append(name);

                    if (program.abbreviation) {
                        const abbreviation = document.createElement('span');
                        abbreviation.className = isDark
                            ? 'block text-xs text-slate-500'
                            : 'block text-xs text-gray-600';
                        abbreviation.textContent = program.abbreviation;
                        option.append(abbreviation);
                    }

                    // mousedown keeps the input from losing the click to blur handling.
                    option.addEventListener('mousedown', (event) => {
                        event.preventDefault();
                        selectProgram(program);
                    });
                    option.addEventListener('mouseenter', () => highlight(index));
                    suggestions.append(option);
                });

                suggestionsWrapper.hidden = false;
                search.setAttribute('aria-expanded', 'true');
            };

            search.addEventListener('input', renderSuggestions);
            search.addEventListener('focus', renderSuggestions);

            search.addEventListener('keydown', (event) => {
                if (event.key === 'ArrowDown' && results.length) {
                    event.preventDefault();
                    highlight(Math.min(activeIndex + 1, results.length - 1));
                } else if (event.key === 'ArrowUp' && results.length) {
                    event.preventDefault();
                    highlight(Math.max(activeIndex - 1, 0));
                } else if (event.key === 'Enter' && activeIndex >= 0) {
                    event.preventDefault();
                    selectProgram(results[activeIndex]);
                } else if (event.key === 'Escape') {
                    closeSuggestions();
                }
            });

            search.addEventListener('blur', () => setTimeout(closeSuggestions, 100));

            picker.querySelector('[data-program-change]').addEventListener('click', showSearch);
        });
    </script>
@endonce
