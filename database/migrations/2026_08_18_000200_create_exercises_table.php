<?php

use App\Enums\Equipment;
use App\Enums\LoadingType;
use App\Enums\MuscleGroup;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The exercise library.
 *
 * A null `user_id` marks a system exercise: shared, and immutable for ordinary
 * users. Anything else belongs to the user who created it.
 *
 * Exercises are archived rather than deleted once anything references them.
 * A workout performed two years ago must still be able to say what it was.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercises', function (Blueprint $table): void {
            $table->id();

            // Null means a system exercise. Ownership is never taken from a
            // request payload; it is set from the authenticated session.
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('primary_muscle');
            $table->json('secondary_muscles')->nullable();
            $table->string('equipment');
            $table->text('instructions')->nullable();

            $table->string('loading_type')->default(LoadingType::ExternalWeight->value);

            // The only step the coach may ever add. Kilograms, like every other
            // weight in this schema.
            $table->decimal('default_weight_increment_kg', 5, 2)->default(2.5);

            $table->boolean('is_unilateral')->default(false);
            $table->boolean('is_bodyweight')->default(false);
            $table->unsignedSmallInteger('default_rest_seconds')->default(120);

            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'archived_at']);
            $table->index('primary_muscle');
        });

        $this->addDatabaseConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('exercises');
    }

    /**
     * PostgreSQL only, for the reasons in docs/database.md: SQLite cannot add
     * constraints after table creation, and treats NULLs in a unique index as
     * distinct with no way to say otherwise — which would let the library hold
     * two system exercises called "Back Squat".
     */
    private function addDatabaseConstraints(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(
            'CREATE UNIQUE INDEX exercises_owner_name_unique
             ON exercises (user_id, lower(name)) NULLS NOT DISTINCT'
        );

        $checks = [
            'exercises_loading_type_check' => sprintf("loading_type IN ('%s')", implode("','", LoadingType::values())),
            'exercises_primary_muscle_check' => sprintf("primary_muscle IN ('%s')", implode("','", MuscleGroup::values())),
            'exercises_equipment_check' => sprintf("equipment IN ('%s')", implode("','", Equipment::values())),
            'exercises_increment_check' => 'default_weight_increment_kg > 0 AND default_weight_increment_kg <= 50',
            'exercises_rest_check' => 'default_rest_seconds >= 0 AND default_rest_seconds <= 900',
        ];

        foreach ($checks as $name => $expression) {
            DB::statement("ALTER TABLE exercises ADD CONSTRAINT {$name} CHECK ({$expression})");
        }
    }
};
