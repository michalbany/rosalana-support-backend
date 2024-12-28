<?php

namespace App\Http\Filters;

class DocFilter extends ApiFilter
{

    /**
     * Array of strings that represent the columns that can be sorted.
     */
    protected array $sortable = ['id', 'name', 'email'];

    /**
     * Array of strings that represent the columns that can be searched.
     * Sorted by relevance.
     */
    protected array $searchable = ['name', 'email'];

    /**
     * An array of 'method' => 'description' pairs that represent the filters that can be applied.
     * You can use `enum::class` as a description
     */
    protected array $filters = [];

    /**
     * An array of 'method' => 'description' pairs that represent the presets that can be applied.
     */
    protected array $presets = [];

    /**
     * Filter functions ↓↓
     */
}
