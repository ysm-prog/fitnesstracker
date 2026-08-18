<?php

declare(strict_types=1);

namespace Tests\Feature\Program;

use App\Models\Exercise;
use App\Models\TemplateExercise;
use App\Models\User;
use App\Models\WorkoutTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProgramManagementTest extends TestCase
{
    use RefreshDatabase;

    /** PROG-001 */
    public function test_a_user_can_create_a_program(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/programs', [
                'name' => '  Upper A  ',
                'description' => 'Upper body, strength emphasis.',
            ])
            ->assertCreated()
            ->assertJsonPath('program.name', 'Upper A')
            ->assertJsonPath('program.is_active', true)
            ->assertJsonPath('program.exercises', []);

        $this->assertDatabaseHas('workout_templates', ['name' => 'Upper A', 'user_id' => $user->id]);
    }

    public function test_program_names_are_unique_per_user(): void
    {
        $user = User::factory()->create();
        WorkoutTemplate::factory()->for($user)->create(['name' => 'Upper A']);

        $this->actingAs($user)
            ->postJson('/api/v1/programs', ['name' => 'Upper A'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    /** PROG-006 — activation and deactivation. */
    public function test_a_program_can_be_deactivated_and_reactivated(): void
    {
        $user = User::factory()->create();
        $program = WorkoutTemplate::factory()->for($user)->create();

        $this->actingAs($user)
            ->patchJson("/api/v1/programs/{$program->id}", ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('program.is_active', false);

        $this->actingAs($user)
            ->patchJson("/api/v1/programs/{$program->id}", ['is_active' => true])
            ->assertOk()
            ->assertJsonPath('program.is_active', true);
    }

    /** Programs are archived rather than destroyed: history refers to them. */
    public function test_deleting_a_program_archives_it(): void
    {
        $user = User::factory()->create();
        $program = WorkoutTemplate::factory()->for($user)->create();

        $this->actingAs($user)
            ->deleteJson("/api/v1/programs/{$program->id}")
            ->assertOk()
            ->assertJsonPath('action', 'archived')
            ->assertJsonPath('program.is_archived', true)
            ->assertJsonPath('program.is_active', false);

        $this->assertDatabaseHas('workout_templates', ['id' => $program->id]);
    }

    public function test_archived_programs_are_hidden_unless_asked_for(): void
    {
        $user = User::factory()->create();
        WorkoutTemplate::factory()->for($user)->create(['name' => 'Current']);
        WorkoutTemplate::factory()->for($user)->archived()->create(['name' => 'Retired']);

        $active = $this->actingAs($user)->getJson('/api/v1/programs')->assertOk();
        $this->assertSame(['Current'], array_column($active->json('programs'), 'name'));

        $all = $this->actingAs($user)->getJson('/api/v1/programs?include_archived=1')->assertOk();
        $this->assertCount(2, $all->json('programs'));
    }

    public function test_an_archived_program_can_be_restored(): void
    {
        $user = User::factory()->create();
        $program = WorkoutTemplate::factory()->for($user)->archived()->create();

        $this->actingAs($user)
            ->postJson("/api/v1/programs/{$program->id}/restore")
            ->assertOk()
            ->assertJsonPath('program.is_archived', false);
    }

    /** PROG-005 — duplication copies the prescriptions, not references to them. */
    public function test_a_program_can_be_duplicated(): void
    {
        $user = User::factory()->create();
        $program = WorkoutTemplate::factory()->for($user)->create(['name' => 'Upper A']);
        $squat = Exercise::factory()->for($user)->create();
        $bench = Exercise::factory()->for($user)->create();
        TemplateExercise::factory()->for($program)->for($squat)->create(['target_sets' => 5, 'min_reps' => 3, 'max_reps' => 5]);
        TemplateExercise::factory()->for($program)->for($bench)->create(['target_sets' => 3, 'min_reps' => 8, 'max_reps' => 10]);

        $response = $this->actingAs($user)
            ->postJson("/api/v1/programs/{$program->id}/duplicate", ['name' => 'Upper A — heavy'])
            ->assertCreated()
            ->assertJsonPath('program.name', 'Upper A — heavy')
            // A duplicate starts inactive: two live copies of one program is
            // almost never what "duplicate" means.
            ->assertJsonPath('program.is_active', false);

        $copyId = $response->json('program.id');
        $this->assertNotSame($program->id, $copyId);

        $exercises = $response->json('program.exercises');
        $this->assertCount(2, $exercises);
        $this->assertSame([1, 2], array_column($exercises, 'position'));
        $this->assertSame(5, $exercises[0]['prescription']['target_sets']);
        $this->assertSame(10, $exercises[1]['prescription']['max_reps']);
    }

    /** The copy must be independent: editing one cannot change the other. */
    public function test_editing_a_duplicate_does_not_touch_the_original(): void
    {
        $user = User::factory()->create();
        $program = WorkoutTemplate::factory()->for($user)->create(['name' => 'Upper A']);
        $exercise = Exercise::factory()->for($user)->create();
        TemplateExercise::factory()->for($program)->for($exercise)->create(['target_sets' => 3]);

        $copyId = $this->actingAs($user)
            ->postJson("/api/v1/programs/{$program->id}/duplicate", ['name' => 'Upper A copy'])
            ->assertCreated()
            ->json('program.id');

        $copiedExerciseId = $this->actingAs($user)
            ->getJson("/api/v1/programs/{$copyId}")
            ->json('program.exercises.0.id');

        $this->actingAs($user)
            ->patchJson("/api/v1/programs/{$copyId}/exercises/{$copiedExerciseId}", ['target_sets' => 8])
            ->assertOk();

        $this->assertSame(3, $program->templateExercises()->first()->target_sets);
    }

    public function test_a_duplicate_cannot_reuse_an_existing_name(): void
    {
        $user = User::factory()->create();
        $program = WorkoutTemplate::factory()->for($user)->create(['name' => 'Upper A']);
        WorkoutTemplate::factory()->for($user)->create(['name' => 'Upper B']);

        $this->actingAs($user)
            ->postJson("/api/v1/programs/{$program->id}/duplicate", ['name' => 'upper b'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_programs_require_authentication(): void
    {
        $this->getJson('/api/v1/programs')->assertStatus(401);
        $this->postJson('/api/v1/programs', ['name' => 'X'])->assertStatus(401);
    }
}
