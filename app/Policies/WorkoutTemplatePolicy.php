<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\WorkoutTemplate;
use Illuminate\Auth\Access\Response;

/**
 * A program belongs to exactly one person. Refusing someone else's with a 403
 * would confirm that the identifier exists, so every refusal here is a 404.
 */
final class WorkoutTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, WorkoutTemplate $template): Response
    {
        return $this->ownsOrNotFound($user, $template);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, WorkoutTemplate $template): Response
    {
        return $this->ownsOrNotFound($user, $template);
    }

    public function delete(User $user, WorkoutTemplate $template): Response
    {
        return $this->ownsOrNotFound($user, $template);
    }

    public function restore(User $user, WorkoutTemplate $template): Response
    {
        return $this->ownsOrNotFound($user, $template);
    }

    private function ownsOrNotFound(User $user, WorkoutTemplate $template): Response
    {
        return $user->getKey() === $template->user_id
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
