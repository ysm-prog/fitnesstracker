<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A program: a named, ordered list of prescriptions.
 *
 * What a template asks for is copied into a workout when the session starts
 * (Milestone 3). Editing a template afterwards changes what the *next* session
 * will ask for and never touches what a past session recorded.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workout_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
            $table->index(['user_id', 'archived_at']);
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement(
                'CREATE UNIQUE INDEX workout_templates_owner_name_unique
                 ON workout_templates (user_id, lower(name))'
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_templates');
    }
};
