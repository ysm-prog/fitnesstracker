<?php

declare(strict_types=1);

namespace Tests\Feature\Program;

use App\Models\Exercise;
use App\Models\TemplateExercise;
use App\Models\User;
use App\Models\WorkoutTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PROG-007 — ordering.
 *
 * Position is dense and 1-based. These tests exist because the obvious
 * implementation — assigning final positions one row at a time — collides with
 * the unique index the moment two exercises swap.
 */
final class ProgramOrderingTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{WorkoutTemplate, list<TemplateExercise>} */
    private function programWithExercises(User $user, int $count): array
    {
        $program = WorkoutTemplate::factory()->for($user)->create();
        $exercises = [];

        for ($i = 0; $i < $count; $i++) {
            $exercises[] = TemplateExercise::factory()
                ->for($program)
                ->for(Exercise::factory()->for($user))
                ->create();
        }

        return [$program, $exercises];
    }

    public function test_a_program_can_be_reordered(): void
    {
        $user = User::factory()->create();
        [$program, $exercises] = $this->programWithExercises($user, 4);

        $reversed = array_reverse(array_map(fn ($e) => $e->id, $exercises));

        $response = $this->actingAs($user)
            ->putJson("/api/v1/programs/{$program->id}/exercises/reorder", [
                'template_exercise_ids' => $reversed,
            ])
            ->assertOk();

        $returned = $response->json('program.exercises');

        $this->assertSame($reversed, array_column($returned, 'id'));
        $this->assertSame([1, 2, 3, 4], array_column($returned, 'position'));
    }

    /** The case a naive one-row-at-a-time update breaks on. */
    public function test_swapping_two_adjacent_exercises_does_not_collide(): void
    {
        $user = User::factory()->create();
        [$program, $exercises] = $this->programWithExercises($user, 2);

        $swapped = [$exercises[1]->id, $exercises[0]->id];

        $this->actingAs($user)
            ->putJson("/api/v1/programs/{$program->id}/exercises/reorder", [
                'template_exercise_ids' => $swapped,
            ])
            ->assertOk();

        $this->assertSame(2, $exercises[0]->fresh()->position);
        $this->assertSame(1, $exercises[1]->fresh()->position);
    }

    public function test_reordering_requires_the_complete_sequence(): void
    {
        $user = User::factory()->create();
        [$program, $exercises] = $this->programWithExercises($user, 3);

        $this->actingAs($user)
            ->putJson("/api/v1/programs/{$program->id}/exercises/reorder", [
                'template_exercise_ids' => [$exercises[0]->id, $exercises[1]->id],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('template_exercise_ids');

        $this->assertSame(1, $exercises[0]->fresh()->position);
    }

    public function test_a_repeated_identifier_is_rejected(): void
    {
        $user = User::factory()->create();
        [$program, $exercises] = $this->programWithExercises($user, 2);

        $this->actingAs($user)
            ->putJson("/api/v1/programs/{$program->id}/exercises/reorder", [
                'template_exercise_ids' => [$exercises[0]->id, $exercises[0]->id],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('template_exercise_ids');
    }

    public function test_an_exercise_from_another_program_is_rejected(): void
    {
        $user = User::factory()->create();
        [$program, $exercises] = $this->programWithExercises($user, 2);
        [, $otherExercises] = $this->programWithExercises($user, 1);

        $this->actingAs($user)
            ->putJson("/api/v1/programs/{$program->id}/exercises/reorder", [
                'template_exercise_ids' => [$exercises[0]->id, $otherExercises[0]->id],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('template_exercise_ids');
    }

    /** Removing from the middle closes the gap rather than leaving a hole. */
    public function test_removing_an_exercise_compacts_the_sequence(): void
    {
        $user = User::factory()->create();
        [$program, $exercises] = $this->programWithExercises($user, 4);

        $this->actingAs($user)
            ->deleteJson("/api/v1/programs/{$program->id}/exercises/{$exercises[1]->id}")
            ->assertOk();

        $remaining = $program->templateExercises()->get();

        $this->assertCount(3, $remaining);
        $this->assertSame([1, 2, 3], $remaining->pluck('position')->all());
        $this->assertSame(
            [$exercises[0]->id, $exercises[2]->id, $exercises[3]->id],
            $remaining->pluck('id')->all(),
        );
    }

    /** After a removal, the next append continues the sequence without a clash. */
    public function test_appending_after_a_removal_uses_the_next_free_position(): void
    {
        $user = User::factory()->create();
        [$program, $exercises] = $this->programWithExercises($user, 3);

        $this->actingAs($user)
            ->deleteJson("/api/v1/programs/{$program->id}/exercises/{$exercises[2]->id}")
            ->assertOk();

        $this->actingAs($user)
            ->postJson("/api/v1/programs/{$program->id}/exercises", [
                'exercise_id' => Exercise::factory()->for($user)->create()->id,
                'target_sets' => 3,
                'min_reps' => 8,
                'max_reps' => 10,
                'rest_seconds' => 120,
            ])
            ->assertCreated()
            ->assertJsonPath('template_exercise.position', 3);
    }

    /** A program is always read back in the order it was written. */
    public function test_a_program_reads_back_in_position_order(): void
    {
        $user = User::factory()->create();
        [$program, $exercises] = $this->programWithExercises($user, 3);

        $reversed = array_reverse(array_map(fn ($e) => $e->id, $exercises));

        $this->actingAs($user)->putJson("/api/v1/programs/{$program->id}/exercises/reorder", [
            'template_exercise_ids' => $reversed,
        ])->assertOk();

        $fetched = $this->actingAs($user)
            ->getJson("/api/v1/programs/{$program->id}")
            ->assertOk()
            ->json('program.exercises');

        $this->assertSame($reversed, array_column($fetched, 'id'));
    }
}
