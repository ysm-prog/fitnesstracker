<?php

declare(strict_types=1);

namespace Tests\Feature\Exercise;

use App\Enums\Equipment;
use App\Enums\MuscleGroup;
use App\Models\Exercise;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ExerciseLibraryTest extends TestCase
{
    use RefreshDatabase;

    /** EX-001 — the library is the system set plus the caller's own. */
    public function test_the_library_shows_system_and_own_exercises(): void
    {
        $user = User::factory()->create();
        $system = Exercise::factory()->system()->create(['name' => 'Barbell Back Squat']);
        $mine = Exercise::factory()->for($user)->create(['name' => 'My Squat Variation']);
        $theirs = Exercise::factory()->create(['name' => 'Someone Elses Movement']);

        $response = $this->actingAs($user)->getJson('/api/v1/exercises')->assertOk();

        $names = array_column($response->json('exercises'), 'name');

        $this->assertContains($system->name, $names);
        $this->assertContains($mine->name, $names);
        $this->assertNotContains($theirs->name, $names);
    }

    public function test_archived_exercises_are_hidden_unless_asked_for(): void
    {
        $user = User::factory()->create();
        Exercise::factory()->for($user)->create(['name' => 'Current Movement']);
        Exercise::factory()->for($user)->archived()->create(['name' => 'Retired Movement']);

        $active = $this->actingAs($user)->getJson('/api/v1/exercises')->assertOk();
        $this->assertSame(['Current Movement'], array_column($active->json('exercises'), 'name'));

        $all = $this->actingAs($user)->getJson('/api/v1/exercises?include_archived=1')->assertOk();
        $this->assertCount(2, $all->json('exercises'));
    }

    public function test_the_library_can_be_searched_and_filtered(): void
    {
        $user = User::factory()->create();
        Exercise::factory()->for($user)->create([
            'name' => 'Barbell Bench Press',
            'primary_muscle' => MuscleGroup::Chest,
            'equipment' => Equipment::Barbell,
        ]);
        Exercise::factory()->for($user)->create([
            'name' => 'Leg Curl',
            'primary_muscle' => MuscleGroup::Hamstrings,
            'equipment' => Equipment::Machine,
        ]);

        $bySearch = $this->actingAs($user)->getJson('/api/v1/exercises?q=bench')->assertOk();
        $this->assertSame(['Barbell Bench Press'], array_column($bySearch->json('exercises'), 'name'));

        $byMuscle = $this->actingAs($user)->getJson('/api/v1/exercises?primary_muscle=hamstrings')->assertOk();
        $this->assertSame(['Leg Curl'], array_column($byMuscle->json('exercises'), 'name'));

        $byEquipment = $this->actingAs($user)->getJson('/api/v1/exercises?equipment=barbell')->assertOk();
        $this->assertSame(['Barbell Bench Press'], array_column($byEquipment->json('exercises'), 'name'));
    }

    /** A search term is data, not syntax: wildcards must not widen the match. */
    public function test_search_wildcards_are_treated_as_literal_text(): void
    {
        $user = User::factory()->create();
        Exercise::factory()->for($user)->create(['name' => 'Barbell Bench Press']);

        $response = $this->actingAs($user)->getJson('/api/v1/exercises?q=%25')->assertOk();

        $this->assertSame([], $response->json('exercises'));
    }

    public function test_the_library_is_paginated(): void
    {
        $user = User::factory()->create();
        Exercise::factory()->for($user)->count(5)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/exercises?per_page=2')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 5)
            ->assertJsonPath('meta.last_page', 3);

        $this->assertCount(2, $response->json('exercises'));
    }

    public function test_the_library_requires_authentication(): void
    {
        $this->getJson('/api/v1/exercises')
            ->assertStatus(401)
            ->assertJsonPath('error_code', 'unauthenticated');
    }
}
