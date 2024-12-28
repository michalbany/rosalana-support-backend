<?php

namespace App\Http\Filters;

class UserFilter extends ApiFilter
{

    protected array $sortable = ['id', 'name', 'email'];

    protected array $searchable = ['name', 'email'];

    protected array $filters = [];

    protected array $presets = [];

    /**
     * Filter functions ↓↓
     */
}
