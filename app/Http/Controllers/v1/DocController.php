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
        return DocResource::collection(Doc::filter($filters)->get());
    }
}
