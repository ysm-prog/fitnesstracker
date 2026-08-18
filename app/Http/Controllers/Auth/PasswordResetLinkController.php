<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;

final class PasswordResetLinkController extends Controller
{
    public function store(ForgotPasswordRequest $request): JsonResponse
    {
        Password::sendResetLink($request->safe()->only('email'));

        // Always the same answer, whether or not the address is registered.
        // Reporting "no such user" here would turn the form into a directory.
        return response()->json([
            'message' => 'If that address has an account, a reset link is on its way.',
        ]);
    }
}
