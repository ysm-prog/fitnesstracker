<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

final class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    /** AUTH-005 */
    public function test_a_reset_link_is_sent_to_a_known_address(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'sam@example.com']);

        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'sam@example.com'])
            ->assertOk();

        Notification::assertSentTo($user, ResetPassword::class);
    }

    /**
     * AUTH-005 — the response must not reveal whether the address exists.
     */
    public function test_an_unknown_address_gets_the_same_response(): void
    {
        Notification::fake();
        User::factory()->create(['email' => 'sam@example.com']);

        $known = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'sam@example.com']);
        $unknown = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'nobody@example.com']);

        $this->assertSame($known->status(), $unknown->status());
        $this->assertSame($known->json('message'), $unknown->json('message'));
        Notification::assertCount(1);
    }

    /** AUTH-006 */
    public function test_a_password_can_be_reset_with_a_valid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'sam@example.com',
            'password' => Hash::make('the-old-password'),
        ]);
        $token = Password::createToken($user);

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'sam@example.com',
            'password' => 'a-brand-new-password',
            'password_confirmation' => 'a-brand-new-password',
        ])->assertOk();

        $this->assertTrue(Hash::check('a-brand-new-password', $user->fresh()->password));
    }

    public function test_an_invalid_token_is_rejected(): void
    {
        User::factory()->create([
            'email' => 'sam@example.com',
            'password' => Hash::make('the-old-password'),
        ]);

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'not-a-real-token',
            'email' => 'sam@example.com',
            'password' => 'a-brand-new-password',
            'password_confirmation' => 'a-brand-new-password',
        ])->assertStatus(422)->assertJsonPath('error_code', 'validation_failed');
    }

    /** SEC-009 — a reset invalidates every token issued under the old password. */
    public function test_resetting_revokes_existing_tokens(): void
    {
        $user = User::factory()->create([
            'email' => 'sam@example.com',
            'password' => Hash::make('the-old-password'),
        ]);
        $user->createToken('Pixel 9');
        $token = Password::createToken($user);

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'sam@example.com',
            'password' => 'a-brand-new-password',
            'password_confirmation' => 'a-brand-new-password',
        ])->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
