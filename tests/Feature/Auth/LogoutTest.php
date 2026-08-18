<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LogoutTest extends TestCase
{
    use RefreshDatabase;

    /** AUTH-003 */
    public function test_a_signed_in_user_can_sign_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        $this->assertGuest('web');
    }

    public function test_signing_out_requires_authentication(): void
    {
        $this->postJson('/api/v1/auth/logout')
            ->assertStatus(401)
            ->assertJsonPath('error_code', 'unauthenticated');
    }
}
