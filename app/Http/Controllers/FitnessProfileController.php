<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Profile\UpdateFitnessProfileRequest;
use App\Http\Resources\FitnessProfileResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class FitnessProfileController extends Controller
{
    /**
     * The profile is reached through the authenticated user, never by an
     * identifier from the request, so there is no identifier to tamper with.
     * The policy check is belt and braces, and it is what the IDOR tests pin.
     */
    public function show(Request $request): JsonResponse
    {
        $profile = $request->user()->fitnessProfileOrNew();

        $this->authorize('view', $profile);

        return response()->json(['fitness_profile' => new FitnessProfileResource($profile)]);
    }

    public function update(UpdateFitnessProfileRequest $request): JsonResponse
    {
        $profile = $request->user()->fitnessProfileOrNew();

        $this->authorize('update', $profile);

        $profile->fill($request->safe()->all())->save();

        return response()->json(['fitness_profile' => new FitnessProfileResource($profile->fresh())]);
    }
}
