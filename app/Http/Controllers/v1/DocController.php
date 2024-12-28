<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Filters\DocFilter;
use App\Http\Resources\V1\DocResource;
use App\Models\Doc;
use Illuminate\Http\Request;

class DocController extends Controller
{
    public function index(DocFilter $filters)
    {
        $docs = Doc::filter($filters)->get();

        return $this->ok('Docs retrieved successfully', DocResource::collection($docs));
    }

    public function show(int $id)
    {
        return $this->ok('Doc retrieved successfully', DocResource::make(Doc::findOrFail($id)));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:50',
            'content' => 'required|string',
            'app_id' => 'required|integer|exists:apps,id',
            'user_id' => 'required|integer|exists:users,id',
        ]);

        if (!$request->user()->is_admin) {
            return $this->unauthorized(new \Exception('You are not authorized to perform this action'));
        }

        $doc = Doc::create([
            'title' => $request->title,
            'content' => $request->content,
            'app_id' => $request->app_id,
            'user_id' => $request->user_id,
            'status' => 'draft',
        ]);

        return $this->ok('Doc created successfully', DocResource::make($doc));
    }

    public function update(Request $request, int $id)
    {
        $request->validate([
            'title' => 'string|max:50',
            'content' => 'string',
            'app_id' => 'integer|exists:apps,id',
            'user_id' => 'integer|exists:users,id',
            'status' => 'string|in:draft,published',
        ]);

        $doc = Doc::findOrFail($id);

        if (!$request->user()->is_admin && $doc->user_id !== $request->user()->id) {
            return $this->unauthorized(new \Exception('You are not authorized to perform this action'));
        }

        if ($request->status === 'published') {
            $doc->publish();
        } elseif ($request->status === 'draft') {
            $doc->draft();
        }

        $doc->update($request->all());

        return $this->ok('Doc updated successfully', DocResource::make($doc));
    }

    public function destroy(int $id)
    {
        $doc = Doc::findOrFail($id);

        $doc->delete();

        return $this->ok('Doc deleted successfully');
    }
}
