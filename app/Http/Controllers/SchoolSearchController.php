<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Services\SchoolSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SchoolSearchController extends Controller
{
    public function __construct(private SchoolSearchService $schoolSearch) {}

    /**
     * Read-only local school autocomplete for the profile forms.
     *
     * Returns active catalog schools only, capped, with just the fields the
     * picker needs to render a suggestion.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'max:100'],
        ]);

        $results = $this->schoolSearch
            ->search($validated['q'])
            ->map(fn (School $school): array => [
                'id' => $school->id,
                'name' => $school->name,
                'abbreviation' => $school->abbreviation,
                'city' => $school->city,
                'province' => $school->province,
            ])
            ->all();

        return response()->json($results);
    }
}
