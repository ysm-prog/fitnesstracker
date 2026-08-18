<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class LoginTest extends TestCase
{
    use RefreshDatabase;

    /** AUTH-002 */
    public function test_a_user_can_sign_in(): void
    {
        $user = User::factory()->create([
            'email' => 'sam@example.com',
            'password' => Hash::make('correct-horse-battery-staple'),
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'sam@example.com',
            'password' => 'correct-horse-battery-staple',
        ])
            ->assertOk()
            ->assertJsonPath('user.email', 'sam@example.com');

        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_sign_in_is_case_insensitive_on_the_address(): void
    {
        User::factory()->create([
            'email' => 'sam@example.com',
            'password' => Hash::make('correct-horse-battery-staple'),
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => '  SAM@Example.com ',
            'password' => 'correct-horse-battery-staple',
        ])->assertOk();
    }

    public function test_a_wrong_password_is_rejected(): void
    {
        User::factory()->create([
            'email' => 'sam@example.com',
            'password' => Hash::make('correct-horse-battery-staple'),
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'sam@example.com',
            'password' => 'not-the-password',
        ])->assertStatus(422)->assertJsonPath('error_code', 'validation_failed');

        $this->assertGuest('web');
    }

    /**
     * SEC-012 — an unknown address and a wrong password must be
     * indistinguishable, or the endpoint becomes a user directory.
     */
    public function test_an_unknown_address_gives_the_same_answer_as_a_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'sam@example.com',
            'password' => Hash::make('correct-horse-battery-staple'),
        ]);

        $wrongPassword = $this->postJson('/api/v1/auth/login', [
            'email' => 'sam@example.com',
            'password' => 'not-the-password',
        ]);

        $unknownUser = $this->postJson('/api/v1/auth/login', [
            'email' => 'nobody@example.com',
            'password' => 'not-the-password',
        ]);

        $this->assertSame($wrongPassword->status(), $unknownUser->status());
        $this->assertSame(
            $wrongPassword->json('errors.email'),
            $unknownUser->json('errors.email'),
        );
    }

    /** A native client asks for a bearer token by naming its device. */
    public function test_a_device_name_yields_a_token(): void
    {
        User::factory()->create([
            'email' => 'sam@example.com',
            'password' => Hash::make('correct-horse-battery-staple'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'sam@example.com',
            'password' => 'correct-horse-battery-staple',
            'device_name' => 'Pixel 9',
        ])->assertOk();

        $this->assertNotEmpty($response->json('token'));
        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'Pixel 9']);
    }

    /** SEC-010 */
    public function test_repeated_failures_are_throttled(): void
    {
        User::factory()->create([
            'email' => 'sam@example.com',
            'password' => Hash::make('correct-horse-battery-staple'),
        ]);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'sam@example.com',
                'password' => 'wrong',
            ]);
        }

        $this->postJson('/api/v1/auth/login', [
            'email' => 'sam@example.com',
            'password' => 'wrong',
        ])
            ->assertStatus(429)
            ->assertJsonPath('error_code', 'too_many_requests');
    }
}
