<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TemplateExercise;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * A prescription is owned by whoever owns the program it belongs to. It has no
 * `user_id` of its own, so ownership is resolved through the template rather
 * than duplicated onto every row.
 */
final class TemplateExercisePolicy
{
    public function view(User $user, TemplateExercise $templateExercise): Response
    {
        return $this->ownsOrNotFound($user, $templateExercise);
    }

    public function update(User $user, TemplateExercise $templateExercise): Response
    {
        return $this->ownsOrNotFound($user, $templateExercise);
    }

    public function delete(User $user, TemplateExercise $templateExercise): Response
    {
        return $this->ownsOrNotFound($user, $templateExercise);
    }

    private function ownsOrNotFound(User $user, TemplateExercise $templateExercise): Response
    {
        return $user->getKey() === $templateExercise->workoutTemplate->user_id
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
