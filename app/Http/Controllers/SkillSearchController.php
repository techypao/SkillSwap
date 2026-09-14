<?php

namespace App\Http\Controllers;

use App\Models\Skill;
use App\Services\SkillSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SkillSearchController extends Controller
{
    public function __construct(private SkillSearchService $skillSearch) {}

    /**
     * Read-only typo-tolerant lookup used by the skill pickers.
     *
     * Returns canonical skills only, capped, with nothing beyond what the
     * picker needs to render a choice.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $results = $this->skillSearch
            ->search((string) ($validated['q'] ?? ''))
            ->map(fn (Skill $skill): array => [
                'id' => $skill->id,
                'name' => $skill->name,
                'category' => $skill->category?->name,
            ])
            ->all();

        return response()->json($results);
    }
}
