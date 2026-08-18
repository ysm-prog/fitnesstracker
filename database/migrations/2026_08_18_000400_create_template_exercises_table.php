<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One prescription within a program: what to do, how much of it, and how hard.
 *
 * `position` is dense and 1-based within a template. Reordering rewrites every
 * position in one transaction rather than swapping pairs, so the sequence is
 * never observed with a gap or a duplicate.
 *
 * The exercise is restricted on delete, not cascaded: removing an exercise that
 * a program prescribes would silently change the program. Exercises are
 * archived instead.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_exercises', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workout_template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exercise_id')->constrained()->restrictOnDelete();

            $table->unsignedSmallInteger('position');

            $table->unsignedSmallInteger('target_sets');
            $table->unsignedSmallInteger('min_reps');
            $table->unsignedSmallInteger('max_reps');
            $table->unsignedTinyInteger('target_rir')->nullable();
            $table->unsignedSmallInteger('rest_seconds');

            // Optional work is prescribed but excluded from adherence, so a
            // skipped finisher never reads as a missed session.
            $table->boolean('is_optional')->default(false);
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['workout_template_id', 'position']);
            $table->index('exercise_id');
        });

        $this->addCheckConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('template_exercises');
    }

    private function addCheckConstraints(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        $checks = [
            'template_exercises_sets_check' => 'target_sets >= 1 AND target_sets <= 20',
            'template_exercises_min_reps_check' => 'min_reps >= 1 AND min_reps <= 100',
            'template_exercises_max_reps_check' => 'max_reps >= 1 AND max_reps <= 100',
            'template_exercises_rep_range_check' => 'min_reps <= max_reps',
            'template_exercises_rir_check' => 'target_rir IS NULL OR (target_rir >= 0 AND target_rir <= 5)',
            'template_exercises_rest_check' => 'rest_seconds >= 0 AND rest_seconds <= 900',
            'template_exercises_position_check' => 'position >= 1',
        ];

        foreach ($checks as $name => $expression) {
            DB::statement("ALTER TABLE template_exercises ADD CONSTRAINT {$name} CHECK ({$expression})");
        }
    }
};
