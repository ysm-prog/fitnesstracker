<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

/**
 * SEC-012 / API-008 — every failure leaves through one envelope, and nothing
 * internal leaves with it.
 */
final class ErrorEnvelopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_unknown_route_returns_the_envelope(): void
    {
        $this->getJson('/api/v1/no-such-thing')
            ->assertStatus(404)
            ->assertJsonPath('error_code', 'not_found')
            ->assertJsonStructure(['error_code', 'message', 'correlation_id']);
    }

    public function test_a_wrong_method_returns_the_envelope(): void
    {
        $this->getJson('/api/v1/auth/login')
            ->assertStatus(405)
            ->assertJsonPath('error_code', 'method_not_allowed');
    }

    public function test_a_supplied_correlation_id_is_echoed_back(): void
    {
        $correlationId = '0195f3c2-1c8a-7f4d-9b3e-2a5c6d7e8f90';

        $response = $this->withHeader('X-Correlation-Id', $correlationId)
            ->getJson('/api/v1/no-such-thing')
            ->assertStatus(404)
            ->assertJsonPath('correlation_id', $correlationId);

        $this->assertSame($correlationId, $response->headers->get('X-Correlation-Id'));
    }

    public function test_a_junk_correlation_id_is_replaced_not_reflected(): void
    {
        $response = $this->withHeader('X-Correlation-Id', '<script>alert(1)</script>')
            ->getJson('/api/v1/no-such-thing')
            ->assertStatus(404);

        $this->assertNotSame('<script>alert(1)</script>', $response->json('correlation_id'));
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            (string) $response->json('correlation_id'),
        );
    }

    /**
     * The one that matters: an unexpected failure must not leak the message,
     * the class, the file, the trace, or anything resembling SQL.
     */
    public function test_an_unexpected_failure_leaks_nothing(): void
    {
        Route::middleware('api')->prefix('api/v1')->get('boom', function (): void {
            throw new RuntimeException(
                'SQLSTATE[42P01]: pgsql:host=db.internal;dbname=secret password=hunter2',
            );
        });

        $response = $this->getJson('/api/v1/boom')
            ->assertStatus(500)
            ->assertJsonPath('error_code', 'server_error')
            ->assertJsonStructure(['error_code', 'message', 'correlation_id']);

        $body = $response->getContent();

        foreach (['SQLSTATE', 'hunter2', 'db.internal', 'RuntimeException', 'vendor/', '#0 '] as $leak) {
            $this->assertStringNotContainsString($leak, $body, "Response leaked: {$leak}");
        }
    }
}
