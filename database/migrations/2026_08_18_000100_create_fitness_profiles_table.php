<?php

use App\Enums\MeasurementUnit;
use App\Enums\PrimaryGoal;
use App\Enums\TrainingLevel;
use App\Enums\WeightUnit;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The user's fitness profile: one row per user.
 *
 * Body weights and heights are stored canonically in kilograms and
 * centimetres regardless of the display units the user prefers. Unit
 * preference is presentation, never storage — mixing the two is how a
 * training log quietly corrupts a year of bodyweight history.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fitness_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            $table->decimal('height_cm', 5, 1)->nullable();
            $table->decimal('current_body_weight_kg', 6, 2)->nullable();
            $table->decimal('target_body_weight_kg', 6, 2)->nullable();

            $table->string('training_level')->default(TrainingLevel::Beginner->value);
            $table->string('primary_goal')->default(PrimaryGoal::Maintenance->value);
            $table->string('weight_unit')->default(WeightUnit::Kilograms->value);
            $table->string('measurement_unit')->default(MeasurementUnit::Centimetres->value);

            $table->unsignedSmallInteger('preferred_session_minutes')->nullable();
            $table->unsignedTinyInteger('training_days_per_week')->nullable();
            $table->json('available_training_days')->nullable();

            // Placeholder only. Nutrition is documented future scope and is not
            // implemented in this build; the column exists so onboarding can
            // capture the preference without a later table rewrite.
            $table->string('dietary_preference')->nullable();

            $table->text('training_limitations')->nullable();

            $table->timestamps();
        });

        $this->addCheckConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('fitness_profiles');
    }

    /**
     * Range checks at the database boundary.
     *
     * Form Requests remain the authoritative validation layer; these exist so
     * that a future importer, console command, or hand-written SQL statement
     * cannot write a body weight of 4,000 kg. SQLite cannot add constraints
     * after table creation, so the test database relies on the application
     * layer alone — which is why the constraint suite requires PostgreSQL.
     */
    private function addCheckConstraints(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        $checks = [
            'fitness_profiles_height_cm_check' => 'height_cm IS NULL OR (height_cm >= 50 AND height_cm <= 300)',
            'fitness_profiles_current_weight_check' => 'current_body_weight_kg IS NULL OR (current_body_weight_kg >= 20 AND current_body_weight_kg <= 500)',
            'fitness_profiles_target_weight_check' => 'target_body_weight_kg IS NULL OR (target_body_weight_kg >= 20 AND target_body_weight_kg <= 500)',
            'fitness_profiles_session_minutes_check' => 'preferred_session_minutes IS NULL OR (preferred_session_minutes >= 10 AND preferred_session_minutes <= 240)',
            'fitness_profiles_days_per_week_check' => 'training_days_per_week IS NULL OR (training_days_per_week >= 1 AND training_days_per_week <= 7)',
            'fitness_profiles_training_level_check' => sprintf("training_level IN ('%s')", implode("','", TrainingLevel::values())),
            'fitness_profiles_primary_goal_check' => sprintf("primary_goal IN ('%s')", implode("','", PrimaryGoal::values())),
            'fitness_profiles_weight_unit_check' => sprintf("weight_unit IN ('%s')", implode("','", WeightUnit::values())),
            'fitness_profiles_measurement_unit_check' => sprintf("measurement_unit IN ('%s')", implode("','", MeasurementUnit::values())),
        ];

        foreach ($checks as $name => $expression) {
            DB::statement("ALTER TABLE fitness_profiles ADD CONSTRAINT {$name} CHECK ({$expression})");
        }
    }
};
