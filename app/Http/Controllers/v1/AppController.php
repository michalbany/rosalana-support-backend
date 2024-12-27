<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Services\RosalanaApps;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppController extends Controller
{
    public function index(): JsonResponse
    {
        $rosalanaApps = RosalanaApps::all();

        $rosalanaApps->each(function ($rosalanaApp) {
            App::sync(collect($rosalanaApp));
        });

        $apps = App::all();
        $apps->each(fn($app) => $app->applyRosalanaData());

        return $this->ok('Apps', $apps->toArray());
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

        return $this->ok('App', $app->toArray());
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:50|unique:apps',
            'description' => 'required|string',
            'icon' => 'string|nullable',
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

        return $this->ok('App created', [
            'app' => $app->toArray(),
            'token' => $appToken,
        ]);
    }

    public function destroy(int $id)
    {
        $app = App::findOrFail($id);

        $response = RosalanaApps::unregister($app->rosalana_account_id);

        $app->delete();

        return $this->ok($response['message']);
    }

    public function disable(int $id)
    {
        $app = App::findOrFail($id);

        $rosalanaApp = RosalanaApps::unregister($app->rosalana_account_id);

        $app->update(['rosalana_account_id' => null]);

        $app->applyRosalanaData();

        return $this->ok('App disabled', $app->toArray());
    }

    public function enable(int $id)
    {
        $app = App::findOrFail($id);

        [$rosalanaApp, $token] = RosalanaApps::register($app->name);

        App::sync($rosalanaApp);

        $app->applyRosalanaData();

        return $this->ok('App enabled', [
            'app' => $app->toArray(),
            'token' => $token,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $request->validate([
            'name' => 'string|max:50|unique:apps',
            'description' => 'required|string',
            'icon' => 'string|nullable',
        ]);

        $app = App::findOrFail($id);

        if ($request->has('name')) {
            RosalanaApps::update($app->rosalana_account_id, $request->name);
        }

        $app->update($request->all());

        return $this->ok('App updated', $app->toArray());
    }

    public function refresh(int $id)
    {
        $app = App::findOrFail($id);

        [$app, $token] = RosalanaApps::refresh($app->rosalana_account_id);

        return $this->ok('Token refreshed', [
            'app' => $app->toArray(),
            'token' => $token,
        ]);
    }
}
