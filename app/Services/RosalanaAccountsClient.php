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
        // Volá rosalana-accounts /login
        $response = Http::withHeaders([
            'X-App-Token' => $this->appToken
        ])->post("$this->baseUrl/api/login", [
            'email' => $email,
            'password' => $password,
        ]);

        return $response->json();
    }

    public function logout($jwtToken)
    {
        $response = Http::withHeaders([
            'X-App-Token' => $this->appToken,
            'Authorization' => 'Bearer ' . $jwtToken,
        ])->post("$this->baseUrl/api/logout");

        return $response->json();
    }

    public function register($name, $email, $password, $password_confirmation)
    {
        $response = Http::withHeaders([
            'X-App-Token' => $this->appToken
        ])->post("$this->baseUrl/api/register", [
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password_confirmation
        ]);

        return $response->json();
    }

    public function me($jwtToken)
    {
        $response = Http::withHeaders([
            'X-App-Token' => $this->appToken,
            'Authorization' => 'Bearer ' . $jwtToken,
        ])->get("$this->baseUrl/api/me");

        return $response->json();
    }

    public function refresh(string $jwtToken)
    {
        $response = Http::withHeaders([
            'X-App-Token' => $this->appToken,
            'Authorization' => 'Bearer ' . $jwtToken,
        ])->post("$this->baseUrl/api/refresh");

        return $response->json();
    }
}