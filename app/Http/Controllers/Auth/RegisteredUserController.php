<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class RegisteredUserController extends Controller
{
    public function store(RegisterRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request): User {
            $user = User::create($request->safe()->only(['name', 'email', 'password']));

            // Every user has a profile from the moment they exist, so onboarding
            // and the coaching engine never meet a missing row.
            $user->fitnessProfile()->create([]);

            return $user;
        });

        event(new Registered($user));

        // A cookie client gets a session; a token client has none, and must
        // not crash the endpoint by lacking one.
        if ($request->hasSession()) {
            Auth::guard('web')->login($user);
            $request->session()->regenerate();
        }

        return response()->json(['user' => new UserResource($user)], 201);
    }
}
