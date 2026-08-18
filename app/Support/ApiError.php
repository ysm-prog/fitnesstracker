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
     * Error codes by HTTP status.
     *
     * Laravel converts several exception types into a plain HttpException
     * carrying only a status — a policy's `denyAsNotFound()` among them — so
     * status is the one thing always available to map from. Anything not listed
     * is a server error the client should not be asked to interpret.
     *
     * @var array<int, string>
     */
    private const CODES_BY_STATUS = [
        400 => ErrorCode::VALIDATION_FAILED,
        401 => ErrorCode::UNAUTHENTICATED,
        403 => ErrorCode::FORBIDDEN,
        404 => ErrorCode::NOT_FOUND,
        405 => ErrorCode::METHOD_NOT_ALLOWED,
        409 => ErrorCode::CONFLICT,
        422 => ErrorCode::VALIDATION_FAILED,
        429 => ErrorCode::TOO_MANY_REQUESTS,
    ];

    /** @var array<int, string> */
    private const MESSAGES_BY_STATUS = [
        400 => 'That request could not be understood.',
        401 => 'Sign in to continue.',
        403 => 'You do not have access to that.',
        404 => 'The requested resource does not exist.',
        405 => 'That method is not allowed for this endpoint.',
        409 => 'That conflicts with the current state of the resource.',
        422 => 'The submitted data is not valid.',
        429 => 'Too many attempts. Try again shortly.',
    ];

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

    /**
     * Build the envelope from an HTTP status alone.
     *
     * `$detail` is used only where it is safe to show: a policy's own denial
     * message, for instance. It is never an exception message, because those
     * carry file paths, SQL, and connection strings.
     */
    public static function fromStatus(int $status, ?string $detail = null): JsonResponse
    {
        $isKnown = array_key_exists($status, self::CODES_BY_STATUS);

        return self::response(
            self::CODES_BY_STATUS[$status] ?? ErrorCode::SERVER_ERROR,
            $isKnown && $detail !== null && $detail !== ''
                ? $detail
                : (self::MESSAGES_BY_STATUS[$status] ?? 'Something went wrong on our side. The correlation ID identifies this request in our logs.'),
            $isKnown ? $status : 500,
        );
    }
}
