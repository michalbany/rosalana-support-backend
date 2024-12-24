<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Firebase\JWT\ExpiredException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

class CheckRosalanaTokenValidation
{
    /**
     * Handle an incoming request.
     * 
     * Kontrola tokenu z Rosalana Accounts
     * - pokud token expiroval, zkusíme získat nový pomocí refresh tokenu
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {        
        $token = Cookie::get('RA-TOKEN');
        
        if (!$token) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $decode = JWT::decode($token, new Key(env('JWT_SECRET'), 'HS256'));
            $this->logginUser($decode);

        } catch (ExpiredException $e) {

            // Token expiroval, takže se pokusíme získat refresh token
            $accounts = app(\App\Services\RosalanaAccountsClient::class);
            $resp = $accounts->refresh($token);

            if (isset($resp['error'])) {
                // Refresh token nebyl platný, takže odhlašujeme uživatele
                $this->logoutUser();

                return response()->json(['message' => 'Unauthorized'], 401);
            } else {
                // Refresh token byl platný, takže uložíme nový token
                Cookie::queue(Cookie::make('RA-TOKEN', $resp['token'], 0, null, null, false, false, true));

                try {
                    $decode = JWT::decode($resp['token'], new Key(env('JWT_SECRET'), 'HS256'));
                    $this->logginUser($decode);
                    logger('Token refreshed'); // #temp
                } catch (\Exception $e) {
                    // Nastala chyba při takže odhlašujeme uživatele
                    $this->logoutUser();
                    return response()->json(['message' => 'Unauthorized'], 401);
                }
            }
        } catch (\Exception $e) {
            // Nastala chyba při takže odhlašujeme uživatele
            $this->logoutUser();
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }

    private function logoutUser()
    {
        Auth::logout();
        Cookie::queue(Cookie::forget('RA-TOKEN'));

        session()->invalidate();
        session()->regenerateToken();
    }

    private function logginUser($decode)
    {
        $user = User::where('rosalana_account_id', $decode->sub)->first();
        Auth::login($user);
    }
}
