<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): JsonResponse
    {
        $accounts = app(\App\Services\RosalanaAccountsClient::class);
        $resp = $accounts->login($request->email, $request->password);

        if (isset($resp['error'])) {
            return response()->json(['message' => 'Login failed'], 401);
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
            ]
        );

        // // 5. Lokální login (session)
        Auth::login($localUser);

        Cookie::queue(Cookie::make('RA-TOKEN', $resp['token'], 0, null, null, false, false, true));
        // // 6. Vrátím success (nebo cokoliv)
        return response()->json([
            'user' => $localUser,
        ]);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): JsonResponse
    {
        $accounts = app(\App\Services\RosalanaAccountsClient::class);
        Auth::guard('web')->logout();
        Cookie::queue(Cookie::forget('RA-TOKEN'));

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        try {
            $token = $request->bearerToken();
            $accounts->logout($token);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return response()->json(['message' => 'Logged out']);

    }
}
