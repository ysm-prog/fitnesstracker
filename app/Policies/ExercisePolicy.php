<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Exercise;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Two different refusals, deliberately distinguished.
 *
 * A system exercise is not a secret: everyone can see it, nobody may edit it,
 * and saying so plainly is more useful than pretending it does not exist.
 * Another user's private exercise *is* a secret, and a 403 there would confirm
 * that the identifier belongs to something — so it is denied as not found.
 */
final class ExercisePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Exercise $exercise): Response
    {
        return $exercise->isSystem() || $this->owns($user, $exercise)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Exercise $exercise): Response
    {
        return $this->authorizeWrite($user, $exercise);
    }

    public function delete(User $user, Exercise $exercise): Response
    {
        return $this->authorizeWrite($user, $exercise);
    }

    public function restore(User $user, Exercise $exercise): Response
    {
        return $this->authorizeWrite($user, $exercise);
    }

    private function authorizeWrite(User $user, Exercise $exercise): Response
    {
        if ($exercise->isSystem()) {
            return Response::deny('System exercises cannot be changed. Create your own variation instead.');
        }

        return $this->owns($user, $exercise)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    private function owns(User $user, Exercise $exercise): bool
    {
        return $exercise->user_id !== null && $exercise->user_id === $user->getKey();
    }
}
