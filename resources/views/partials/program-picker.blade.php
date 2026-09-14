{{--
    Program picker.

    Type-to-search over the active programs already loaded for the page. It
    submits program_id only; the server still validates it against active
    programs.
--}}
@php
    $selectedProgramId = (string) old('program_id', $user->program_id);
    $selectedProgram = $programs->first(fn ($program): bool => (string) $program->id === $selectedProgramId);
    $programOptions = $programs->map(fn ($program): array => [
        'id' => $program->id,
        'name' => $program->name,
        'abbreviation' => $program->abbreviation,
    ])->values();
@endphp

<div class="mb-6" data-program-picker>
    <label for="program_search" class="block text-sm font-semibold text-gray-700 mb-2">
        Program
    </label>

    <input type="hidden" name="program_id" value="{{ $selectedProgram?->id }}" data-program-id>

    <script type="application/json" data-program-options>@json($programOptions)</script>

    {{-- Selected program --}}
    <div data-program-selected @if (! $selectedProgram) hidden @endif>
        <div class="flex items-center justify-between gap-3 border border-gray-300 bg-gray-50 rounded-xl px-4 py-3">
            <div>
                <p class="font-semibold text-gray-900" data-program-selected-name>{{ $selectedProgram?->name }}</p>
                <p class="text-xs text-gray-600" data-program-selected-abbreviation>{{ $selectedProgram?->abbreviation }}</p>
            </div>

            <button type="button" class="shrink-0 text-sm font-semibold text-gray-700 underline" data-program-change>
                Change
            </button>
        </div>
    </div>

    {{-- Search --}}
    <div data-program-search-panel @if ($selectedProgram) hidden @endif>
        <div class="relative">
            <input
                type="text"
                id="program_search"
                autocomplete="off"
                placeholder="Search your program..."
                role="combobox"
                aria-autocomplete="list"
                aria-expanded="false"
                aria-controls="program_suggestions"
                class="w-full border border-gray-300 rounded-xl px-4 py-3
                       focus:outline-none focus:ring-2 focus:ring-gray-900"
                data-program-search
            >

            <div data-program-suggestions-wrapper hidden>
                <ul
                    id="program_suggestions"
                    role="listbox"
                    class="absolute z-10 mt-1 w-full max-h-72 overflow-y-auto bg-white border border-gray-200 rounded-xl shadow-lg"
                    data-program-suggestions
                ></ul>
            </div>
        </div>

        <p class="text-sm text-gray-500 mt-2" data-program-empty hidden>No matching programs found.</p>
    </div>

    @error('program_id')
        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
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
                programId.value = '';
                search.value = '';
                search.focus();
            };

            const selectProgram = (program) => {
                programId.value = program.id;
                selectedName.textContent = program.name;
                selectedAbbreviation.textContent = program.abbreviation ?? '';
                selectedPanel.hidden = false;
                searchPanel.hidden = true;
                search.value = '';
                closeSuggestions();
            };

            const highlight = (index) => {
                activeIndex = index;

                suggestions.querySelectorAll('[role="option"]').forEach((option, optionIndex) => {
                    const isActive = optionIndex === index;
                    option.setAttribute('aria-selected', isActive ? 'true' : 'false');
                    option.classList.toggle('bg-gray-100', isActive);

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
                    option.className = 'cursor-pointer px-4 py-2 hover:bg-gray-100';

                    const name = document.createElement('span');
                    name.className = 'block font-medium text-gray-900';
                    name.textContent = program.name;
                    option.append(name);

                    if (program.abbreviation) {
                        const abbreviation = document.createElement('span');
                        abbreviation.className = 'block text-xs text-gray-600';
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
