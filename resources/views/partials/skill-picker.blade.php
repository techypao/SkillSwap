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
                Search this category
            </label>
            <input
                id="{{ $group }}_search"
                type="search"
                data-skill-search
                placeholder="e.g. Laravel"
                class="w-full border border-gray-300 rounded-xl px-4 py-3"
            >
        </div>
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

                categoryFilter.addEventListener('change', filterSkills);
                search.addEventListener('input', filterSkills);
                filterSkills();
            });
        </script>
    @endpush
@endonce
