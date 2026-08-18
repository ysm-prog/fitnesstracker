<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Profile\DeleteAccountRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\FitnessProfileResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => new UserResource($user),
            'fitness_profile' => new FitnessProfileResource($user->fitnessProfileOrNew()),
        ]);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $attributes = $request->safe()->only(['name', 'email', 'password']);

        // Changing the address un-verifies it: the new address has not proved
        // anything yet, and leaving the old timestamp would claim otherwise.
        if (isset($attributes['email']) && $attributes['email'] !== $user->email) {
            $attributes['email_verified_at'] = null;
        }

        $user->forceFill($attributes)->save();

        return response()->json(['user' => new UserResource($user->fresh())]);
    }

    public function destroy(DeleteAccountRequest $request): JsonResponse
    {
        $user = $request->user();

        // Sign out first, and deliberately so. Logging out cycles the remember
        // token, which saves the user model — and a model whose `exists` flag
        // has just been cleared by delete() saves as an INSERT, quietly
        // resurrecting the account that was supposed to be gone.
        Auth::guard('web')->logout();

        // The default guard is Sanctum, which holds the resolved user for the
        // rest of the process. Clearing the session alone would leave this
        // request still believing it is signed in.
        Auth::forgetGuards();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        DB::transaction(function () use ($user): void {
            $user->tokens()->delete();
            // Owned rows cascade from the foreign keys; the profile goes with
            // the user rather than being orphaned.
            $user->delete();
        });

        return response()->json(['message' => 'Account deleted.']);
    }
}
