<?php

namespace App\Services;

use App\Models\School;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

/**
 * Deterministic autocomplete over the local school catalog.
 *
 * Ranking tiers (lowest wins), ties broken alphabetically:
 * 1. exact abbreviation, 2. exact name, 3. name prefix,
 * 4. abbreviation prefix, 5. name contains, 6. search keywords contain.
 */
class SchoolSearchService
{
    public const RESULT_LIMIT = 10;

    /**
     * Ranked active schools for a search term.
     *
     * @return Collection<int, School>
     */
    public function search(string $query, int $limit = self::RESULT_LIMIT): Collection
    {
        $term = Str::of($query)->squish()->lower()->value();

        if ($term === '') {
            return new Collection;
        }

        // "!" is the LIKE escape character so user-typed % and _ stay literal.
        $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term);
        $prefix = $escaped.'%';
        $contains = '%'.$escaped.'%';

        return School::query()
            ->active()
            ->where(function (Builder $query) use ($term, $prefix, $contains): void {
                $query->whereRaw('LOWER(abbreviation) = ?', [$term])
                    ->orWhereRaw("LOWER(name) LIKE ? ESCAPE '!'", [$contains])
                    ->orWhereRaw("LOWER(abbreviation) LIKE ? ESCAPE '!'", [$prefix])
                    ->orWhereRaw("LOWER(search_keywords) LIKE ? ESCAPE '!'", [$contains]);
            })
            ->orderByRaw(
                "CASE
                    WHEN LOWER(abbreviation) = ? THEN 1
                    WHEN LOWER(name) = ? THEN 2
                    WHEN LOWER(name) LIKE ? ESCAPE '!' THEN 3
                    WHEN LOWER(abbreviation) LIKE ? ESCAPE '!' THEN 4
                    WHEN LOWER(name) LIKE ? ESCAPE '!' THEN 5
                    ELSE 6
                END",
                [$term, $term, $prefix, $prefix, $contains]
            )
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }
}
