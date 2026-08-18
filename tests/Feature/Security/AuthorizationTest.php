<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\FitnessProfile;
use App\Models\User;
use App\Policies\FitnessProfilePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * SEC-005 / SEC-007 — the IDOR suite.
 *
 * The requirement is "User A cannot access or modify User B's records". These
 * tests are written so that they would fail if ownership were ever taken from
 * the request instead of the session.
 */
final class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_policy_denies_another_users_profile(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $profile = FitnessProfile::factory()->for($owner)->create();

        $this->assertTrue(Gate::forUser($owner)->allows('view', $profile));
        $this->assertTrue(Gate::forUser($owner)->allows('update', $profile));

        $this->assertFalse(Gate::forUser($stranger)->allows('view', $profile));
        $this->assertFalse(Gate::forUser($stranger)->allows('update', $profile));
        $this->assertFalse(Gate::forUser($stranger)->allows('delete', $profile));
    }

    public function test_the_policy_is_discovered_for_the_model(): void
    {
        $this->assertInstanceOf(
            FitnessProfilePolicy::class,
            Gate::getPolicyFor(FitnessProfile::class),
        );
    }

    /** A reads only A's data, never B's, whoever asks. */
    public function test_each_user_only_ever_sees_their_own_profile(): void
    {
        $userA = User::factory()->create();
        FitnessProfile::factory()->for($userA)->create(['height_cm' => 170.0]);

        $userB = User::factory()->create();
        FitnessProfile::factory()->for($userB)->create(['height_cm' => 190.0]);

        $this->actingAs($userA)
            ->getJson('/api/v1/profile/fitness')
            ->assertOk()
            ->assertJsonPath('fitness_profile.height_cm', fn ($value) => (float) $value === 170.0);

        $this->actingAs($userB)
            ->getJson('/api/v1/profile/fitness')
            ->assertOk()
            ->assertJsonPath('fitness_profile.height_cm', fn ($value) => (float) $value === 190.0);
    }

    /** A bearer token issued to A must not read B's account. */
    public function test_a_token_reads_only_its_own_owners_account(): void
    {
        $userA = User::factory()->create(['name' => 'User A']);
        $userB = User::factory()->create(['name' => 'User B']);
        $token = $userA->createToken('Pixel 9')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/profile')
            ->assertOk()
            ->assertJsonPath('user.name', 'User A')
            ->assertJsonPath('user.id', $userA->id);

        $this->assertNotSame($userB->id, $userA->id);
    }

    /** A must not be able to delete B's account by naming it. */
    public function test_deletion_only_ever_removes_the_caller(): void
    {
        $userA = User::factory()->create(['password' => bcrypt('a-password')]);
        $userB = User::factory()->create();

        $this->actingAs($userA)
            ->deleteJson('/api/v1/account', [
                'current_password' => 'a-password',
                'user_id' => $userB->id,
                'id' => $userB->id,
            ])
            ->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $userA->id]);
        $this->assertDatabaseHas('users', ['id' => $userB->id]);
    }

    public function test_every_protected_route_refuses_an_anonymous_caller(): void
    {
        $routes = [
            ['getJson', '/api/v1/profile'],
            ['patchJson', '/api/v1/profile'],
            ['deleteJson', '/api/v1/account'],
            ['getJson', '/api/v1/profile/fitness'],
            ['putJson', '/api/v1/profile/fitness'],
            ['postJson', '/api/v1/auth/logout'],
            ['postJson', '/api/v1/auth/email/verification-notification'],
        ];

        foreach ($routes as [$method, $uri]) {
            $this->{$method}($uri)
                ->assertStatus(401)
                ->assertJsonPath('error_code', 'unauthenticated');
        }
    }
}
