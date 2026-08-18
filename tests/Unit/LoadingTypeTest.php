<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\LoadingType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The loading type decides what the coach is allowed to do with an exercise
 * from Milestone 9. These are the distinctions the progression rules depend on,
 * pinned now so a later edit to the enum cannot quietly change them.
 */
final class LoadingTypeTest extends TestCase
{
    #[DataProvider('externalLoadExpectations')]
    public function test_only_external_weight_supports_external_load(LoadingType $type, bool $expected): void
    {
        $this->assertSame($expected, $type->supportsExternalLoad());
    }

    /** @return array<string, array{LoadingType, bool}> */
    public static function externalLoadExpectations(): array
    {
        return [
            'external weight' => [LoadingType::ExternalWeight, true],
            'bodyweight' => [LoadingType::Bodyweight, false],
            'assisted' => [LoadingType::AssistedBodyweight, false],
            'time' => [LoadingType::Time, false],
            'distance' => [LoadingType::Distance, false],
        ];
    }

    public function test_assisted_movements_progress_by_reducing_assistance(): void
    {
        $this->assertTrue(LoadingType::AssistedBodyweight->progressesByReducingAssistance());

        foreach ([LoadingType::ExternalWeight, LoadingType::Bodyweight, LoadingType::Time, LoadingType::Distance] as $type) {
            $this->assertFalse($type->progressesByReducingAssistance());
        }
    }

    public function test_bodyweight_movements_progress_by_repetitions(): void
    {
        $this->assertTrue(LoadingType::Bodyweight->progressesByRepetitions());
        $this->assertFalse(LoadingType::AssistedBodyweight->progressesByRepetitions());
        $this->assertFalse(LoadingType::ExternalWeight->progressesByRepetitions());
    }

    /** The stored values are a database contract; renaming one rewrites history. */
    public function test_the_stored_values_are_stable(): void
    {
        $this->assertSame(
            ['external_weight', 'bodyweight', 'assisted_bodyweight', 'time', 'distance'],
            LoadingType::values(),
        );
    }
}
