<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
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

        $localUser = User::updateOrCreate(
            ['rosalana_account_id' => $user['id']],
            [
                'name' => $user['name'] ?? $user['email'],
                'email' => $user['email'],
                'password' => Hash::make($request->password),
            ]
        );

        Auth::login($localUser);
        RosalanaAuth::CookieCreate($token);

        event(new Registered($localUser));

        return $this->ok('Registered', $localUser->toArray()); #change to $userResouce
    }
}
