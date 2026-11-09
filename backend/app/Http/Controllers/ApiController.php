<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Routing\Controller as BaseController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Base API controller.
 *
 * All API controllers extend this class and use the response helpers
 * so every response follows the same envelope:
 *
 *   Success:  { "data": ..., "meta": ..., "links": ... }
 *   Error:    { "error": { "code": "...", "message": "...", "details": {} } }
 */
abstract class ApiController extends BaseController
{
    // ─── Success ────────────────────────────────────────────────────────────

    protected function ok(mixed $data = null, array $meta = []): JsonResponse
    {
        return $this->success($data, $meta, Response::HTTP_OK);
    }

    protected function created(mixed $data = null, array $meta = []): JsonResponse
    {
        return $this->success($data, $meta, Response::HTTP_CREATED);
    }

    protected function noContent(): JsonResponse
    {
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    protected function success(mixed $data, array $meta = [], int $status = Response::HTTP_OK): JsonResponse
    {
        $payload = [];

        if ($data instanceof ResourceCollection) {
            // Let the resource collection handle its own response (includes pagination)
            return $data->response()->setStatusCode($status);
        }

        if ($data instanceof JsonResource) {
            $payload['data'] = $data->resolve();
        } elseif ($data !== null) {
            $payload['data'] = $data;
        }

        if (! empty($meta)) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    // ─── Errors ─────────────────────────────────────────────────────────────

    protected function error(
        string $code,
        string $message,
        int $status = Response::HTTP_BAD_REQUEST,
        array $details = [],
    ): JsonResponse {
        $payload = [
            'error' => [
                'code'    => $code,
                'message' => $message,
            ],
        ];

        if (! empty($details)) {
            $payload['error']['details'] = $details;
        }

        return response()->json($payload, $status);
    }

    protected function notFound(string $message = 'Resource not found.'): JsonResponse
    {
        return $this->error('not_found', $message, Response::HTTP_NOT_FOUND);
    }

    protected function forbidden(string $message = 'Forbidden.', string $code = 'forbidden'): JsonResponse
    {
        return $this->error($code, $message, Response::HTTP_FORBIDDEN);
    }

    protected function unauthorized(string $message = 'Unauthenticated.'): JsonResponse
    {
        return $this->error('unauthenticated', $message, Response::HTTP_UNAUTHORIZED);
    }

    protected function validationError(array $errors, string $message = 'The given data was invalid.'): JsonResponse
    {
        return $this->error('validation_failed', $message, Response::HTTP_UNPROCESSABLE_ENTITY, $errors);
    }
}
