<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Email verification is supported but not enforced: the product requirement
 * calls it optional, so no route is gated on `verified`.
 *
 * `user_id` appears in no fillable list anywhere in this application.
 * Ownership is always taken from the authenticated session.
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmailContract
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** @return HasOne<FitnessProfile, $this> */
    public function fitnessProfile(): HasOne
    {
        return $this->hasOne(FitnessProfile::class);
    }

    /**
     * The profile always exists from the caller's point of view; it is created
     * with defaults on first read so onboarding never has to special-case a
     * missing row.
     */
    public function fitnessProfileOrNew(): FitnessProfile
    {
        return $this->fitnessProfile()->firstOrCreate([]);
    }
}
