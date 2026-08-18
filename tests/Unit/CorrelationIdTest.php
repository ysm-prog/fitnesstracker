<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\CorrelationId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CorrelationIdTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        CorrelationId::reset();
    }

    public function test_a_valid_uuid_is_adopted(): void
    {
        $uuid = '0195f3c2-1c8a-7f4d-9b3e-2a5c6d7e8f90';

        $this->assertSame($uuid, CorrelationId::set($uuid));
        $this->assertSame($uuid, CorrelationId::current());
    }

    #[DataProvider('rejectedValues')]
    public function test_anything_that_is_not_a_uuid_is_replaced(?string $candidate): void
    {
        $assigned = CorrelationId::set($candidate);

        $this->assertNotSame($candidate, $assigned);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $assigned,
        );
    }

    /** @return array<string, array{?string}> */
    public static function rejectedValues(): array
    {
        return [
            'null' => [null],
            'empty' => [''],
            'plain text' => ['not-a-uuid'],
            'injection attempt' => ["a\nX-Injected: yes"],
            'almost a uuid' => ['0195f3c2-1c8a-7f4d-9b3e-2a5c6d7e8f9'],
        ];
    }

    public function test_it_generates_one_when_never_set(): void
    {
        $first = CorrelationId::current();

        $this->assertSame($first, CorrelationId::current());
    }
}
