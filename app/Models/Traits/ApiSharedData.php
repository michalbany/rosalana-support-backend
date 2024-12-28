<?php

namespace App\Models\Traits;

trait ApiSharedData
{
    /** @var array<mixed>|callable|null */
    public $attributesData;
    /** @var array<mixed>|callable|null */
    public $relationshipsData;
    /** @var array<mixed>|callable|null */
    public $linksData;
    /** @var string|null */
    public $modelType;

    /**
     * @param array<string,boolean> $data
     */
    public function permissions(array $data): self
    {
        $this->additional(array_merge(['permissions' => $data], $this->additional));

        return $this;
    }

    /**
     * @param array<string,mixed> $data
     */
    public function meta(array $data): self
    {
        $this->additional(array_merge(['meta' => $data], $this->additional));

        return $this;
    }

    /**
     * Get additional data that should be returned with the resource array.
     * 
     * @return array<string,mixed>
     */
    public function with($request): array
    {
        return [
            'meta' => [
                'version' => '1.0.0',
            ]
        ];
    }

    /**
     * Pick the attributes based on the request.
     * 
     * @param array<mixed> $attributes
     * @return array<mixed>
     */
    public function pick($attributes)
    {
        $rows = request()->get('pick');
        if ($rows) {
            $rows = explode(',', $rows);
            $attributes = collect($attributes)->only($rows)->toArray();
        }

        return $attributes;
    }
}
