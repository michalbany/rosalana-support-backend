<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;


class RosalanaAccountsClient
{
    protected $baseUrl; // "https://accounts.rosalana.co"
    protected $appToken; // ten "X-App-Token" pro rosalana-accounts
    protected $appOrigin; // "http://localhost:8000"

    public function __construct()
    {
        $this->baseUrl = config('services.rosalana_accounts.url');
        $this->appToken = config('services.rosalana_accounts.token');
        $this->appOrigin = config('services.rosalana_accounts.origin');
    }

    public function login($email, $password)
    {
        $response = Http::withHeaders([
            'X-App-Token' => $this->appToken,
            'X-App-Origin' => $this->appOrigin
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
            'X-App-Origin' => $this->appOrigin,
            'Authorization' => 'Bearer ' . $jwtToken,
        ])->post("$this->baseUrl/api/v1/logout");

        return $response;
    }

    public function register($name, $email, $password, $password_confirmation)
    {
        $response = Http::withHeaders([
            'X-App-Token' => $this->appToken,
            'X-App-Origin' => $this->appOrigin
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
            'X-App-Origin' => $this->appOrigin,
            'Authorization' => 'Bearer ' . $jwtToken,
        ])->get("$this->baseUrl/api/v1/me");

        return $response;
    }

    public function refresh(string $jwtToken)
    {
        $response = Http::withHeaders([
            'X-App-Token' => $this->appToken,
            'X-App-Origin' => $this->appOrigin,
            'Authorization' => 'Bearer ' . $jwtToken,
        ])->post("$this->baseUrl/api/v1/refresh");

        return $response;
    }

    public function getAllApps()
    {
        $response = Http::withHeaders([
            'X-App-Token' => $this->appToken,
            'X-App-Origin' => $this->appOrigin
        ])->get("$this->baseUrl/api/v1/apps");

        return $response;
    }

    public function getApp($id)
    {
        $response = Http::withHeaders([
            'X-App-Token' => $this->appToken,
            'X-App-Origin' => $this->appOrigin
        ])->get("$this->baseUrl/api/v1/apps/$id");

        return $response;
    }

    public function registerApp($name)
    {
        $response = Http::withHeaders([
            'X-App-Token' => $this->appToken,
            'X-App-Origin' => $this->appOrigin
        ])->post("$this->baseUrl/api/v1/apps", [
            'name' => $name,
        ]);

        return $response;
    }

    public function unregisterApp($id)
    {
        $response = Http::withHeaders([
            'X-App-Token' => $this->appToken,
            'X-App-Origin' => $this->appOrigin
        ])->delete("$this->baseUrl/api/v1/apps/$id");

        return $response;
    }

    public function updateApp($id, $name)
    {
        $response = Http::withHeaders([
            'X-App-Token' => $this->appToken,
            'X-App-Origin' => $this->appOrigin
        ])->patch("$this->baseUrl/api/v1/apps/$id", [
            'name' => $name,
        ]);

        return $response;
    }
}
