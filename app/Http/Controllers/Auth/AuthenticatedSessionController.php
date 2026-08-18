<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

final class AuthenticatedSessionController extends Controller
{
    public function store(LoginRequest $request): JsonResponse
    {
        $credentials = $request->safe()->only(['email', 'password']);

        // Explicitly the web guard: the default guard is Sanctum, which
        // verifies an existing identity and has no credential check of its own.
        if (! Auth::guard('web')->attempt($credentials, $request->boolean('remember'))) {
            // One message for both a wrong password and an unknown address, so
            // the endpoint cannot be used to enumerate registered users.
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        $user = Auth::guard('web')->user();

        $payload = ['user' => new UserResource($user)];

        if ($deviceName = $request->string('device_name')->trim()->value()) {
            $payload['token'] = $user->createToken($deviceName)->plainTextToken;
        }

        return response()->json($payload);
    }

    public function destroy(Request $request): JsonResponse
    {
        // A token-authenticated client logs out by revoking that token; a
        // cookie client tears down the session. Handle whichever arrived.
        $token = $request->user()?->currentAccessToken();

        if ($token !== null && method_exists($token, 'delete')) {
            $token->delete();
        }

        Auth::guard('web')->logout();

        // The default guard is Sanctum, which holds the resolved user for the
        // rest of the process. Clearing the session alone would leave this
        // request still believing it is signed in.
        Auth::forgetGuards();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json(['message' => 'Signed out.']);
    }
}
