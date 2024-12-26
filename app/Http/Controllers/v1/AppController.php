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

        $rosalanaApp = RosalanaApps::find($app->rosalana_account_id);

        App::sync($rosalanaApp);
        $app->applyRosalanaData();

        return $this->ok('App', $app->toArray());
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'description' => 'required|string',
            'icon' => 'string|nullable',
        ]);


        $rosalanaApp = RosalanaApps::register($request->name);

        try {
            $app = App::sync($rosalanaApp);
        } catch (\Exception $e) {
            // #note: Toto může nastat když je nesrovnalost v databázi RA a lokální. Např. když je email in use ale v RA o něm záznam není
            $appExists = App::where('name', $request->name)->first();
            if ($appExists) {
                $appExists->update([
                    'rosalana_account_id' => $rosalanaApp['id'],
                    'description' => $request->description,
                    'icon' => $request->icon,
                ]);
                $app = $appExists;
            }
        }

        $app->applyRosalanaData();

        return $this->ok('App created', $app->toArray());
    }

    public function destroy(int $id)
    {
        $app = App::findOrFail($id);

        $response = RosalanaApps::unregister($app->rosalana_account_id);

        $app->delete();

        return $this->ok($response['message']);
    }
}
