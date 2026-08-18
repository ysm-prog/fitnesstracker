<?php

declare(strict_types=1);

namespace App\Support;

/**
 * The stable error vocabulary of the API.
 *
 * Clients branch on these strings, so a value here is a public contract:
 * add new ones freely, never repurpose an existing one.
 */
final class ErrorCode
{
    public const VALIDATION_FAILED = 'validation_failed';

    public const UNAUTHENTICATED = 'unauthenticated';

    public const INVALID_CREDENTIALS = 'invalid_credentials';

    public const EMAIL_NOT_VERIFIED = 'email_not_verified';

    public const FORBIDDEN = 'forbidden';

    public const NOT_FOUND = 'not_found';

    public const METHOD_NOT_ALLOWED = 'method_not_allowed';

    public const TOO_MANY_REQUESTS = 'too_many_requests';

    public const CONFLICT = 'conflict';

    public const SERVER_ERROR = 'server_error';
}
