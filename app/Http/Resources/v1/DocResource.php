<?php

namespace App\Http\Resources\V1;

use App\Http\Resources\v1\ApiResource;
use App\Http\Resources\v1\AppResource;
use App\Http\Resources\v1\UserResource;

class DocResource extends ApiResource
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
            $this->mergeWhen($request->routeIs('docs.*'), [
                'author' => UserResource::make($this->user),
                'app' => AppResource::make($this->app),
            ]),
            $this->mergeWhen(!$request->routeIs('docs.*'), [
                'app' => ApiResource::make($this->app)->setAttributes(function ($app) {
                    return [
                        'name' => $app->name,
                    ];
                }),
                'author' => ApiResource::make($this->user)->setAttributes(function ($user) {
                    return [
                        'name' => $user->name,
                    ];
                }),
            ]),
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
