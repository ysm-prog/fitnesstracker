<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Equipment;
use App\Enums\LoadingType;
use App\Enums\MuscleGroup;
use App\Models\Exercise;
use Illuminate\Database\Seeder;

/**
 * The shared exercise library.
 *
 * Idempotent: seeding twice updates in place rather than duplicating, so this
 * can be re-run on every deploy. Increments are the smallest honest jump for
 * the equipment — 2.5 kg for a barbell, 2 kg for most dumbbell pairs, 5 kg for
 * stacks that move in 5s — because that number becomes the only step the
 * progression engine is ever allowed to add.
 */
final class SystemExerciseSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->exercises() as $exercise) {
            Exercise::query()->updateOrCreate(
                ['user_id' => null, 'name' => $exercise['name']],
                $exercise + ['user_id' => null],
            );
        }
    }

    /** @return list<array<string, mixed>> */
    private function exercises(): array
    {
        $barbell = ['equipment' => Equipment::Barbell->value, 'default_weight_increment_kg' => 2.5];
        $dumbbell = ['equipment' => Equipment::Dumbbell->value, 'default_weight_increment_kg' => 2.0];
        $machine = ['equipment' => Equipment::Machine->value, 'default_weight_increment_kg' => 5.0];
        $cable = ['equipment' => Equipment::Cable->value, 'default_weight_increment_kg' => 2.5];

        return [
            $barbell + [
                'name' => 'Barbell Back Squat',
                'primary_muscle' => MuscleGroup::Quads->value,
                'secondary_muscles' => [MuscleGroup::Glutes->value, MuscleGroup::Hamstrings->value, MuscleGroup::Core->value],
                'instructions' => 'Bar on upper back, brace, descend under control to depth, drive up through mid-foot.',
                'default_rest_seconds' => 210,
            ],
            $barbell + [
                'name' => 'Barbell Bench Press',
                'primary_muscle' => MuscleGroup::Chest->value,
                'secondary_muscles' => [MuscleGroup::Triceps->value, MuscleGroup::Shoulders->value],
                'instructions' => 'Shoulder blades retracted, lower to mid-chest, press to lockout.',
                'default_rest_seconds' => 210,
            ],
            $barbell + [
                'name' => 'Conventional Deadlift',
                'primary_muscle' => MuscleGroup::Hamstrings->value,
                'secondary_muscles' => [MuscleGroup::Glutes->value, MuscleGroup::Back->value, MuscleGroup::Traps->value],
                'instructions' => 'Bar over mid-foot, brace, push the floor away, lock out without hyperextending.',
                'default_rest_seconds' => 240,
            ],
            $barbell + [
                'name' => 'Overhead Press',
                'primary_muscle' => MuscleGroup::Shoulders->value,
                'secondary_muscles' => [MuscleGroup::Triceps->value, MuscleGroup::Core->value],
                'instructions' => 'Brace, press overhead, finish with the bar over mid-foot.',
                'default_rest_seconds' => 180,
            ],
            $barbell + [
                'name' => 'Barbell Row',
                'primary_muscle' => MuscleGroup::Back->value,
                'secondary_muscles' => [MuscleGroup::Lats->value, MuscleGroup::Biceps->value],
                'instructions' => 'Hinge to roughly 45 degrees, row to the lower ribs, control the descent.',
                'default_rest_seconds' => 180,
            ],
            $barbell + [
                'name' => 'Romanian Deadlift',
                'primary_muscle' => MuscleGroup::Hamstrings->value,
                'secondary_muscles' => [MuscleGroup::Glutes->value, MuscleGroup::Back->value],
                'instructions' => 'Soft knees, hinge back until hamstrings load, return without rounding.',
                'default_rest_seconds' => 180,
            ],
            $barbell + [
                'name' => 'Front Squat',
                'primary_muscle' => MuscleGroup::Quads->value,
                'secondary_muscles' => [MuscleGroup::Core->value, MuscleGroup::Glutes->value],
                'instructions' => 'Elbows high, upright torso, descend and drive up.',
                'default_rest_seconds' => 210,
            ],
            $barbell + [
                'name' => 'Incline Barbell Press',
                'primary_muscle' => MuscleGroup::Chest->value,
                'secondary_muscles' => [MuscleGroup::Shoulders->value, MuscleGroup::Triceps->value],
                'instructions' => 'Bench at 30 degrees, lower to upper chest, press to lockout.',
                'default_rest_seconds' => 180,
            ],
            $dumbbell + [
                'name' => 'Dumbbell Bench Press',
                'primary_muscle' => MuscleGroup::Chest->value,
                'secondary_muscles' => [MuscleGroup::Triceps->value, MuscleGroup::Shoulders->value],
                'instructions' => 'Lower until the chest stretches, press without clashing the bells.',
                'default_rest_seconds' => 150,
            ],
            $dumbbell + [
                'name' => 'Dumbbell Shoulder Press',
                'primary_muscle' => MuscleGroup::Shoulders->value,
                'secondary_muscles' => [MuscleGroup::Triceps->value],
                'instructions' => 'Press overhead without flaring the ribs.',
                'default_rest_seconds' => 150,
            ],
            $dumbbell + [
                'name' => 'Dumbbell Row',
                'primary_muscle' => MuscleGroup::Lats->value,
                'secondary_muscles' => [MuscleGroup::Back->value, MuscleGroup::Biceps->value],
                'instructions' => 'Row to the hip, keep the torso still.',
                'default_rest_seconds' => 120,
                'is_unilateral' => true,
            ],
            $dumbbell + [
                'name' => 'Bulgarian Split Squat',
                'primary_muscle' => MuscleGroup::Quads->value,
                'secondary_muscles' => [MuscleGroup::Glutes->value],
                'instructions' => 'Rear foot elevated, descend vertically, drive through the front heel.',
                'default_rest_seconds' => 150,
                'is_unilateral' => true,
            ],
            $dumbbell + [
                'name' => 'Dumbbell Curl',
                'primary_muscle' => MuscleGroup::Biceps->value,
                'secondary_muscles' => [MuscleGroup::Forearms->value],
                'instructions' => 'Curl without swinging, control the lowering.',
                'default_rest_seconds' => 90,
                'is_unilateral' => true,
            ],
            $dumbbell + [
                'name' => 'Lateral Raise',
                'primary_muscle' => MuscleGroup::Shoulders->value,
                'secondary_muscles' => [],
                'instructions' => 'Raise to shoulder height, lead with the elbow.',
                'default_rest_seconds' => 90,
                'is_unilateral' => true,
            ],
            $machine + [
                'name' => 'Leg Press',
                'primary_muscle' => MuscleGroup::Quads->value,
                'secondary_muscles' => [MuscleGroup::Glutes->value],
                'instructions' => 'Lower until the hips stay flat on the pad, press without locking hard.',
                'default_rest_seconds' => 180,
            ],
            $machine + [
                'name' => 'Leg Curl',
                'primary_muscle' => MuscleGroup::Hamstrings->value,
                'secondary_muscles' => [MuscleGroup::Calves->value],
                'instructions' => 'Curl fully, lower under control.',
                'default_rest_seconds' => 120,
            ],
            $machine + [
                'name' => 'Leg Extension',
                'primary_muscle' => MuscleGroup::Quads->value,
                'secondary_muscles' => [],
                'instructions' => 'Extend to straight, pause, lower under control.',
                'default_rest_seconds' => 120,
            ],
            $machine + [
                'name' => 'Seated Calf Raise',
                'primary_muscle' => MuscleGroup::Calves->value,
                'secondary_muscles' => [],
                'instructions' => 'Full stretch at the bottom, full contraction at the top.',
                'default_rest_seconds' => 90,
            ],
            $cable + [
                'name' => 'Lat Pulldown',
                'primary_muscle' => MuscleGroup::Lats->value,
                'secondary_muscles' => [MuscleGroup::Biceps->value, MuscleGroup::Back->value],
                'instructions' => 'Pull to the collarbone, control the return.',
                'default_rest_seconds' => 150,
            ],
            $cable + [
                'name' => 'Seated Cable Row',
                'primary_muscle' => MuscleGroup::Back->value,
                'secondary_muscles' => [MuscleGroup::Lats->value, MuscleGroup::Biceps->value],
                'instructions' => 'Row to the stomach without rocking.',
                'default_rest_seconds' => 150,
            ],
            $cable + [
                'name' => 'Triceps Pushdown',
                'primary_muscle' => MuscleGroup::Triceps->value,
                'secondary_muscles' => [],
                'instructions' => 'Elbows pinned, extend fully, control the return.',
                'default_rest_seconds' => 90,
            ],
            $cable + [
                'name' => 'Cable Face Pull',
                'primary_muscle' => MuscleGroup::Shoulders->value,
                'secondary_muscles' => [MuscleGroup::Traps->value, MuscleGroup::Back->value],
                'instructions' => 'Pull to the forehead, externally rotate at the end.',
                'default_rest_seconds' => 90,
            ],
            [
                'name' => 'Pull-Up',
                'primary_muscle' => MuscleGroup::Lats->value,
                'secondary_muscles' => [MuscleGroup::Biceps->value, MuscleGroup::Back->value],
                'equipment' => Equipment::Bodyweight->value,
                'instructions' => 'Full hang to chin over the bar, control the descent.',
                'loading_type' => LoadingType::Bodyweight->value,
                'is_bodyweight' => true,
                'default_weight_increment_kg' => 2.5,
                'default_rest_seconds' => 180,
            ],
            [
                'name' => 'Assisted Pull-Up',
                'primary_muscle' => MuscleGroup::Lats->value,
                'secondary_muscles' => [MuscleGroup::Biceps->value],
                'equipment' => Equipment::Machine->value,
                'instructions' => 'Progress by reducing assistance, never by adding weight.',
                'loading_type' => LoadingType::AssistedBodyweight->value,
                'is_bodyweight' => true,
                'default_weight_increment_kg' => 5.0,
                'default_rest_seconds' => 180,
            ],
            [
                'name' => 'Dip',
                'primary_muscle' => MuscleGroup::Chest->value,
                'secondary_muscles' => [MuscleGroup::Triceps->value, MuscleGroup::Shoulders->value],
                'equipment' => Equipment::Bodyweight->value,
                'instructions' => 'Lower to a comfortable stretch, press to lockout.',
                'loading_type' => LoadingType::Bodyweight->value,
                'is_bodyweight' => true,
                'default_weight_increment_kg' => 2.5,
                'default_rest_seconds' => 180,
            ],
            [
                'name' => 'Plank',
                'primary_muscle' => MuscleGroup::Core->value,
                'secondary_muscles' => [],
                'equipment' => Equipment::Bodyweight->value,
                'instructions' => 'Hold a straight line from head to heels.',
                'loading_type' => LoadingType::Time->value,
                'is_bodyweight' => true,
                'default_weight_increment_kg' => 2.5,
                'default_rest_seconds' => 60,
            ],
        ];
    }
}
