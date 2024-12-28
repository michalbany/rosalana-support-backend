<?php

namespace App\Http\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Methods are invoked dynamically based on the query parameters.
 * If the method does not exist, it will be ignored.
 */
abstract class ApiFilter
{
    protected Builder $builder;
    protected Request $request;
    /**
     * Attributes that can be sorted by.
     * @var array<string>
     */
    protected array $sortable = [];
    /**
     * Preset filters that can be applied by `preset` method.
     * @var array<string>
     */
    protected array $presets = [];
    /**
     * Attributes that can by searched by.
     * @var array<string>
     */
    protected array $searchable = [];
    /**
     * Filters that can be applied by the query.
     * @var array<string>
     */
    protected array $filters = [];
    /**
     * Methods that can be called by the query.
     * @var array<string>
     */
    private array $methods = [
        'include' => 'Include related resources',
        'only' => 'Only specific records with the given ids',
        'omit' => 'Omit specific records with the given ids',
        'filter' => 'Apply the filters to the builder instance',
        'preset' => 'Apply the predefined preset filter to the builder instance',
        'limit' => 'Limit the number of records',
        'offset' => 'Skips the specified number of results.',
        'pick' => 'Pick the attributes to return',
        'sort' => 'Sort the collection by the given attributes',
        'search' => 'Search for the given value in the searchable columns',
        'help' => 'Show information about the available filters',
    ];

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Apply the filters to the builder instance.
     */
    public function apply(Builder $builder): Builder
    {
        $this->builder = $builder;

        foreach ($this->request->all() as $key => $value) {
            if (method_exists($this, $key) && in_array($key, array_keys($this->methods))) {
                try {
                    $this->$key($value);
                } catch (\App\Exceptions\ApiFilterHelpException $e) {
                    throw $e;
                } catch (\ValueError $e) {
                    throw new \InvalidArgumentException("Invalid value provided for the query '$key'. Value does not match the expected type.");
                } catch (\Throwable $e) {
                    throw new \Exception("Invalid data provided for the query '$key'. Please check the input values.");
                }
            }
        }

        return $builder;
    }

    /**
     * Search for the given value in the registered columns.
     * @param string $value
     */
    public function search(string $value): Builder
    {
        $columns = $this->searchable;
        $baseWeight = 20;
        $weightStep = 2;

        if (empty($columns)) {
            return $this->builder->where('id', -1);
        }

        // Build relevance scoring with weights based on column priority
        $relevanceScore = '';
        $bindings = [];

        foreach ($columns as $index => $column) {
            $weight = $baseWeight - ($index * $weightStep);
            if ($weight < 0) {
                $weight = 0;
            }
            // Přesná shoda (přesný název)
            $relevanceScore .= "CASE WHEN `$column` = ? THEN " . ($weight + 50) . " ELSE 0 END + ";
            $bindings[] = $value;

            // Shoda na začátku sloupce
            $relevanceScore .= "CASE WHEN `$column` LIKE ? THEN " . ($weight + 5) . " ELSE 0 END + ";
            $bindings[] = "$value%";

            // Částečná shoda
            $relevanceScore .= "CASE WHEN `$column` LIKE ? THEN $weight ELSE 0 END + ";
            $bindings[] = "%$value%";
        }
        $relevanceScore = rtrim($relevanceScore, ' + ');

        return $this->builder
            ->select('*', DB::raw("($relevanceScore) AS relevance"))
            ->where(function ($query) use ($columns, $value) {
                foreach ($columns as $column) {
                    $query->orWhere($column, 'LIKE', "%$value%");
                }
            })
            ->orderByDesc('relevance')
            ->addBinding($bindings, 'select');
    }


    public function include(string $value): Builder
    {
        $relations = explode(',', $value);

        foreach ($relations as $relation) {
            try {
                $this->builder->getRelation($relation);
            } catch (RelationNotFoundException $e) {
                continue; // ingore the relation if it does not exist
            }

            $this->builder->with($relation);
        }

        return $this->builder;
    }

    /**
     * Only specific records with the given ids.
     */
    protected function only(string $value): Builder
    {
        return $this->builder->whereIn('id', explode(',', $value));
    }

    /**
     * Omit specific records with the given ids.
     */
    protected function omit(string $value): Builder
    {
        return $this->builder->whereNotIn('id', explode(',', $value));
    }

    /**
     * Shorthand for the filter method.
     * @param array<string,mixed> $arr
     */
    protected function filter(array $arr): Builder
    {
        foreach ($arr as $key => $value) {
            if (method_exists($this, $key) && in_array($key, array_keys($this->filters))) {
                $this->$key($value);
            }
        }

        return $this->builder;
    }

    /**
     * Apply the preset to the builder instance.
     * For complex filters
     */
    protected function preset(string $value): Builder
    {
        if (method_exists($this, $value) && in_array($value, array_keys($this->presets))) {
            $this->$value();
        }

        return $this->builder;
    }

    protected function limit(int $value): Builder
    {
        return $this->builder->limit($value);
    }

    protected function offset(int $value): Builder
    {
        return $this->builder->offset($value);
    }

    /**
     * Sort the collection by the given attributes.
     */
    protected function sort(string $value): void
    {
        $sortAttributes = explode(',', $value);

        foreach ($sortAttributes as $sortAttribute) {
            $direction = 'asc';

            if (strpos($sortAttribute, '-') === 0) {
                $direction = 'desc';
                $sortAttribute = substr($sortAttribute, 1);
            }

            if (!in_array($sortAttribute, $this->sortable)) {
                continue;
            }

            $this->builder->orderBy($sortAttribute, $direction);
        }
    }

    /**
     * When ?help everything stops and the help method is returned.
     */
    protected function help(): JsonResponse
    {
        $data = [
            'methods' => $this->methods,
            'filters' => $this->filters,
            'presets' => $this->presets,
            'sortable' => $this->sortable,
            'searchable' => $this->searchable,
        ];

        foreach ($data['filters'] as $key => $value) {
            if (enum_exists($value)) {
                $cases = implode(', ', array_column($value::cases(), 'value'));
                $data['filters'][$key] = "Filter results by $key. Acceptable values are: $cases";
            }
        }

        throw new \App\Exceptions\ApiFilterHelpException($data);
    }
}
