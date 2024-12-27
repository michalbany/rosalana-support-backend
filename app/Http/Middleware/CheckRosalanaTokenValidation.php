<?php

namespace App\Http\Middleware;

use App\Models\Traits\ApiResponses;
use App\Models\User;
use App\Services\RosalanaAuth;
use Closure;
use Firebase\JWT\ExpiredException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Auth;

class CheckRosalanaTokenValidation
{
    use ApiResponses;

    /**
     * Handle an incoming request.
     * 
     * #note Refresh funguje tak na pul. Prvně to vrátí error $decode undefined a potom to funguje :) user nepozná že to nefunguje
     * Je to pravněpodobně kvůli tomu, že když JWT vyhodí při decodování exception proměnná není definovaná a já na ni potom navazuji
     * 
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = RosalanaAuth::CookieGet();

        if (!$token) {
            return $this->unauthorized(new \Exception('No token provided'));
        }

        try {
            $decode = JWT::decode($token, new Key(env('JWT_SECRET'), 'HS256'));
            $this->logginUser($decode);
        } catch (ExpiredException $e) {
            try {
                $response = RosalanaAuth::refresh($token);
                $token = $response['data']['token'];
                RosalanaAuth::CookieCreate($token);
                $decode = JWT::decode($token, new Key(env('JWT_SECRET'), 'HS256'));
                $this->logginUser($decode);
                logger('Token refreshed'); // #temp
            } catch (\App\Exceptions\RosalanaAuthException $e) {
                $this->logoutUser();
                return $this->unauthorized(new \Exception('Unauthorized'));
            }
        }

        return $next($request);
    }

    private function logoutUser()
    {
        logger('HIT LOGOUT'); // #temp
        Auth::logout();
        RosalanaAuth::CookieForget();

        session()->invalidate();
        session()->regenerateToken();
    }

    private function logginUser($decode)
    {
        logger('HIT LOGIN'); // #temp
        $user = User::where('rosalana_account_id', $decode->sub)->first();
        Auth::login($user);

        session()->regenerate();
    }
}
