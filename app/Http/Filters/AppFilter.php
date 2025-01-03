<?php

namespace App\Http\Filters;

class AppFilter extends ApiFilter
{

    /**
     * Array of strings that represent the columns that can be sorted.
     */
    protected array $sortable = [];

    /**
     * Array of strings that represent the columns that can be searched.
     * Sorted by relevance.
     */
    protected array $searchable = [];

    /**
     * An array of 'method' => 'description' pairs that represent the filters that can be applied.
     * You can use `enum::class` as a description
     */
    protected array $filters = [
        'name' => 'Filter App by name',
    ];

    /**
     * An array of 'method' => 'description' pairs that represent the presets that can be applied.
     */
    protected array $presets = [];

    /**
     * Filter functions ↓↓
     */
    public function name(string $value)
    {
        return $this->builder->where('name', 'like', "%$value%");
    }
}
