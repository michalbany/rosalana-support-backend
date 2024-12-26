<?php

namespace App\Models\Traits;

use App\Exceptions\RosalanaAuthException;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;

trait ApiResponses
{

    /**
     * @param array<string,mixed> $data
     */
    protected function ok(string $message, array $data = []): JsonResponse
    {
        return $this->success($message, $data, 200);
    }

    /**
     * @param array<string,mixed> $data
     */
    protected function success(string $message, array $data = [], int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    /**
     * @param string|array<string,mixed> $errors
     */
    protected function error(string|array $errors = [], int $statusCode = 500): JsonResponse
    {
        if (is_string($errors)) {
            return response()->json([
                'error' => $errors,
            ], $statusCode);
        }

        return response()->json([
            'errors' => $errors,
        ], $statusCode);
    }

    protected function rosalanaAuthFailed(RosalanaAuthException $e): JsonResponse
    {
        return $this->error($e->getErrors()['error'] ?? $e->getErrors()['errors'] ?? $e->getErrors()['message'], $e->getStatus());
    }

    protected function notFound(Exception|ModelNotFoundException $e): JsonResponse
    {
        if ($e instanceof ModelNotFoundException && $e->getModel()) {
            $modelName = class_basename($e->getModel());
            $ids = join(',', $e->getIds());
            return $this->error("{$modelName} not found for key {$ids}", 404);
        } else {
            return $this->error($e->getMessage(), 404);
        }
    }

    protected function unauthorized(Exception $e): JsonResponse
    {
        return $this->error($e->getMessage(), 401);
    }

    protected function serverError(Exception $e): JsonResponse
    {
        return $this->error($e->getMessage(), 500);
    }

    protected function badRequest(Exception $e): JsonResponse
    {
        return $this->error($e->getMessage(), 400);
    }

    protected function forbidden(Exception $e): JsonResponse
    {
        return $this->error($e->getMessage(), 403);
    }

    protected function validationFailed(Exception|\Illuminate\Validation\ValidationException $e): JsonResponse
    {
        if ($e instanceof \Illuminate\Validation\ValidationException) {
            return $this->error($e->errors(), 422);
        } else {
            return $this->error($e->getMessage(), 422);
        }
    }
}
