<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

final class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function signedUrlFor(User $user, ?string $email = null): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->getKey(), 'hash' => sha1($email ?? $user->getEmailForVerification())],
        );
    }

    /** AUTH-004 */
    public function test_a_signed_link_verifies_the_address(): void
    {
        Event::fake([Verified::class]);
        $user = User::factory()->unverified()->create();

        $this->getJson($this->signedUrlFor($user))->assertOk();

        $this->assertNotNull($user->fresh()->email_verified_at);
        Event::assertDispatched(Verified::class);
    }

    public function test_an_unsigned_link_is_refused(): void
    {
        $user = User::factory()->unverified()->create();

        $this->getJson("/api/v1/auth/email/verify/{$user->getKey()}/".sha1($user->email))
            ->assertStatus(403);

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_a_hash_for_a_different_address_is_refused(): void
    {
        $user = User::factory()->unverified()->create();

        $this->getJson($this->signedUrlFor($user, 'someone-else@example.com'))
            ->assertStatus(404)
            ->assertJsonPath('error_code', 'not_found');

        $this->assertNull($user->fresh()->email_verified_at);
    }

    /**
     * Verification is optional by requirement: an unverified user still has
     * full access to their own data.
     */
    public function test_an_unverified_user_can_still_use_the_api(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->getJson('/api/v1/profile')->assertOk();
    }
}
