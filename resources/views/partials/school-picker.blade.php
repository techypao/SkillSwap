{{--
    School / University picker.

    A catalog pick submits school_id. Any other typed value submits as a custom
    school_organization, so students are not limited to the suggestion list.
--}}
@php
    $customSchoolName = $selectedSchool ? '' : old('school_organization', $user->school_organization);
    $isDark = ($theme ?? null) === 'dark';
@endphp

<div class="mb-7" data-school-picker data-theme="{{ $isDark ? 'dark' : 'light' }}" data-search-url="{{ route('schools.search') }}">
    <label for="school_search" class="mb-2 block text-sm font-medium {{ $isDark ? 'text-slate-200' : 'text-gray-700' }}">
        School / University
    </label>

    <input type="hidden" name="school_id" value="{{ $selectedSchool?->id }}" data-school-id>

    {{-- Selected canonical school --}}
    <div data-school-selected @if (! $selectedSchool) hidden @endif>
        <div class="flex items-center justify-between gap-3 rounded-xl border px-4 py-3 {{ $isDark ? 'border-violet-400/20 bg-violet-400/8' : 'border-gray-300 bg-gray-50' }}">
            <div>
                <p class="font-semibold {{ $isDark ? 'text-slate-100' : 'text-gray-900' }}" data-school-selected-name>{{ $selectedSchool?->name }}</p>
                <p class="text-xs {{ $isDark ? 'text-slate-500' : 'text-gray-600' }}" data-school-selected-location>{{ $selectedSchool?->location }}</p>
            </div>

            <button type="button" class="shrink-0 text-sm font-semibold {{ $isDark ? 'text-violet-300 hover:text-violet-200' : 'text-gray-700 underline' }}" data-school-change>
                Change
            </button>
        </div>
    </div>

    {{-- Search --}}
    <div data-school-search-panel @if ($selectedSchool) hidden @endif>
        <div class="relative">
            <input
                type="text"
                name="school_organization"
                id="school_search"
                value="{{ $customSchoolName }}"
                autocomplete="off"
                placeholder="Search or enter your school..."
                maxlength="255"
                role="combobox"
                aria-autocomplete="list"
                aria-expanded="false"
                aria-controls="school_suggestions"
                class="w-full rounded-xl border px-4 py-3 text-sm outline-none transition {{ $isDark ? 'border-white/10 bg-[#0b1120]/80 text-white placeholder:text-slate-600 hover:border-white/20 focus:border-violet-400/60 focus:ring-4 focus:ring-violet-500/10' : 'border-gray-300 focus:ring-2 focus:ring-gray-900' }}"
                @disabled($selectedSchool)
                @required(! $selectedSchool)
                data-school-search
            >

            <div data-school-suggestions-wrapper hidden>
                <ul
                    id="school_suggestions"
                    role="listbox"
                    class="absolute z-20 mt-2 max-h-72 w-full overflow-y-auto rounded-xl border shadow-2xl {{ $isDark ? 'border-white/10 bg-[#11182d] shadow-black/40' : 'border-gray-200 bg-white shadow-lg' }}"
                    data-school-suggestions
                ></ul>
            </div>
        </div>

        <p class="mt-2 text-sm {{ $isDark ? 'text-slate-500' : 'text-gray-500' }}" data-school-empty hidden>
            No matching schools found. You can continue with the school name you entered.
        </p>
    </div>

    <p class="mt-2 text-sm {{ $isDark ? 'text-slate-500' : 'text-gray-600' }}" data-school-help @if ($selectedSchool) hidden @endif>
        Choose a suggestion or keep your typed school name.
    </p>

    @error('school_id')
        <p class="mt-2 text-sm {{ $isDark ? 'text-rose-300' : 'text-red-500' }}">{{ $message }}</p>
    @enderror

    @error('school_organization')
        <p class="mt-2 text-sm {{ $isDark ? 'text-rose-300' : 'text-red-500' }}">{{ $message }}</p>
    @enderror
</div>

@once
    <script>
        document.querySelectorAll('[data-school-picker]').forEach((picker) => {
            const searchUrl = picker.dataset.searchUrl;
            const schoolId = picker.querySelector('[data-school-id]');
            const selectedPanel = picker.querySelector('[data-school-selected]');
            const selectedName = picker.querySelector('[data-school-selected-name]');
            const selectedLocation = picker.querySelector('[data-school-selected-location]');
            const searchPanel = picker.querySelector('[data-school-search-panel]');
            const search = picker.querySelector('[data-school-search]');
            const suggestionsWrapper = picker.querySelector('[data-school-suggestions-wrapper]');
            const suggestions = picker.querySelector('[data-school-suggestions]');
            const empty = picker.querySelector('[data-school-empty]');
            const help = picker.querySelector('[data-school-help]');
            const isDark = picker.dataset.theme === 'dark';

            let results = [];
            let activeIndex = -1;
            let searchTimer = null;
            let latestQuery = 0;

            const locationOf = (school) => [school.city, school.province]
                .filter((part, index, parts) => part && parts.indexOf(part) === index)
                .join(', ');

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
                schoolId.value = '';
                search.disabled = false;
                search.required = true;
                closeSuggestions();
                search.focus();
            };

            const selectSchool = (school) => {
                schoolId.value = school.id;
                selectedName.textContent = school.name;
                selectedLocation.textContent = locationOf(school);
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

            const renderSuggestions = (schools, term) => {
                closeSuggestions();
                results = schools;

                if (! schools.length) {
                    empty.hidden = term.length < 2;

                    return;
                }

                schools.forEach((school, index) => {
                    const option = document.createElement('li');
                    option.id = `school_option_${school.id}`;
                    option.setAttribute('role', 'option');
                    option.setAttribute('aria-selected', 'false');
                    option.className = isDark
                        ? 'cursor-pointer px-4 py-2.5 hover:bg-violet-400/10'
                        : 'cursor-pointer px-4 py-2 hover:bg-gray-100';

                    const name = document.createElement('span');
                    name.className = isDark
                        ? 'block font-medium text-slate-100'
                        : 'block font-medium text-gray-900';
                    name.textContent = school.name;

                    const location = document.createElement('span');
                    location.className = isDark
                        ? 'block text-xs text-slate-500'
                        : 'block text-xs text-gray-600';
                    location.textContent = locationOf(school);

                    option.append(name, location);
                    // mousedown keeps the input from losing the click to blur handling.
                    option.addEventListener('mousedown', (event) => {
                        event.preventDefault();
                        selectSchool(school);
                    });
                    option.addEventListener('mouseenter', () => highlight(index));
                    suggestions.append(option);
                });

                suggestionsWrapper.hidden = false;
                search.setAttribute('aria-expanded', 'true');
            };

            const runSearch = () => {
                const term = search.value.trim();

                if (term.length < 2) {
                    closeSuggestions();

                    return;
                }

                const queryId = ++latestQuery;

                fetch(`${searchUrl}?q=${encodeURIComponent(term)}`, {
                    headers: { 'Accept': 'application/json' },
                })
                    .then((response) => response.ok ? response.json() : [])
                    // Ignore a slow response that a newer keystroke superseded.
                    .then((schools) => queryId === latestQuery && renderSuggestions(schools, term))
                    .catch(() => closeSuggestions());
            };

            search.addEventListener('input', () => {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(runSearch, 200);
            });

            search.addEventListener('keydown', (event) => {
                if (event.key === 'ArrowDown' && results.length) {
                    event.preventDefault();
                    highlight(Math.min(activeIndex + 1, results.length - 1));
                } else if (event.key === 'ArrowUp' && results.length) {
                    event.preventDefault();
                    highlight(Math.max(activeIndex - 1, 0));
                } else if (event.key === 'Enter' && activeIndex >= 0) {
                    event.preventDefault();
                    selectSchool(results[activeIndex]);
                } else if (event.key === 'Escape') {
                    closeSuggestions();
                }
            });

            search.addEventListener('blur', () => setTimeout(closeSuggestions, 100));

            picker.querySelector('[data-school-change]').addEventListener('click', showSearch);
        });
    </script>
@endonce
