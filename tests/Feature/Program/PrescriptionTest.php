<?php

declare(strict_types=1);

namespace Tests\Feature\Program;

use App\Models\Exercise;
use App\Models\TemplateExercise;
use App\Models\User;
use App\Models\WorkoutTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class PrescriptionTest extends TestCase
{
    use RefreshDatabase;

    private function programFor(User $user): WorkoutTemplate
    {
        return WorkoutTemplate::factory()->for($user)->create();
    }

    /** PROG-002, PROG-003 */
    public function test_an_exercise_can_be_added_to_a_program(): void
    {
        $user = User::factory()->create();
        $program = $this->programFor($user);
        $exercise = Exercise::factory()->for($user)->create();

        $this->actingAs($user)
            ->postJson("/api/v1/programs/{$program->id}/exercises", [
                'exercise_id' => $exercise->id,
                'target_sets' => 4,
                'min_reps' => 6,
                'max_reps' => 8,
                'target_rir' => 2,
                'rest_seconds' => 180,
                'is_optional' => false,
                'notes' => 'Leave one in the tank on the last set.',
            ])
            ->assertCreated()
            ->assertJsonPath('template_exercise.position', 1)
            ->assertJsonPath('template_exercise.prescription.target_sets', 4)
            ->assertJsonPath('template_exercise.prescription.min_reps', 6)
            ->assertJsonPath('template_exercise.prescription.target_rir', 2)
            ->assertJsonPath('template_exercise.exercise.id', $exercise->id);
    }

    public function test_added_exercises_take_the_next_position(): void
    {
        $user = User::factory()->create();
        $program = $this->programFor($user);

        foreach (range(1, 3) as $expectedPosition) {
            $exercise = Exercise::factory()->for($user)->create();

            $this->actingAs($user)
                ->postJson("/api/v1/programs/{$program->id}/exercises", [
                    'exercise_id' => $exercise->id,
                    'target_sets' => 3,
                    'min_reps' => 8,
                    'max_reps' => 10,
                    'rest_seconds' => 120,
                ])
                ->assertCreated()
                ->assertJsonPath('template_exercise.position', $expectedPosition);
        }
    }

    /** PROG-004 — the ranges the brief pins down. */
    #[DataProvider('invalidPrescriptions')]
    public function test_prescription_ranges_are_enforced(array $overrides, string $errorKey): void
    {
        $user = User::factory()->create();
        $program = $this->programFor($user);
        $exercise = Exercise::factory()->for($user)->create();

        $this->actingAs($user)
            ->postJson("/api/v1/programs/{$program->id}/exercises", array_merge([
                'exercise_id' => $exercise->id,
                'target_sets' => 3,
                'min_reps' => 8,
                'max_reps' => 10,
                'rest_seconds' => 120,
            ], $overrides))
            ->assertStatus(422)
            ->assertJsonValidationErrors($errorKey);
    }

    /** @return array<string, array{array<string, mixed>, string}> */
    public static function invalidPrescriptions(): array
    {
        return [
            'zero sets' => [['target_sets' => 0], 'target_sets'],
            'twenty-one sets' => [['target_sets' => 21], 'target_sets'],
            'zero minimum reps' => [['min_reps' => 0], 'min_reps'],
            'one hundred and one reps' => [['max_reps' => 101], 'max_reps'],
            'minimum above maximum' => [['min_reps' => 12, 'max_reps' => 8], 'min_reps'],
            'negative RIR' => [['target_rir' => -1], 'target_rir'],
            'RIR above five' => [['target_rir' => 6], 'target_rir'],
            'negative rest' => [['rest_seconds' => -1], 'rest_seconds'],
            'rest beyond fifteen minutes' => [['rest_seconds' => 901], 'rest_seconds'],
        ];
    }

    /** min_reps == max_reps is a fixed prescription, not an error. */
    public function test_a_fixed_rep_target_is_allowed(): void
    {
        $user = User::factory()->create();
        $program = $this->programFor($user);
        $exercise = Exercise::factory()->for($user)->create();

        $this->actingAs($user)
            ->postJson("/api/v1/programs/{$program->id}/exercises", [
                'exercise_id' => $exercise->id,
                'target_sets' => 5,
                'min_reps' => 5,
                'max_reps' => 5,
                'rest_seconds' => 180,
            ])
            ->assertCreated();
    }

    /**
     * `exists` on its own would let a caller prescribe another user's private
     * exercise by guessing an identifier.
     */
    public function test_another_users_exercise_cannot_be_prescribed(): void
    {
        $user = User::factory()->create();
        $program = $this->programFor($user);
        $someoneElses = Exercise::factory()->create();

        $this->actingAs($user)
            ->postJson("/api/v1/programs/{$program->id}/exercises", [
                'exercise_id' => $someoneElses->id,
                'target_sets' => 3,
                'min_reps' => 8,
                'max_reps' => 10,
                'rest_seconds' => 120,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('exercise_id');
    }

    public function test_an_archived_exercise_cannot_be_prescribed(): void
    {
        $user = User::factory()->create();
        $program = $this->programFor($user);
        $archived = Exercise::factory()->for($user)->archived()->create();

        $this->actingAs($user)
            ->postJson("/api/v1/programs/{$program->id}/exercises", [
                'exercise_id' => $archived->id,
                'target_sets' => 3,
                'min_reps' => 8,
                'max_reps' => 10,
                'rest_seconds' => 120,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('exercise_id');
    }

    /** PROG-007 — editing a prescription in place. */
    public function test_a_prescription_can_be_edited(): void
    {
        $user = User::factory()->create();
        $program = $this->programFor($user);
        $prescription = TemplateExercise::factory()->for($program)
            ->for(Exercise::factory()->for($user))
            ->create(['target_sets' => 3, 'min_reps' => 8, 'max_reps' => 10]);

        $this->actingAs($user)
            ->patchJson("/api/v1/programs/{$program->id}/exercises/{$prescription->id}", [
                'target_sets' => 5,
                'min_reps' => 3,
                'max_reps' => 5,
            ])
            ->assertOk()
            ->assertJsonPath('template_exercise.prescription.target_sets', 5)
            ->assertJsonPath('template_exercise.prescription.max_reps', 5);
    }

    /**
     * A partial edit is validated against the resulting row: sending only a new
     * minimum must not be able to leave it above the stored maximum.
     */
    public function test_a_partial_edit_cannot_invert_the_rep_range(): void
    {
        $user = User::factory()->create();
        $program = $this->programFor($user);
        $prescription = TemplateExercise::factory()->for($program)
            ->for(Exercise::factory()->for($user))
            ->create(['min_reps' => 8, 'max_reps' => 10]);

        $this->actingAs($user)
            ->patchJson("/api/v1/programs/{$program->id}/exercises/{$prescription->id}", ['min_reps' => 12])
            ->assertStatus(422)
            ->assertJsonValidationErrors('min_reps');

        $this->assertSame(8, $prescription->fresh()->min_reps);
    }

    /** PROG-007 — replacement keeps the prescription and swaps the movement. */
    public function test_the_exercise_can_be_replaced_in_place(): void
    {
        $user = User::factory()->create();
        $program = $this->programFor($user);
        $original = Exercise::factory()->for($user)->create();
        $replacement = Exercise::factory()->for($user)->create();
        $prescription = TemplateExercise::factory()->for($program)->for($original)
            ->create(['target_sets' => 4, 'min_reps' => 6, 'max_reps' => 8]);

        $this->actingAs($user)
            ->patchJson("/api/v1/programs/{$program->id}/exercises/{$prescription->id}", [
                'exercise_id' => $replacement->id,
            ])
            ->assertOk()
            ->assertJsonPath('template_exercise.exercise.id', $replacement->id)
            ->assertJsonPath('template_exercise.prescription.target_sets', 4)
            ->assertJsonPath('template_exercise.position', $prescription->position);
    }

    public function test_optional_work_can_be_marked_as_such(): void
    {
        $user = User::factory()->create();
        $program = $this->programFor($user);
        $exercise = Exercise::factory()->for($user)->create();

        $this->actingAs($user)
            ->postJson("/api/v1/programs/{$program->id}/exercises", [
                'exercise_id' => $exercise->id,
                'target_sets' => 2,
                'min_reps' => 12,
                'max_reps' => 15,
                'rest_seconds' => 60,
                'is_optional' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('template_exercise.is_optional', true);
    }

    /** A prescription reached through the wrong program identifies nothing. */
    public function test_a_prescription_cannot_be_reached_through_another_program(): void
    {
        $user = User::factory()->create();
        $programA = $this->programFor($user);
        $programB = $this->programFor($user);
        $prescription = TemplateExercise::factory()->for($programA)
            ->for(Exercise::factory()->for($user))
            ->create();

        $this->actingAs($user)
            ->patchJson("/api/v1/programs/{$programB->id}/exercises/{$prescription->id}", ['target_sets' => 9])
            ->assertStatus(404);

        $this->assertNotSame(9, $prescription->fresh()->target_sets);
    }
}
