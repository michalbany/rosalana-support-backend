<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\UserResource;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function me(Request $request)
    {
        if ($request->user()) {
            return $this->ok('Logged user', UserResource::make($request->user()));
        } else {
            return $this->error('Unauthorized', 401);
        }
    }
}
