<?php

namespace App\Http\Controllers;

use App\Http\Processors\API\V1\ApiRequest;
use App\Models\Traits\ApiResponses;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;


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
        } catch (\App\Exceptions\ApiFilterHelpException $e) {
            return response()->json($e->getData());
        } catch (\Exception $e) {
            return $this->serverError($e);
        }
    }


    /**
     * @param Request $request
     * @param array<mixed> $rules
     * @return array<mixed>
     */
    public function validateRequest(Request $request, array $rules)
    {
        $validationRules = [
            'data' => 'required|array',
            'data.type' => 'string',
            'data.attributes.*' => 'prohibited',
            'data.relationships.*' => 'prohibited',
        ];

        // ID
        $validationRules['data.id'] = $rules['id'] ?? 'sometimes|integer|nullable';

        // Attributes
        if (isset($rules['attributes'])) {
            foreach ($rules['attributes'] as $attribute => $rule) {
                $validationRules["data.attributes.$attribute"] = $rule;
            }
        }

        // Relationships
        if (isset($rules['relationships'])) {
            foreach ($rules['relationships'] as $relationship => $rule) {
                $validationRules["data.relationships.$relationship"] = $rule;
            }
        }

        $validator = Validator::make($request->all(), $validationRules, [
            'prohibited' => 'The :attribute field is not allowed',
        ]);

        $data = $validator->validate();

        return $data;
    }

    /**
     * Authorize request with multiple permissions for single attributes. If no specific permission is set to the attribute, global permission is used.
     * Specific permissions are for users with lower level of access rights to let them update only specific attributes.
     * @param array<mixed> $data Request data
     * @param array<mixed> $specific Specific permissions for single attributes
     * @param string $global Global permissions if no specific permissions are set for the attribute
     * @param mixed $model Model instance
     * @return void
     */
    protected function authorizeRequest(array $data, array $specific, string $global, $model): void
    {
        $data = $data['data'];

        // If user has global permission, skip the rest (zrychlení procesu)
        if (Gate::allows($global, $model)) {
            return;
        }

        $categories = ['attributes', 'relationships'];

        // Check specific permissions
        foreach ($categories as $category) {
            foreach ($data[$category] ?? [] as $key => $value) {
                $permission = $specific[$category . '.' . $key] ?? null;

                if (!$permission || !Gate::allows($permission, $model)) {
                    throw new \Illuminate\Auth\Access\AuthorizationException("You are not allowed to update the $category: $key");
                }
            }
        }

        // If everything is OK,
        return;
    }

    /**
     * #note Funguje pouze pro UPDATE
     * @param array<mixed> $requestData
     */
    public function processRequest(array $requestData, string $modelClass): mixed
    {
        $process = new ApiRequest($modelClass, $requestData, request()->method());
        $model = null;

        switch ($process->type()) {
            case 'CREATE':
                throw new \BadMethodCallException('Creating not implemented yet');
            case 'UPDATE':
                $model = $process->process('update');
                $process->process('relationships');
                break;
            case 'DELETE':
                throw new \BadMethodCallException('Deleting not implemented yet');
        }

        return $model;
    }

}
