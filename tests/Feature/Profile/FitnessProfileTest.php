<?php

declare(strict_types=1);

namespace Tests\Feature\Profile;

use App\Models\FitnessProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class FitnessProfileTest extends TestCase
{
    use RefreshDatabase;

    /** PROFILE-001…010 */
    public function test_a_user_can_read_their_profile(): void
    {
        $user = User::factory()->create();
        FitnessProfile::factory()->for($user)->create();

        $this->actingAs($user)
            ->getJson('/api/v1/profile/fitness')
            ->assertOk()
            ->assertJsonPath('fitness_profile.height_cm', fn ($value) => (float) $value === 178.0)
            ->assertJsonPath('fitness_profile.training_level', 'intermediate')
            ->assertJsonPath('fitness_profile.primary_goal', 'lean_muscle_gain');
    }

    public function test_a_profile_is_created_on_first_read(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/profile/fitness')
            ->assertOk()
            ->assertJsonPath('fitness_profile.training_level', 'beginner');

        $this->assertDatabaseHas('fitness_profiles', ['user_id' => $user->id]);
    }

    public function test_a_user_can_update_every_supported_field(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->putJson('/api/v1/profile/fitness', [
                'height_cm' => 182.5,
                'current_body_weight_kg' => 79.4,
                'target_body_weight_kg' => 84.0,
                'training_level' => 'advanced',
                'primary_goal' => 'strength',
                'weight_unit' => 'lb',
                'measurement_unit' => 'in',
                'preferred_session_minutes' => 75,
                'training_days_per_week' => 5,
                'available_training_days' => ['Monday', 'monday', 'WEDNESDAY'],
                'dietary_preference' => 'high protein',
                'training_limitations' => 'Left shoulder impingement; avoid overhead pressing.',
            ])
            ->assertOk()
            ->assertJsonPath('fitness_profile.height_cm', fn ($value) => (float) $value === 182.5)
            ->assertJsonPath('fitness_profile.primary_goal', 'strength')
            ->assertJsonPath('fitness_profile.weight_unit', 'lb')
            ->assertJsonPath('fitness_profile.available_training_days', ['monday', 'wednesday']);
    }

    #[DataProvider('outOfRangeValues')]
    public function test_out_of_range_values_are_rejected(string $field, mixed $value, ?string $errorKey = null): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->putJson('/api/v1/profile/fitness', [$field => $value])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'validation_failed')
            ->assertJsonValidationErrors($errorKey ?? $field);
    }

    /** @return array<string, array{0: string, 1: mixed, 2?: string}> */
    public static function outOfRangeValues(): array
    {
        return [
            'height too small' => ['height_cm', 49],
            'height too large' => ['height_cm', 301],
            'weight too small' => ['current_body_weight_kg', 19.9],
            'weight too large' => ['current_body_weight_kg', 500.1],
            'target weight too large' => ['target_body_weight_kg', 501],
            'session too short' => ['preferred_session_minutes', 9],
            'session too long' => ['preferred_session_minutes', 241],
            'zero training days' => ['training_days_per_week', 0],
            'eight training days' => ['training_days_per_week', 8],
            'unknown training level' => ['training_level', 'olympian'],
            'unknown goal' => ['primary_goal', 'become_a_bird'],
            'unknown weight unit' => ['weight_unit', 'stone'],
            // A per-item rule reports the item's own key, not the array's.
            'unknown day' => ['available_training_days', ['funday'], 'available_training_days.0'],
        ];
    }

    /**
     * SEC-007 — the decisive one. A client that supplies someone else's
     * user_id must not be able to move or write another user's profile.
     */
    public function test_a_client_supplied_user_id_is_ignored(): void
    {
        $victim = User::factory()->create();
        $victimProfile = FitnessProfile::factory()->for($victim)->create(['height_cm' => 165.0]);

        $attacker = User::factory()->create();

        $this->actingAs($attacker)
            ->putJson('/api/v1/profile/fitness', [
                'user_id' => $victim->id,
                'id' => $victimProfile->id,
                'height_cm' => 200.0,
            ])
            ->assertOk();

        $this->assertSame(165.0, (float) $victimProfile->fresh()->height_cm);
        $this->assertSame($victim->id, $victimProfile->fresh()->user_id);
        $this->assertSame(200.0, (float) $attacker->fitnessProfile->height_cm);
    }

    public function test_the_profile_requires_authentication(): void
    {
        $this->getJson('/api/v1/profile/fitness')
            ->assertStatus(401)
            ->assertJsonPath('error_code', 'unauthenticated');

        $this->putJson('/api/v1/profile/fitness', ['height_cm' => 180])
            ->assertStatus(401);
    }
}
