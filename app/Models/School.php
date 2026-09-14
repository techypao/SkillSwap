<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    protected $fillable = [
        'name',
        'abbreviation',
        'search_keywords',
        'region',
        'province',
        'city',
        'external_code',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Only schools that may be selected by students.
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Short "City, Province" label for suggestion lists.
     */
    protected function location(): Attribute
    {
        return Attribute::get(fn (): string => collect([$this->city, $this->province])
            ->filter()
            ->unique()
            ->implode(', '));
    }
}
