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
        $doc = Doc::create($request->all());

        return $this->created('Doc created successfully', DocResource::make($doc));
    }

    public function update(Request $request, int $id)
    {
        $doc = Doc::findOrFail($id);

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
