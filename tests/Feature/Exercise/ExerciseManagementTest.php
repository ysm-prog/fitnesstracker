<?php

declare(strict_types=1);

namespace Tests\Feature\Exercise;

use App\Models\Exercise;
use App\Models\TemplateExercise;
use App\Models\User;
use App\Models\WorkoutTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class ExerciseManagementTest extends TestCase
{
    use RefreshDatabase;

    /** EX-002 … EX-007 */
    public function test_a_user_can_create_a_custom_exercise(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/exercises', [
                'name' => '  Safety Bar Squat  ',
                'primary_muscle' => 'quads',
                'secondary_muscles' => ['glutes', 'core'],
                'equipment' => 'barbell',
                'instructions' => 'Upright torso, controlled descent.',
                'loading_type' => 'external_weight',
                'default_weight_increment_kg' => 2.5,
                'is_unilateral' => false,
                'is_bodyweight' => false,
                'default_rest_seconds' => 210,
            ])
            ->assertCreated()
            ->assertJsonPath('exercise.name', 'Safety Bar Squat')
            ->assertJsonPath('exercise.is_system', false)
            ->assertJsonPath('exercise.secondary_muscles', ['glutes', 'core'])
            ->assertJsonPath('exercise.default_rest_seconds', 210);

        $this->assertDatabaseHas('exercises', ['name' => 'Safety Bar Squat', 'user_id' => $user->id]);
    }

    public function test_a_name_must_be_unique_within_the_users_own_library(): void
    {
        $user = User::factory()->create();
        Exercise::factory()->for($user)->create(['name' => 'Safety Bar Squat']);

        $this->actingAs($user)
            ->postJson('/api/v1/exercises', [
                'name' => 'Safety Bar Squat',
                'primary_muscle' => 'quads',
                'equipment' => 'barbell',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    /** A custom exercise may share a name with a system one: that is a variant. */
    public function test_a_custom_exercise_may_reuse_a_system_name(): void
    {
        $user = User::factory()->create();
        Exercise::factory()->system()->create(['name' => 'Barbell Back Squat']);

        $this->actingAs($user)
            ->postJson('/api/v1/exercises', [
                'name' => 'Barbell Back Squat',
                'primary_muscle' => 'quads',
                'equipment' => 'barbell',
            ])
            ->assertCreated();
    }

    public function test_a_user_can_update_their_own_exercise(): void
    {
        $user = User::factory()->create();
        $exercise = Exercise::factory()->for($user)->create(['default_rest_seconds' => 120]);

        $this->actingAs($user)
            ->patchJson("/api/v1/exercises/{$exercise->id}", ['default_rest_seconds' => 240])
            ->assertOk()
            ->assertJsonPath('exercise.default_rest_seconds', 240);
    }

    /**
     * EX-003 — a bodyweight movement cannot also be externally loaded. The
     * combination would tell the coach to add plates to a pull-up.
     */
    public function test_incoherent_loading_combinations_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/exercises', [
                'name' => 'Weighted Confusion',
                'primary_muscle' => 'lats',
                'equipment' => 'bodyweight',
                'loading_type' => 'external_weight',
                'is_bodyweight' => true,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('loading_type');

        $this->actingAs($user)
            ->postJson('/api/v1/exercises', [
                'name' => 'Other Confusion',
                'primary_muscle' => 'lats',
                'equipment' => 'bodyweight',
                'loading_type' => 'bodyweight',
                'is_bodyweight' => false,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('is_bodyweight');
    }

    /** A partial update is checked against the resulting exercise, not the payload. */
    public function test_a_partial_update_cannot_create_an_incoherent_exercise(): void
    {
        $user = User::factory()->create();
        $exercise = Exercise::factory()->for($user)->bodyweight()->create();

        $this->actingAs($user)
            ->patchJson("/api/v1/exercises/{$exercise->id}", ['loading_type' => 'external_weight'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('loading_type');
    }

    #[DataProvider('invalidExerciseFields')]
    public function test_invalid_fields_are_rejected(string $field, mixed $value): void
    {
        $user = User::factory()->create();
        $exercise = Exercise::factory()->for($user)->create();

        $this->actingAs($user)
            ->patchJson("/api/v1/exercises/{$exercise->id}", [$field => $value])
            ->assertStatus(422)
            ->assertJsonValidationErrors($field);
    }

    /** @return array<string, array{string, mixed}> */
    public static function invalidExerciseFields(): array
    {
        return [
            'unknown muscle' => ['primary_muscle', 'spleen'],
            'unknown equipment' => ['equipment', 'vibes'],
            'unknown loading type' => ['loading_type', 'telekinesis'],
            'zero increment' => ['default_weight_increment_kg', 0],
            'negative increment' => ['default_weight_increment_kg', -2.5],
            'absurd increment' => ['default_weight_increment_kg', 51],
            'negative rest' => ['default_rest_seconds', -1],
            'rest beyond fifteen minutes' => ['default_rest_seconds', 901],
        ];
    }

    /** EX-009 — an exercise nothing depends on is deleted outright. */
    public function test_an_unused_exercise_is_deleted(): void
    {
        $user = User::factory()->create();
        $exercise = Exercise::factory()->for($user)->create();

        $this->actingAs($user)
            ->deleteJson("/api/v1/exercises/{$exercise->id}")
            ->assertOk()
            ->assertJsonPath('action', 'deleted');

        $this->assertDatabaseMissing('exercises', ['id' => $exercise->id]);
    }

    /**
     * EX-009 — the one that matters. A program that prescribes an exercise must
     * keep being able to name it, so the exercise is archived, not destroyed.
     */
    public function test_a_referenced_exercise_is_archived_not_deleted(): void
    {
        $user = User::factory()->create();
        $exercise = Exercise::factory()->for($user)->create();
        $program = WorkoutTemplate::factory()->for($user)->create();
        TemplateExercise::factory()->for($program)->for($exercise)->create();

        $this->actingAs($user)
            ->deleteJson("/api/v1/exercises/{$exercise->id}")
            ->assertOk()
            ->assertJsonPath('action', 'archived')
            ->assertJsonPath('exercise.is_archived', true);

        $this->assertDatabaseHas('exercises', ['id' => $exercise->id]);
        $this->assertNotNull($exercise->fresh()->archived_at);
    }

    public function test_an_archived_exercise_can_be_restored(): void
    {
        $user = User::factory()->create();
        $exercise = Exercise::factory()->for($user)->archived()->create();

        $this->actingAs($user)
            ->postJson("/api/v1/exercises/{$exercise->id}/restore")
            ->assertOk()
            ->assertJsonPath('exercise.is_archived', false);

        $this->assertNull($exercise->fresh()->archived_at);
    }

    public function test_restoring_something_that_is_not_archived_is_a_conflict(): void
    {
        $user = User::factory()->create();
        $exercise = Exercise::factory()->for($user)->create();

        $this->actingAs($user)
            ->postJson("/api/v1/exercises/{$exercise->id}/restore")
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'conflict');
    }
}
