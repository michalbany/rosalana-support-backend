<?php

namespace App\Services;

use App\Exceptions\RosalanaAuthException;

class RosalanaApps
{
    public static function all()
    {
        $ra = app(\App\Services\RosalanaAccountsClient::class);
        $response = $ra->getAllApps();

        if ($response->status() !== 200) {
            throw new RosalanaAuthException($response->json(), $response->status());
        }

        return collect($response->json()['data']['apps']);
    }

    public static function find($id)
    {
        $ra = app(\App\Services\RosalanaAccountsClient::class);
        $response = $ra->getApp($id);

        if ($response->status() !== 200) {
            throw new RosalanaAuthException($response->json(), $response->status());
        }

        return collect($response->json()['data']['app']);
    }

    public static function register($name)
    {
        $ra = app(\App\Services\RosalanaAccountsClient::class);
        $response = $ra->registerApp($name);

        if ($response->status() !== 200) {
            throw new RosalanaAuthException($response->json(), $response->status());
        }

        return collect($response->json()['data']['app']);
    }

    public static function unregister($id)
    {
        $ra = app(\App\Services\RosalanaAccountsClient::class);
        $response = $ra->unregisterApp($id);

        if ($response->status() !== 200) {
            throw new RosalanaAuthException($response->json(), $response->status());
        }

        return $response->json();
    }

    public static function update($id, $name)
    {
        $ra = app(\App\Services\RosalanaAccountsClient::class);
        $response = $ra->updateApp($id, $name);

        if ($response->status() !== 200) {
            throw new RosalanaAuthException($response->json(), $response->status());
        }

        return $response->json();
    }

    public static function refreshToken($id)
    {
        //
    }
}