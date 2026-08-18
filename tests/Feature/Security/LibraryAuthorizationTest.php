<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\Exercise;
use App\Models\TemplateExercise;
use App\Models\User;
use App\Models\WorkoutTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SEC-005 / SEC-007 — the IDOR suite for Milestone 2.
 *
 * Every route added in this milestone takes an identifier from the URL, which
 * is exactly the surface the earlier milestone did not have. User A must not be
 * able to read, change, or destroy anything of User B's by naming it.
 */
final class LibraryAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_cannot_read_another_users_exercise(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $exercise = Exercise::factory()->for($owner)->create();

        $this->actingAs($stranger)
            ->getJson("/api/v1/exercises/{$exercise->id}")
            ->assertStatus(404)
            ->assertJsonPath('error_code', 'not_found');
    }

    public function test_a_user_cannot_change_or_destroy_another_users_exercise(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $exercise = Exercise::factory()->for($owner)->create(['name' => 'Owner Movement']);

        $this->actingAs($stranger)
            ->patchJson("/api/v1/exercises/{$exercise->id}", ['name' => 'Hijacked'])
            ->assertStatus(404);

        $this->actingAs($stranger)
            ->deleteJson("/api/v1/exercises/{$exercise->id}")
            ->assertStatus(404);

        $this->actingAs($stranger)
            ->postJson("/api/v1/exercises/{$exercise->id}/restore")
            ->assertStatus(404);

        $this->assertSame('Owner Movement', $exercise->fresh()->name);
        $this->assertNull($exercise->fresh()->archived_at);
    }

    public function test_a_user_cannot_read_or_change_another_users_program(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $program = WorkoutTemplate::factory()->for($owner)->create(['name' => 'Owner Program']);

        foreach ([
            ['getJson', "/api/v1/programs/{$program->id}", []],
            ['patchJson', "/api/v1/programs/{$program->id}", ['name' => 'Hijacked']],
            ['deleteJson', "/api/v1/programs/{$program->id}", []],
            ['postJson', "/api/v1/programs/{$program->id}/restore", []],
            ['postJson', "/api/v1/programs/{$program->id}/duplicate", ['name' => 'Stolen']],
        ] as [$method, $uri, $payload]) {
            $this->actingAs($stranger)->{$method}($uri, $payload)->assertStatus(404);
        }

        $this->assertSame('Owner Program', $program->fresh()->name);
        $this->assertNull($program->fresh()->archived_at);
        $this->assertDatabaseMissing('workout_templates', ['name' => 'Stolen']);
    }

    public function test_a_user_cannot_touch_prescriptions_in_another_users_program(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $program = WorkoutTemplate::factory()->for($owner)->create();
        $prescription = TemplateExercise::factory()->for($program)
            ->for(Exercise::factory()->for($owner))
            ->create(['target_sets' => 3]);

        $this->actingAs($stranger)
            ->postJson("/api/v1/programs/{$program->id}/exercises", [
                'exercise_id' => Exercise::factory()->for($stranger)->create()->id,
                'target_sets' => 3,
                'min_reps' => 8,
                'max_reps' => 10,
                'rest_seconds' => 120,
            ])
            ->assertStatus(404);

        $this->actingAs($stranger)
            ->patchJson("/api/v1/programs/{$program->id}/exercises/{$prescription->id}", ['target_sets' => 9])
            ->assertStatus(404);

        $this->actingAs($stranger)
            ->deleteJson("/api/v1/programs/{$program->id}/exercises/{$prescription->id}")
            ->assertStatus(404);

        $this->actingAs($stranger)
            ->putJson("/api/v1/programs/{$program->id}/exercises/reorder", [
                'template_exercise_ids' => [$prescription->id],
            ])
            ->assertStatus(404);

        $this->assertSame(3, $prescription->fresh()->target_sets);
        $this->assertDatabaseCount('template_exercises', 1);
    }

    /**
     * A user_id in the payload must not be able to plant a row in someone
     * else's library, however it is spelled.
     */
    public function test_a_client_supplied_owner_is_ignored_on_creation(): void
    {
        $victim = User::factory()->create();
        $attacker = User::factory()->create();

        $exerciseId = $this->actingAs($attacker)
            ->postJson('/api/v1/exercises', [
                'user_id' => $victim->id,
                'name' => 'Planted Exercise',
                'primary_muscle' => 'chest',
                'equipment' => 'barbell',
            ])
            ->assertCreated()
            ->json('exercise.id');

        $programId = $this->actingAs($attacker)
            ->postJson('/api/v1/programs', [
                'user_id' => $victim->id,
                'name' => 'Planted Program',
            ])
            ->assertCreated()
            ->json('program.id');

        $this->assertDatabaseHas('exercises', ['id' => $exerciseId, 'user_id' => $attacker->id]);
        $this->assertDatabaseHas('workout_templates', ['id' => $programId, 'user_id' => $attacker->id]);
        $this->assertSame(0, $victim->exercises()->count());
        $this->assertSame(0, $victim->workoutTemplates()->count());
    }

    /** A program listing must never include somebody else's programs. */
    public function test_listings_are_scoped_to_the_caller(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        WorkoutTemplate::factory()->for($owner)->create(['name' => 'Owner Program']);
        Exercise::factory()->for($owner)->create(['name' => 'Owner Movement']);

        $programs = $this->actingAs($stranger)->getJson('/api/v1/programs')->assertOk();
        $this->assertSame([], $programs->json('programs'));

        $exercises = $this->actingAs($stranger)->getJson('/api/v1/exercises')->assertOk();
        $this->assertNotContains('Owner Movement', array_column($exercises->json('exercises'), 'name'));
    }

    public function test_every_library_route_refuses_an_anonymous_caller(): void
    {
        $user = User::factory()->create();
        $exercise = Exercise::factory()->for($user)->create();
        $program = WorkoutTemplate::factory()->for($user)->create();
        $prescription = TemplateExercise::factory()->for($program)->for($exercise)->create();

        $routes = [
            ['getJson', '/api/v1/exercises'],
            ['postJson', '/api/v1/exercises'],
            ['getJson', "/api/v1/exercises/{$exercise->id}"],
            ['patchJson', "/api/v1/exercises/{$exercise->id}"],
            ['deleteJson', "/api/v1/exercises/{$exercise->id}"],
            ['postJson', "/api/v1/exercises/{$exercise->id}/restore"],
            ['getJson', '/api/v1/programs'],
            ['postJson', '/api/v1/programs'],
            ['getJson', "/api/v1/programs/{$program->id}"],
            ['patchJson', "/api/v1/programs/{$program->id}"],
            ['deleteJson', "/api/v1/programs/{$program->id}"],
            ['postJson', "/api/v1/programs/{$program->id}/restore"],
            ['postJson', "/api/v1/programs/{$program->id}/duplicate"],
            ['postJson', "/api/v1/programs/{$program->id}/exercises"],
            ['putJson', "/api/v1/programs/{$program->id}/exercises/reorder"],
            ['patchJson', "/api/v1/programs/{$program->id}/exercises/{$prescription->id}"],
            ['deleteJson', "/api/v1/programs/{$program->id}/exercises/{$prescription->id}"],
        ];

        foreach ($routes as [$method, $uri]) {
            $this->{$method}($uri)
                ->assertStatus(401)
                ->assertJsonPath('error_code', 'unauthenticated');
        }
    }
}
