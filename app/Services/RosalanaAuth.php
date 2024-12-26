<?php

namespace App\Services;

use App\Models\Traits\ApiResponses;
use App\Models\User;
use Illuminate\Support\Facades\Cookie;

/**
 * Implementuje kompletní logiku pro autentifikaci
 * Vzdálený login i lokální login
 */
class RosalanaAuth
{
    use ApiResponses;

    public static function login($email, $password)
    {
        $accounts = app(\App\Services\RosalanaAccountsClient::class);
        $response = $accounts->login($email, $password);

        if ($response->status() !== 200) {
            throw new \App\Exceptions\RosalanaAuthException($response->json(), $response->status());
        }

        return $response->json();
    }

    public static function logout()
    {
        $accounts = app(\App\Services\RosalanaAccountsClient::class);
        $token = Cookie::get('RA-TOKEN');
        $response = $accounts->logout($token);

        if ($response->status() !== 200) {
            throw new \App\Exceptions\RosalanaAuthException($response->json(), $response->status());
        }

        return $response->json();
    }

    public static function register($name, $email, $password, $password_confirmation)
    {
        $accounts = app(\App\Services\RosalanaAccountsClient::class);
        $response = $accounts->register($name, $email, $password, $password_confirmation);

        if ($response->status() !== 200) {
            throw new \App\Exceptions\RosalanaAuthException($response->json(), $response->status());
        }

        return $response->json();

    }
}
