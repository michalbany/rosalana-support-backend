<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
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
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $accounts = app(\App\Services\RosalanaAccountsClient::class);
        $resp = $accounts->register($request->name, $request->email, $request->password, $request->password_confirmation);

        if (isset($resp['error'])) {
            return response()->json(['message' => $resp['error']], 401);
        }

        // // 3. RA vrátil (user, token) => user['id'] je rosalana_account_id
        $raUserId = $resp['user']['id'] ?? null;
        if (!$raUserId) {
            return response()->json(['message' => 'No user ID from RA'], 500);
        }

        // // 4. Najdu / vytvořím local user
        $localUser = User::updateOrCreate(
            ['rosalana_account_id' => $raUserId],
            [
                'name' => $resp['user']['name'] ?? $resp['user']['email'],
                'email' => $resp['user']['email'],
                'password' => Hash::make($request->password),
            ]
        );

        // // 5. Lokální login (session)
        Auth::login($localUser);

        Cookie::queue(Cookie::make('RA-TOKEN', $resp['token'], 0, null, null, false, false, true));


        event(new Registered($localUser));

        return response()->json([
            'user' => $localUser,
        ]);
    }
}
