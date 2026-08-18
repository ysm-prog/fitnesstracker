<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

final class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /** AUTH-001 */
    public function test_a_visitor_can_register(): void
    {
        Event::fake([Registered::class]);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Sam Rivers',
            'email' => 'Sam@Example.com',
            'password' => 'correct-horse-battery-staple',
            'password_confirmation' => 'correct-horse-battery-staple',
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.name', 'Sam Rivers')
            ->assertJsonPath('user.email', 'sam@example.com')
            ->assertJsonPath('user.email_verified', false)
            ->assertJsonMissingPath('user.password');

        $this->assertDatabaseHas('users', ['email' => 'sam@example.com']);
        $this->assertAuthenticated('web');
        Event::assertDispatched(Registered::class);
    }

    /** PROFILE-001 — a user always has a profile row from the moment they exist. */
    public function test_registration_creates_the_fitness_profile(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Sam Rivers',
            'email' => 'sam@example.com',
            'password' => 'correct-horse-battery-staple',
            'password_confirmation' => 'correct-horse-battery-staple',
        ])->assertCreated();

        $user = User::firstWhere('email', 'sam@example.com');

        $this->assertDatabaseHas('fitness_profiles', ['user_id' => $user->id]);
    }

    public function test_registration_rejects_a_duplicate_address(): void
    {
        User::factory()->create(['email' => 'sam@example.com']);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Someone Else',
            'email' => 'sam@example.com',
            'password' => 'correct-horse-battery-staple',
            'password_confirmation' => 'correct-horse-battery-staple',
        ])
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'validation_failed')
            ->assertJsonStructure(['error_code', 'message', 'correlation_id', 'errors' => ['email']]);
    }

    public function test_registration_rejects_an_unconfirmed_password(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Sam Rivers',
            'email' => 'sam@example.com',
            'password' => 'correct-horse-battery-staple',
            'password_confirmation' => 'something-else-entirely',
        ])->assertStatus(422)->assertJsonValidationErrors('password');

        $this->assertDatabaseCount('users', 0);
    }
}
