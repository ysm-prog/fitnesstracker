<?php

declare(strict_types=1);

namespace Tests\Feature\Profile;

use App\Models\FitnessProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class AccountTest extends TestCase
{
    use RefreshDatabase;

    /** AUTH-007 */
    public function test_a_user_can_read_their_account(): void
    {
        $user = User::factory()->create(['name' => 'Sam Rivers']);

        $this->actingAs($user)
            ->getJson('/api/v1/profile')
            ->assertOk()
            ->assertJsonPath('user.name', 'Sam Rivers')
            ->assertJsonStructure(['user' => ['id', 'name', 'email'], 'fitness_profile' => ['id', 'training_level']]);
    }

    public function test_a_user_can_change_their_name_without_a_password(): void
    {
        $user = User::factory()->create(['name' => 'Sam Rivers']);

        $this->actingAs($user)
            ->patchJson('/api/v1/profile', ['name' => 'Samantha Rivers'])
            ->assertOk()
            ->assertJsonPath('user.name', 'Samantha Rivers');
    }

    public function test_changing_the_address_requires_the_current_password(): void
    {
        $user = User::factory()->create([
            'email' => 'sam@example.com',
            'password' => Hash::make('the-current-password'),
        ]);

        $this->actingAs($user)
            ->patchJson('/api/v1/profile', ['email' => 'new@example.com'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('current_password');

        $this->assertSame('sam@example.com', $user->fresh()->email);
    }

    public function test_changing_the_address_clears_verification(): void
    {
        $user = User::factory()->create([
            'email' => 'sam@example.com',
            'password' => Hash::make('the-current-password'),
        ]);
        $this->assertNotNull($user->email_verified_at);

        $this->actingAs($user)
            ->patchJson('/api/v1/profile', [
                'email' => 'new@example.com',
                'current_password' => 'the-current-password',
            ])
            ->assertOk()
            ->assertJsonPath('user.email_verified', false);

        $this->assertNull($user->fresh()->email_verified_at);
    }

    /** AUTH-008 */
    public function test_a_user_can_delete_their_account(): void
    {
        $user = User::factory()->create(['password' => Hash::make('the-current-password')]);
        FitnessProfile::factory()->for($user)->create();
        $user->createToken('Pixel 9');

        $this->actingAs($user)
            ->deleteJson('/api/v1/account', ['current_password' => 'the-current-password'])
            ->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('fitness_profiles', ['user_id' => $user->id]);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_deletion_requires_the_current_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('the-current-password')]);

        $this->actingAs($user)
            ->deleteJson('/api/v1/account', ['current_password' => 'guessing'])
            ->assertStatus(422);

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }
}
