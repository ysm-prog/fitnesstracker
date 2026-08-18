<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\FitnessProfile;
use App\Models\User;

final class FitnessProfilePolicy
{
    public function view(User $user, FitnessProfile $profile): bool
    {
        return $this->owns($user, $profile);
    }

    public function update(User $user, FitnessProfile $profile): bool
    {
        return $this->owns($user, $profile);
    }

    public function delete(User $user, FitnessProfile $profile): bool
    {
        return $this->owns($user, $profile);
    }

    private function owns(User $user, FitnessProfile $profile): bool
    {
        return $user->getKey() === $profile->user_id;
    }
}
