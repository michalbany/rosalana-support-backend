<?php

namespace App\Http\Resources\v1;

use App\Models\Traits\ApiSharedData;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @method $this setModelType(string $type)
 * @method $this setAttributes(callable $callback)
 * @method $this setLinks(callable $callback)
 * @method $this setRelationships(callable $callback)
 */
class ApiCollection extends AnonymousResourceCollection
{
    use ApiSharedData;
    
    /**
     * Call method on each resource if it is not defined on the collection
     * 
     * @param string $method
     * @param array<mixed> $arguments
     * @return $this
     */
    public function __call($method, $arguments)
    {
        // @phpstan-ignore-next-line
        if ($this->collection) {
            $this->collection->each(function ($resource) use ($method, $arguments) {
                if (is_callable([$resource, $method])) {
                    $resource->{$method}(...$arguments);
                }
            });
        }
        return $this;
    }

    /**
     * Transform the resource collection into an array.
     * Apply pick to each item in 'data'
     * @note: Musí to být takto řešené pro collection. 
     *  Z nějakého důvodu se nevolá resolve pro každou collection
     * 
     * @param \Illuminate\Http\Request $request
     */
    public function toResponse($request)
    {
        $response = parent::toResponse($request);

        $data = $response->getData(true);

        // Aplikujeme pick na každou položku v 'data'
        if (isset($data['data'])) {
            $data['data'] = collect($data['data'])->map(function ($item) {
                if (isset($item['attributes'])) {
                    $item['attributes'] = $this->pick($item['attributes']);
                }
                return $item;
            })->all();
        }

        $response->setData($data);

        return $response;
    }
}
