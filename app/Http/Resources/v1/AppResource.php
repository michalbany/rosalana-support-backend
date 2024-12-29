<?php

namespace App\Http\Resources\v1;

use App\Http\Processors\API\V1\ApiRequest;
use App\Http\Resources\v1\ApiResource;
use App\Http\Resources\V1\DocResource;

class AppResource extends ApiResource
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
        $docs = $this->docs;
        if (!$request->user()?->is_admin) {
            $docs = $this->docs->where('status', 'published');
        }

        $issues = $this->issues;
        if (!$request->user()?->is_admin) {
            $issues = $this->issues->filter(function ($issue) use ($request) {
                return $issue->visibility === 'public' || $issue->user_id === $request->user()?->id;
            });
        }


        return [
            $this->mergeWhen($request->routeIs('apps.*'), [
                'docs' => DocResource::collection($docs),
                'issues' => IssueResource::collection($issues),
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
