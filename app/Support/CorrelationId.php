<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Ties every log line and error response for one request together.
 *
 * A caller may supply its own identifier so a mobile client can correlate its
 * own telemetry with ours, but only if it is a well-formed UUID — an
 * unvalidated header would let a caller poison the log with arbitrary text.
 */
final class CorrelationId
{
    private static ?string $current = null;

    public static function set(?string $candidate): string
    {
        self::$current = ($candidate !== null && Str::isUuid($candidate))
            ? $candidate
            : (string) Str::uuid();

        return self::$current;
    }

    public static function current(): string
    {
        return self::$current ??= (string) Str::uuid();
    }

    public static function reset(): void
    {
        self::$current = null;
    }
}
