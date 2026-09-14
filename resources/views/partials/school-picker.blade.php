{{--
    School / University picker.

    A catalog pick submits school_id. Any other typed value submits as a custom
    school_organization, so students are not limited to the suggestion list.
--}}
@php
    $customSchoolName = $selectedSchool ? '' : old('school_organization', $user->school_organization);
@endphp

<div class="mb-6" data-school-picker data-search-url="{{ route('schools.search') }}">
    <label for="school_search" class="block text-sm font-semibold text-gray-700 mb-2">
        School / University
    </label>

    <input type="hidden" name="school_id" value="{{ $selectedSchool?->id }}" data-school-id>

    {{-- Selected canonical school --}}
    <div data-school-selected @if (! $selectedSchool) hidden @endif>
        <div class="flex items-center justify-between gap-3 border border-gray-300 bg-gray-50 rounded-xl px-4 py-3">
            <div>
                <p class="font-semibold text-gray-900" data-school-selected-name>{{ $selectedSchool?->name }}</p>
                <p class="text-xs text-gray-600" data-school-selected-location>{{ $selectedSchool?->location }}</p>
            </div>

            <button type="button" class="shrink-0 text-sm font-semibold text-gray-700 underline" data-school-change>
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
                class="w-full border border-gray-300 rounded-xl px-4 py-3
                       focus:outline-none focus:ring-2 focus:ring-gray-900"
                @disabled($selectedSchool)
                @required(! $selectedSchool)
                data-school-search
            >

            <div data-school-suggestions-wrapper hidden>
                <ul
                    id="school_suggestions"
                    role="listbox"
                    class="absolute z-10 mt-1 w-full max-h-72 overflow-y-auto bg-white border border-gray-200 rounded-xl shadow-lg"
                    data-school-suggestions
                ></ul>
            </div>
        </div>

        <p class="text-sm text-gray-500 mt-2" data-school-empty hidden>
            No matching schools found. You can continue with the school name you entered.
        </p>
    </div>

    <p class="text-sm text-gray-600 mt-2" data-school-help @if ($selectedSchool) hidden @endif>
        Choose a suggestion or keep your typed school name.
    </p>

    @error('school_id')
        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
    @enderror

    @error('school_organization')
        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
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
                    option.classList.toggle('bg-gray-100', isActive);

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
                    option.className = 'cursor-pointer px-4 py-2 hover:bg-gray-100';

                    const name = document.createElement('span');
                    name.className = 'block font-medium text-gray-900';
                    name.textContent = school.name;

                    const location = document.createElement('span');
                    location.className = 'block text-xs text-gray-600';
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
