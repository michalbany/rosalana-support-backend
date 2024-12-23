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
        ])->post("$this->baseUrl/logout");

        return $response->json();
    }

    public function me($jwtToken)
    {
        $response = Http::withHeaders([
            'X-App-Token' => $this->appToken,
            'Authorization' => 'Bearer ' . $jwtToken,
        ])->get("$this->baseUrl/me");

        return $response->json(); 
    }

    public function refresh(string $jwtToken)
    {
        $response = Http::withHeaders([
            'X-App-Token' => $this->appToken,
            'Authorization' => 'Bearer ' . $jwtToken,
        ])->get("$this->baseUrl/refresh");

        return $response->json();
    }
}
