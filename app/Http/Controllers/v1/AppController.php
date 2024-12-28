<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Filters\AppFilter;
use App\Http\Resources\v1\AppResource;
use App\Models\App;
use App\Services\RosalanaApps;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppController extends Controller
{
    public function index(AppFilter $filters): JsonResponse
    {
        $filter = request()->get('filter', []);

        $rosalanaApps = RosalanaApps::all();

        $rosalanaApps->each(function ($rosalanaApp) {
            App::sync(collect($rosalanaApp));
        });

        $apps = App::filter($filters)->get();
        $apps->each(fn($app) => $app->applyRosalanaData());

        if (array_key_exists('active', $filter)) {
            // filter only active apps
            $apps = $apps->filter(function ($app) {
                return $app->active == true;
            });
        }

        return $this->ok('Apps', AppResource::collection($apps));
    }


    public function show(int $id): JsonResponse
    {
        $app = App::findOrFail($id);

        try {
            $rosalanaApp = RosalanaApps::find($app->rosalana_account_id);
            App::sync($rosalanaApp);
        } catch (\Exception $e) {
            // nothing
        }

        $app->applyRosalanaData();

        return $this->ok('App', AppResource::make($app));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:50|unique:apps',
            'description' => 'required|string',
            'icon' => 'string',
        ]);


        [$rosalanaApp, $appToken] = RosalanaApps::register($request->name);

        try {
            $app = App::sync($rosalanaApp);
        } catch (\Exception $e) {
            // #note: Toto může nastat když je nesrovnalost v databázi RA a lokální. Např. když je email in use ale v RA o něm záznam není
            $appExists = App::where('name', $request->name)->first();
            if ($appExists) {
                $appExists->rosalana_account_id = $rosalanaApp['id'];
                $app = $appExists;
            }
        }

        $app->update([
            'description' => $request->description,
            'icon' => $request->icon,
        ]);

        $app->applyRosalanaData();

        return $this->ok('App created', AppResource::make($app)->addAttributes([
            'token' => $appToken,
        ]));
    }

    public function destroy(int $id)
    {
        $app = App::findOrFail($id);

        $appExists = false;
        try {
            RosalanaApps::find($app->rosalana_account_id);
            $appExists = true;
        } catch (\Exception $e) {
            $appExists = false;
        }

        if ($appExists) {
            RosalanaApps::unregister($app->rosalana_account_id);
        }

        $app->delete();

        return $this->ok('App has been deleted');
    }

    public function disable(int $id)
    {
        $app = App::findOrFail($id);

        RosalanaApps::unregister($app->rosalana_account_id);

        $app->update(['rosalana_account_id' => null]);

        $app->applyRosalanaData();

        return $this->ok('App disabled', AppResource::make($app));
    }

    public function enable(int $id)
    {
        $app = App::findOrFail($id);

        [$rosalanaApp, $token] = RosalanaApps::register($app->name);

        $app->update(['rosalana_account_id' => $rosalanaApp['id']]);

        App::sync($rosalanaApp);

        $app->applyRosalanaData();

        return $this->ok('App enabled', AppResource::make($app)->addAttributes([
            'token' => $token,
        ]));
    }

    public function update(Request $request, int $id)
    {
        $request->validate([
            'name' => 'string|max:50',
            'description' => 'required|string',
            'icon' => 'string|nullable',
        ]);

        $app = App::findOrFail($id);

        if ($request->has('name') && $app->name !== $request->name) {
            $appExists = false;
            try {
                RosalanaApps::find($app->rosalana_account_id);
                $appExists = true;
            } catch (\Exception $e) {
                $appExists = false;
            }

            if ($appExists) {
                RosalanaApps::update($app->rosalana_account_id, $request->name);
            }
        }

        $app->update($request->all());

        return $this->ok('App updated', AppResource::make($app));
    }

    public function refresh(int $id)
    {
        $app = App::findOrFail($id);

        [$rosalanaApp, $token] = RosalanaApps::refresh($app->rosalana_account_id);

        App::sync($rosalanaApp);
        $app->applyRosalanaData();

        return $this->ok('Token refreshed', AppResource::make($app)->addAttributes([
            'token' => $token,
        ]));
    }
}
