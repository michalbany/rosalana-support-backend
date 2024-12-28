<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\UserResource;
use App\Models\User;
use App\Services\RosalanaAuth;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class RegisteredUserController extends Controller
{
    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        // #removed Validation is on Rosalana Accounts
        $response = RosalanaAuth::register($request->name, $request->email, $request->password, $request->password_confirmation);

        $user = $response['data']['user'];
        $token = $response['data']['token'];

        try {
            $localUser = User::updateOrCreate(
                ['rosalana_account_id' => $user['id']],
                [
                    'name' => $user['name'] ?? $user['email'],
                    'email' => $user['email'],
                ]
            );
        } catch (\Exception $e) {
            // #note: Toto může nastat když je nesrovnalost v databázi RA a lokální. Např. když je email in use ale v RA o něm záznam není
            $susUser = User::where('email', $user['email'])->first();
            if ($susUser) {
                $susUser->update([
                    'rosalana_account_id' => $user['id'],
                    'name' => $user['name'] ?? $user['email'],
                    'email' => $user['email'],
                ]);

                $localUser = $susUser;
            } else {
                return $this->error('Failed to Register. Error while creating local user account. Please contact support.', 500);
            }
        }

        Auth::login($localUser);
        RosalanaAuth::CookieCreate($token);

        event(new Registered($localUser));

        return $this->ok('Registered', UserResource::make($localUser));
    }
}
