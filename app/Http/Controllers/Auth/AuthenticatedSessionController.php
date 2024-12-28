<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\v1\UserResource;
use App\Models\User;
use App\Services\RosalanaAuth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
                    return $this->error('Failed to login. Error while creating local user account. Please contact support.', 500);
                }
            }

        Auth::login($localUser);
        RosalanaAuth::CookieCreate($token);

        return $this->ok('Logged in', UserResource::make($localUser));
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
