<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\RosalanaAuth;
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
        $response = RosalanaAuth::login($request->email, $request->password);
        
        $user = $response['data']['user'];
        $token = $response['data']['token'];

        $localUser = User::updateOrCreate(
            ['rosalana_account_id' => $user['id']],
            [
                'name' => $user['name'] ?? $user['email'],
                'email' => $user['email'],
            ]
        );

        Auth::login($localUser);
        RosalanaAuth::CookieCreate($token);

        return $this->ok('Logged in', $localUser->toArray()); #change to $userResouce
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();        
        RosalanaAuth::logout();
        RosalanaAuth::CookieForget();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->ok('Logged out');
    }
}
