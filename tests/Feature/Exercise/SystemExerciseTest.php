<?php

declare(strict_types=1);

namespace Tests\Feature\Exercise;

use App\Models\Exercise;
use App\Models\User;
use Database\Seeders\SystemExerciseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * EX-008 — system exercises are immutable for ordinary users.
 *
 * The shared library sits underneath everyone's training history. If a user
 * could rename or archive one, they would be editing the meaning of workouts
 * other people already performed.
 */
final class SystemExerciseTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_system_exercise_can_be_read_by_anyone(): void
    {
        $user = User::factory()->create();
        $system = Exercise::factory()->system()->create(['name' => 'Barbell Back Squat']);

        $this->actingAs($user)
            ->getJson("/api/v1/exercises/{$system->id}")
            ->assertOk()
            ->assertJsonPath('exercise.name', 'Barbell Back Squat')
            ->assertJsonPath('exercise.is_system', true);
    }

    public function test_a_system_exercise_cannot_be_updated(): void
    {
        $user = User::factory()->create();
        $system = Exercise::factory()->system()->create(['name' => 'Barbell Back Squat']);

        $this->actingAs($user)
            ->patchJson("/api/v1/exercises/{$system->id}", ['name' => 'My Squat'])
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'forbidden');

        $this->assertSame('Barbell Back Squat', $system->fresh()->name);
    }

    public function test_a_system_exercise_cannot_be_deleted_or_archived(): void
    {
        $user = User::factory()->create();
        $system = Exercise::factory()->system()->create();

        $this->actingAs($user)
            ->deleteJson("/api/v1/exercises/{$system->id}")
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'forbidden');

        $this->assertDatabaseHas('exercises', ['id' => $system->id]);
        $this->assertNull($system->fresh()->archived_at);
    }

    /** The shared library is seedable on every deploy without duplicating. */
    public function test_the_system_library_seeder_is_idempotent(): void
    {
        $this->seed(SystemExerciseSeeder::class);
        $afterFirstRun = Exercise::whereNull('user_id')->count();

        $this->seed(SystemExerciseSeeder::class);

        $this->assertGreaterThan(0, $afterFirstRun);
        $this->assertSame($afterFirstRun, Exercise::whereNull('user_id')->count());
    }

    /**
     * The assisted machine must not be seeded as externally loaded, or the
     * progression engine would eventually be told to add weight to it.
     */
    public function test_the_seeded_library_classifies_loading_correctly(): void
    {
        $this->seed(SystemExerciseSeeder::class);

        $this->assertSame(
            'assisted_bodyweight',
            Exercise::firstWhere('name', 'Assisted Pull-Up')->loading_type->value,
        );
        $this->assertSame(
            'bodyweight',
            Exercise::firstWhere('name', 'Pull-Up')->loading_type->value,
        );
        $this->assertSame(
            'external_weight',
            Exercise::firstWhere('name', 'Barbell Back Squat')->loading_type->value,
        );
        $this->assertTrue(Exercise::firstWhere('name', 'Dumbbell Row')->is_unilateral);
    }
}
