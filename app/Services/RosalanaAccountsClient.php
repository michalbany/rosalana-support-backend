<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;


class RosalanaAccountsClient
{
    protected $baseUrl; // "https://accounts.rosalana.co"
    protected $appToken; // ten "X-App-Token" pro rosalana-accounts

    public function __construct()
    {
        $this->baseUrl = config('services.rosalana_accounts.url');
        $this->appToken = config('services.rosalana_accounts.token');
    }

    public function login($email, $password)
    {
        $response = Http::withHeaders([
            'X-App-Token' => $this->appToken
        ])->post("$this->baseUrl/api/v1/login", [
            'email' => $email,
            'password' => $password,
        ]);

        return $response;
    }

    public function logout($jwtToken)
    {
        $response = Http::withHeaders([
            'X-App-Token' => $this->appToken,
            'Authorization' => 'Bearer ' . $jwtToken,
        ])->post("$this->baseUrl/api/v1/logout");

        return $response;
    }

    public function register($name, $email, $password, $password_confirmation)
    {
        $response = Http::withHeaders([
            'X-App-Token' => $this->appToken
        ])->post("$this->baseUrl/api/v1/register", [
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password_confirmation
        ]);

        return $response;
    }

    public function me($jwtToken)
    {
        $response = Http::withHeaders([
            'X-App-Token' => $this->appToken,
            'Authorization' => 'Bearer ' . $jwtToken,
        ])->get("$this->baseUrl/api/v1/me");

        return $response;
    }

    public function refresh(string $jwtToken)
    {
        $response = Http::withHeaders([
            'X-App-Token' => $this->appToken,
            'Authorization' => 'Bearer ' . $jwtToken,
        ])->post("$this->baseUrl/api/v1/refresh");

        return $response;
    }
}
