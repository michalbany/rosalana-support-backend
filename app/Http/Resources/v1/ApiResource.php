<?php

namespace App\Http\Resources\v1;

use App\Models\Traits\ApiSharedData;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Route;

class ApiResource extends JsonResource
{
    use ApiSharedData;

    /** @var bool */
    public $preserveKeys = false;
    /** @var array<mixed>|callable */
    protected $currentFilterData = null;

    protected $additionalAttributes = [];
    protected $additionalRelationships = [];
    protected $additionalLinks = [];


    /**
     * Create a new resource instance.
     *
     * @param  mixed  $resource
     * @return void
     */
    final public function __construct($resource)
    {
        parent::__construct($resource);

        $this->currentFilterData = request()->input('filter');
    }

    /**
     * Transform the resource into an array.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return array<string,mixed>|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'type' => $this->modelType($request),
            'id' => $this->resource->id ?? $this->resource['id'] ?? null,
            'attributes' => array_merge($this->attributes($request), $this->additionalAttributes),
            'relationships' => array_merge($this->relationships($request), $this->additionalRelationships),
            'links' => array_merge($this->links($request), $this->additionalLinks),
        ];
    }

    /**
     * Get the resource attributes.
     *
     * @param \Illuminate\Http\Request $request
     * @return array<mixed>
     * @phpstan-ignore-next-line
     */
    public function attributes($request)
    {
        if (is_callable($this->attributesData)) {
            return call_user_func($this->attributesData, $this->resource);
        }

        return $this->attributesData ?? $this->resource->attributesToArray();
    }

    /**
     * Get the resource relationships.
     *
     * @param \Illuminate\Http\Request $request
     * @return array<mixed>
     */
    public function relationships($request)
    {
        if (is_callable($this->relationshipsData)) {
            return call_user_func($this->relationshipsData, $this->resource);
        }

        return $this->relationshipsData ?? [];
    }

    /**
     * Get the resource links.
     *
     * @param \Illuminate\Http\Request $request
     * @return array<mixed>
     */
    public function links($request)
    {
        if (is_callable($this->linksData)) {
            return call_user_func($this->linksData, $this->resource);
        }

        return $this->linksData ?? [];
    }

    /**
     * Explicitly set the model type.
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    public function modelType($request)
    {
        return $this->getModelType();
    }

    /**
     * Set the attributes data by chaining.
     * 
     * @param array<mixed>|callable $data
     * @return $this
     */
    public function setAttributes(array|callable $data): self
    {
        $this->attributesData = $data;

        return $this;
    }

    /**
     * Add attributes data by chaining.
     */
    public function addAttributes(array|callable $data): self
    {
        if (is_callable($data)) {
            $data = call_user_func($data, $this->resource);
        }

        $this->additionalAttributes = array_merge($this->additionalAttributes, $data);

        return $this;
    }

    /**
     * Set the relationships data by chaining.
     * 
     * @param array<mixed>|callable $data
     * @return $this
     */
    public function setRelationships(array|callable $data): self
    {
        $this->relationshipsData = $data;

        return $this;
    }

    /**
     * Add relationships data by chaining.
     */
    public function addRelationships(array|callable $data): self
    {
        if (is_callable($data)) {
            $data = call_user_func($data, $this->resource);
        }

        $this->additionalRelationships = array_merge($this->additionalRelationships, $data);

        return $this;
    }

    /**
     * Set the links data by chaining.
     * 
     * @param array<mixed>|callable $data
     * @return $this
     */
    public function setLinks(array|callable $data): self
    {
        $this->linksData = $data;

        return $this;
    }

    /**
     * Add links data by chaining.
     */
    public function addLinks(array|callable $data): self
    {
        if (is_callable($data)) {
            $data = call_user_func($data, $this->resource);
        }

        $this->additionalLinks = array_merge($this->additionalLinks, $data);

        return $this;
    }

    /**
     * Set the model type by chaining.
     * 
     * @param string $type
     * @return $this
     */
    public function setModelType(string $type): self
    {
        $this->modelType = $type;

        return $this;
    }

    /**
     * Get the model type.
     * 
     * @return string
     */
    public function getModelType(): string
    {
        if ($this->modelType) {
            return $this->modelType;
        }

        if (is_array($this->resource)) {
            return isset($this->resource['type']) ? strtolower($this->resource['type']) : 'unknown';
        }

        return strtolower(class_basename($this->resource));
    }

    /**
     * Rewrite the collection method to return an instance of ApiCollection.
     * 
     * @param mixed $resource
     * @return ApiCollection
     * @see \Illuminate\Http\Resources\Json\JsonResource::collection()
     */
    public static function collection($resource): ApiCollection
    {
        return tap(new ApiCollection($resource, static::class), function ($collection) {
            if (property_exists(static::class, 'preserveKeys')) {
                $collection->preserveKeys = (new static([]))->preserveKeys === true;
            }
        });
    }

    /**
     * Resolve the resource data.
     * Apply the pick method to the attributes if it exists.
     * 
     * @param mixed $request
     * @return array<string,mixed>
     */
    public function resolve($request = null)
    {
        $data = parent::resolve($request);

        if (isset($data['attributes'])) {
            $data['attributes'] = $this->pick($data['attributes']);
        }

        return $data;
    }

    /**
     * Get the route if it exists.
     * 
     * @param  string  $name
     * @param  array<string,mixed>  $params
     */
    protected function getRouteIfExists(string $name, array $params = []): ?string
    {
        return Route::has($name) ? route($name, $params) : null;
    }

    /**
     * Merge the value when the condition is not simple.
     * If simple or not is set in the request, the value will be merged or not.
     * 
     * @param array<mixed> $value
     * @return array<mixed>
     */
    protected function whenNotSimple(array $value)
    {
        if (request()->has('simple')) {
            return parent::mergeWhen(false, $value);
        } else {
            return parent::mergeWhen(true, $value);
        }
    }

    /**
     * Merge when the filter some filter is active.
     * 
     * @param string $filter
     * @param array<mixed> $merge
     * @param array<mixed>|null $fallback
     * @return array<mixed>
     */
    protected function whenFilter(string $filter, array $merge, ?array $fallback = null)
    {
        if ((request()->has('filter') && array_key_exists($filter, request()->input('filter'))) || request()->has($filter)) {
            return parent::mergeWhen(true, $merge);
        } else {
            if (!is_null($fallback)) {
                return parent::mergeWhen(true, $fallback);
            } else {
                return parent::mergeWhen(false, $merge);
            }
        }
    }

    /**
     * Get current active filter value or null.
     * or get all active filters.
     * @return mixed
     */
    public function currentFilter(string $key = null)
    {
        if (is_null($key)) {
            return $this->currentFilterData;
        }
        return $this->currentFilterData[$key] ?? null;
    }
}
