<?php

namespace App\Http\Resources\v1;

use App\Http\Resources\v1\ApiResource;

class ThreadResource extends ApiResource
{
    /**
     * Get the resource attributes.
     *
     * @param \Illuminate\Http\Request $request
     * @return array<mixed>
     */
    public function attributes($request)
    {
        return parent::attributes($request);
    }

    /**
     * Get the resource relationships.
     *
     * @param \Illuminate\Http\Request $request
     * @return array<mixed>
     */
    public function relationships($request)
    {
        return [
            // Define relationships...
        ];
    }

    /**
     * Get the resource links.
     *
     * @param \Illuminate\Http\Request $request
     * @return array<mixed>
     */
    public function links($request)
    {
        return [
            // Define links...
        ];
    }

    /**
     * Explicitly set the model type.
     * 
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    public function modelType($request)
    {
        return parent::modelType($request);
    }
}
