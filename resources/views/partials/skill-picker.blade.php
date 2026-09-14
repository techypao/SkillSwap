@php
    $selectedIds = array_map('strval', old("{$group}_skills", $selected));
@endphp

<section class="mb-8" data-skill-section="{{ $group }}">
    <div class="mb-5">
        <h2 class="text-xl font-bold text-gray-900">{{ $heading }}</h2>
        <p class="text-sm text-gray-500 mt-1">Select one or more skills and a level for each.</p>
    </div>

    @error("{$group}_skills")
        <div class="mb-4 bg-red-100 text-red-700 px-4 py-3 rounded-xl">{{ $message }}</div>
    @enderror

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-5">
        <div>
            <label for="{{ $group }}_category" class="block text-sm font-semibold text-gray-700 mb-2">
                Category
            </label>
            <select
                id="{{ $group }}_category"
                data-category-filter
                class="w-full border border-gray-300 rounded-xl px-4 py-3"
            >
                <option value="">All categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->slug }}">{{ $category->name }}</option>
                @endforeach
                <option value="uncategorized">My suggested skills</option>
            </select>
        </div>

        <div>
            <label for="{{ $group }}_search" class="block text-sm font-semibold text-gray-700 mb-2">
                Search skills
            </label>
            {{-- Deliberately unnamed: typing here never submits a skill, only selecting one does. --}}
            <input
                id="{{ $group }}_search"
                type="search"
                data-skill-search
                autocomplete="off"
                placeholder="Try larvel, JS or MS Excel"
                class="w-full border border-gray-300 rounded-xl px-4 py-3"
            >
        </div>
    </div>

    <div
        class="mb-5 hidden rounded-xl border border-blue-200 bg-blue-50 p-4"
        data-skill-suggestions
        hidden
    >
        <p class="text-xs font-semibold uppercase tracking-wide text-blue-800">Did you mean</p>
        <div class="mt-2 space-y-2" data-suggestion-list></div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" data-skill-list>
        @foreach ($skills as $skill)
            @php
                $categorySlug = $skill->category?->slug ?? 'uncategorized';
            @endphp

            <div
                class="border border-gray-200 rounded-xl p-4"
                data-skill-card
                data-category="{{ $categorySlug }}"
                data-skill-name="{{ mb_strtolower($skill->name) }}"
            >
                <label class="flex items-start gap-3 cursor-pointer">
                    <input
                        type="checkbox"
                        name="{{ $group }}_skills[]"
                        value="{{ $skill->id }}"
                        class="w-4 h-4 mt-1"
                        data-skill-checkbox
                        @checked(in_array((string) $skill->id, $selectedIds, true))
                    >
                    <span>
                        <span class="font-medium text-gray-800">{{ $skill->name }}</span>
                        <span class="block text-xs text-gray-500">
                            {{ $skill->category?->name ?? 'Other / Suggested' }}
                        </span>
                        @if (! $skill->is_approved)
                            <span class="block text-xs text-amber-600">Pending approval</span>
                        @endif
                    </span>
                </label>
            </div>
        @endforeach
    </div>

    <div class="mt-6 border-t border-gray-200 pt-5">
        <h3 class="font-semibold text-gray-900">Can't find your skill? Suggest a skill</h3>
        <p class="text-xs text-gray-500 mt-1">
            Suggestions remain pending approval. Separate multiple suggestions with commas.
        </p>

        <div class="mt-3">
            <input
                type="text"
                name="new_{{ $group }}_skills"
                value="{{ old("new_{$group}_skills") }}"
                maxlength="500"
                placeholder="Enter a skill"
                class="w-full border border-gray-300 rounded-xl px-4 py-3"
            >
        </div>

        @error("new_{$group}_skills")
            <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
        @enderror
    </div>
</section>

@once
    @push('scripts')
        <script>
            document.querySelectorAll('[data-skill-section]').forEach((section) => {
                const categoryFilter = section.querySelector('[data-category-filter]');
                const search = section.querySelector('[data-skill-search]');
                const cards = section.querySelectorAll('[data-skill-card]');
                const suggestionBox = section.querySelector('[data-skill-suggestions]');
                const suggestionList = section.querySelector('[data-suggestion-list]');
                const searchUrl = @json(route('skills.search'));

                const filterSkills = () => {
                    const category = categoryFilter.value;
                    const term = search.value.trim().toLowerCase();

                    cards.forEach((card) => {
                        const checkbox = card.querySelector('[data-skill-checkbox]');
                        const matchesCategory = category === '' || card.dataset.category === category;
                        const matchesSearch = card.dataset.skillName.includes(term);
                        card.hidden = ! (checkbox.checked || (matchesCategory && matchesSearch));
                    });
                };

                cards.forEach((card) => {
                    const checkbox = card.querySelector('[data-skill-checkbox]');

                    checkbox.addEventListener('change', filterSkills);
                });

                const selectSkill = (id) => {
                    const checkbox = section.querySelector(
                        `[data-skill-checkbox][value="${id}"]`
                    );

                    if (! checkbox) {
                        return;
                    }

                    checkbox.checked = true;
                    checkbox.dispatchEvent(new Event('change', { bubbles: true }));
                    checkbox.closest('[data-skill-card]')
                        ?.scrollIntoView({ block: 'nearest' });
                };

                const renderSuggestions = (results) => {
                    suggestionList.replaceChildren();

                    if (! results.length) {
                        suggestionBox.hidden = true;

                        return;
                    }

                    results.forEach((result) => {
                        const row = document.createElement('div');
                        row.className = 'flex items-center justify-between gap-3';

                        const text = document.createElement('span');
                        const name = document.createElement('span');
                        name.className = 'font-medium text-gray-900';
                        name.textContent = result.name;
                        const category = document.createElement('span');
                        category.className = 'block text-xs text-gray-600';
                        category.textContent = result.category ?? 'Other / Suggested';
                        text.append(name, category);

                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className =
                            'shrink-0 rounded-lg bg-gray-900 px-3 py-1.5 text-xs font-semibold text-white';
                        button.textContent = 'Select';
                        button.addEventListener('click', () => selectSkill(result.id));

                        row.append(text, button);
                        suggestionList.append(row);
                    });

                    suggestionBox.hidden = false;
                };

                let searchTimer = null;
                let latestQuery = 0;

                const runSmartSearch = () => {
                    const term = search.value.trim();

                    if (term.length < 2) {
                        renderSuggestions([]);

                        return;
                    }

                    const queryId = ++latestQuery;

                    fetch(`${searchUrl}?q=${encodeURIComponent(term)}`, {
                        headers: { 'Accept': 'application/json' },
                    })
                        .then((response) => response.ok ? response.json() : [])
                        // Ignore a slow response that a newer keystroke superseded.
                        .then((results) => queryId === latestQuery && renderSuggestions(results))
                        .catch(() => renderSuggestions([]));
                };

                categoryFilter.addEventListener('change', filterSkills);
                search.addEventListener('input', () => {
                    filterSkills();
                    clearTimeout(searchTimer);
                    searchTimer = setTimeout(runSmartSearch, 200);
                });
                filterSkills();
            });
        </script>
    @endpush
@endonce
