<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\JsonResponse;

/**
 * The single shape every API failure takes.
 *
 * Routing all failures through one builder is what makes "never expose SQL,
 * stack traces, secrets, or connection strings" a property of the system
 * rather than a habit each handler has to remember.
 */
final class ApiError
{
    /**
     * @param  array<string, mixed>  $errors
     */
    public static function response(string $code, string $message, int $status, array $errors = []): JsonResponse
    {
        $payload = [
            'error_code' => $code,
            'message' => $message,
            'correlation_id' => CorrelationId::current(),
        ];

        if ($errors !== []) {
            $payload['errors'] = $errors;
        }

        return new JsonResponse($payload, $status);
    }
}
