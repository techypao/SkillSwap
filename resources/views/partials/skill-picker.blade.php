@php
    $selectedIds = array_map('strval', old("{$group}_skills", $selected));
@endphp

<section class="mb-8" data-skill-section="{{ $group }}">
    <div class="mb-6">
        <h2 class="text-xl font-semibold text-white">{{ $heading }}</h2>
        <p class="mt-1 text-sm text-slate-500">Select one or more skills. You can always update these later.</p>
    </div>

    @error("{$group}_skills")
        <div class="mb-5 rounded-xl border border-rose-400/20 bg-rose-400/10 px-4 py-3 text-sm text-rose-200" role="alert">{{ $message }}</div>
    @enderror

    <div class="mb-6">
        <p class="mb-3 block text-sm font-medium text-slate-200">Browse by category</p>
        <div class="flex flex-wrap gap-2" data-category-options aria-label="Skill categories">
            <button type="button" data-category-option="" aria-pressed="true" class="rounded-xl border border-white/10 bg-white/[0.025] px-3.5 py-2 text-xs font-medium text-slate-400 transition hover:border-violet-400/30 hover:text-slate-200 aria-pressed:border-violet-400/50 aria-pressed:bg-violet-400/15 aria-pressed:text-violet-200">
                All skills
            </button>
            @foreach ($categories as $category)
                <button type="button" data-category-option="{{ $category->slug }}" aria-pressed="false" class="rounded-xl border border-white/10 bg-white/[0.025] px-3.5 py-2 text-xs font-medium text-slate-400 transition hover:border-violet-400/30 hover:text-slate-200 aria-pressed:border-violet-400/50 aria-pressed:bg-violet-400/15 aria-pressed:text-violet-200">
                    {{ $category->name }}
                </button>
            @endforeach
            <button type="button" data-category-option="uncategorized" aria-pressed="false" class="rounded-xl border border-white/10 bg-white/[0.025] px-3.5 py-2 text-xs font-medium text-slate-400 transition hover:border-violet-400/30 hover:text-slate-200 aria-pressed:border-violet-400/50 aria-pressed:bg-violet-400/15 aria-pressed:text-violet-200">
                My suggested skills
            </button>
        </div>

        <select id="{{ $group }}_category" data-category-filter class="sr-only" tabindex="-1" aria-hidden="true">
            <option value="">All skills</option>
            @foreach ($categories as $category)
                <option value="{{ $category->slug }}">{{ $category->name }}</option>
            @endforeach
            <option value="uncategorized">My suggested skills</option>
        </select>
    </div>

    <div class="mb-5">
        <div class="max-w-xl">
            <label for="{{ $group }}_search" class="mb-2 block text-sm font-medium text-slate-200">
                Search skills
            </label>
            {{-- Deliberately unnamed: typing here never submits a skill, only selecting one does. --}}
            <input
                id="{{ $group }}_search"
                type="search"
                data-skill-search
                autocomplete="off"
                placeholder="Try Laravel, JavaScript, Figma, or Excel"
                class="w-full rounded-xl border border-white/10 bg-[#0b1120]/80 px-4 py-3 text-sm text-white outline-none transition placeholder:text-slate-600 hover:border-white/20 focus:border-violet-400/60 focus:ring-4 focus:ring-violet-500/10"
            >
        </div>
    </div>

    <div
        class="mb-5 hidden rounded-xl border border-indigo-400/20 bg-indigo-400/10 p-4"
        data-skill-suggestions
        hidden
    >
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-indigo-300">Did you mean</p>
        <div class="mt-2 space-y-2" data-suggestion-list></div>
    </div>

    <div class="mb-3 flex items-center justify-between gap-3">
        <h3 class="text-sm font-semibold text-slate-200">Available skills</h3>
        <p class="text-xs text-slate-500"><span data-skill-count>{{ $skills->count() }}</span> shown</p>
    </div>

    <div class="max-h-[430px] overflow-y-auto pr-1">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3" data-skill-list>
            @foreach ($skills as $skill)
                @php
                    $categorySlug = $skill->category?->slug ?? 'uncategorized';
                @endphp

                <label
                    class="group flex cursor-pointer items-start gap-3 rounded-xl border border-white/8 bg-white/[0.025] p-4 transition hover:border-violet-400/30 hover:bg-white/[0.045] has-checked:border-violet-400/60 has-checked:bg-violet-400/12"
                    data-skill-card
                    data-category="{{ $categorySlug }}"
                    data-skill-name="{{ mb_strtolower($skill->name) }}"
                >
                    <input
                        type="checkbox"
                        name="{{ $group }}_skills[]"
                        value="{{ $skill->id }}"
                        class="mt-0.5 size-4 shrink-0 accent-violet-500"
                        data-skill-checkbox
                        @checked(in_array((string) $skill->id, $selectedIds, true))
                    >
                    <span class="min-w-0">
                        <span class="block truncate font-medium text-slate-200 group-hover:text-white">{{ $skill->name }}</span>
                        <span class="mt-1 block text-[11px] leading-4 text-slate-500">{{ $skill->category?->name ?? 'Other / Suggested' }}</span>
                        @if (! $skill->is_approved)
                            <span class="mt-1 block text-xs text-amber-300">Pending approval</span>
                        @endif
                    </span>
                </label>
            @endforeach
        </div>

        <div class="rounded-2xl border border-dashed border-white/10 px-5 py-8 text-center" data-skill-empty hidden>
            <svg class="mx-auto size-7 text-slate-600" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.6"/><path d="m16 16 4 4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
            <p class="mt-3 text-sm font-medium text-slate-300">No skills match those filters.</p>
            <p class="mt-1 text-xs text-slate-600">Try another search or browse all skills.</p>
            <button type="button" data-clear-skill-filters class="mt-4 rounded-lg border border-violet-400/20 bg-violet-400/10 px-3 py-2 text-xs font-semibold text-violet-200 transition hover:bg-violet-400/20">Show all skills</button>
        </div>
    </div>

    <div class="mt-7 border-t border-white/8 pt-6">
        <h3 class="font-semibold text-slate-200">Can't find your skill?</h3>
        <p class="mt-1 text-xs leading-5 text-slate-500">
            Suggestions remain pending approval. Separate multiple suggestions with commas.
        </p>

        <div class="mt-3">
            <input
                type="text"
                name="new_{{ $group }}_skills"
                value="{{ old("new_{$group}_skills") }}"
                maxlength="500"
                placeholder="Enter a skill"
                class="w-full rounded-xl border border-white/10 bg-[#0b1120]/80 px-4 py-3 text-sm text-white outline-none transition placeholder:text-slate-600 hover:border-white/20 focus:border-violet-400/60 focus:ring-4 focus:ring-violet-500/10"
            >
        </div>

        @error("new_{$group}_skills")
            <p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
        @enderror
    </div>
</section>

@once
    @push('scripts')
        <script>
            document.querySelectorAll('[data-skill-section]').forEach((section) => {
                const categoryFilter = section.querySelector('[data-category-filter]');
                const categoryOptions = section.querySelectorAll('[data-category-option]');
                const search = section.querySelector('[data-skill-search]');
                const cards = section.querySelectorAll('[data-skill-card]');
                const skillCount = section.querySelector('[data-skill-count]');
                const emptyState = section.querySelector('[data-skill-empty]');
                const clearFilters = section.querySelector('[data-clear-skill-filters]');
                const suggestionBox = section.querySelector('[data-skill-suggestions]');
                const suggestionList = section.querySelector('[data-suggestion-list]');
                const searchUrl = @json(route('skills.search'));

                const filterSkills = () => {
                    const category = categoryFilter.value;
                    const term = search.value.trim().toLowerCase();
                    let visibleCount = 0;

                    cards.forEach((card) => {
                        const checkbox = card.querySelector('[data-skill-checkbox]');
                        const matchesCategory = term !== '' || category === '' || card.dataset.category === category;
                        const matchesSearch = card.dataset.skillName.includes(term);
                        const isVisible = checkbox.checked || (matchesCategory && matchesSearch);

                        card.hidden = ! isVisible;
                        visibleCount += isVisible ? 1 : 0;
                    });

                    skillCount.textContent = visibleCount;
                    emptyState.hidden = visibleCount !== 0;
                };

                const selectCategory = (value) => {
                    categoryFilter.value = value;
                    search.value = '';

                    categoryOptions.forEach((option) => {
                        option.setAttribute('aria-pressed', String(option.dataset.categoryOption === value));
                    });

                    renderSuggestions([]);
                    filterSkills();
                };

                cards.forEach((card) => {
                    const checkbox = card.querySelector('[data-skill-checkbox]');

                    checkbox.addEventListener('change', filterSkills);
                });

                categoryOptions.forEach((option) => {
                    option.addEventListener('click', () => selectCategory(option.dataset.categoryOption));
                });

                clearFilters.addEventListener('click', () => selectCategory(''));

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
                        name.className = 'font-medium text-slate-100';
                        name.textContent = result.name;
                        const category = document.createElement('span');
                        category.className = 'block text-xs text-slate-500';
                        category.textContent = result.category ?? 'Other / Suggested';
                        text.append(name, category);

                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className =
                            'shrink-0 rounded-lg bg-violet-500 px-3 py-1.5 text-xs font-semibold text-white hover:bg-violet-400';
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

                search.addEventListener('input', () => {
                    if (search.value.trim() !== '' && categoryFilter.value !== '') {
                        categoryFilter.value = '';
                        categoryOptions.forEach((option) => {
                            option.setAttribute('aria-pressed', String(option.dataset.categoryOption === ''));
                        });
                    }

                    filterSkills();
                    clearTimeout(searchTimer);
                    searchTimer = setTimeout(runSmartSearch, 200);
                });
                filterSkills();
            });
        </script>
    @endpush
@endonce
