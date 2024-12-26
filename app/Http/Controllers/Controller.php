<?php

namespace App\Http\Controllers;

use App\Models\Traits\ApiResponses;

abstract class Controller extends \Illuminate\Routing\Controller
{
    use ApiResponses;

    /**
     * Catching all Exceptions
     * 
     * @param string $method
     * @param array<string> $parameters
     */
    public function callAction($method, $parameters)
    {
        try {
            return parent::callAction($method, $parameters);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound($e);
        } catch (\InvalidArgumentException $e) {
            return $this->badRequest($e);
        } catch (\BadMethodCallException $e) {
            return $this->serverError($e);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->unauthorized($e);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationFailed($e);
        } catch (\App\Exceptions\RosalanaAuthException $e) {
            return $this->rosalanaAuthFailed($e);
        } catch (\Exception $e) {
            return $this->serverError($e);
        }
    }
}
