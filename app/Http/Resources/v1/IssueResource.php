<?php

namespace App\Http\Resources\v1;

use App\Http\Resources\v1\ApiResource;

class IssueResource extends ApiResource
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
            $this->mergeWhen($request->routeIs('issues.*'), [
                'author' => UserResource::make($this->user),
                'app' => AppResource::make($this->app),
                'thread' => ThreadResource::make($this->thread),
            ]),
            $this->mergeWhen(!$request->routeIs('issues.*'), [
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
                'thread' => $this->threads->count()
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
