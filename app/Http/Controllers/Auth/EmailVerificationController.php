<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Verification is offered, not enforced: no route in this application is gated
 * on `verified`, because the product requirement calls it optional.
 */
final class EmailVerificationController extends Controller
{
    public function send(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'That address is already verified.']);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification email sent.']);
    }

    public function verify(Request $request, string $id, string $hash): JsonResponse
    {
        $user = User::find($id);

        // The signature proves the link is ours; the hash proves it belongs to
        // this address. Both must hold, and a mismatch reveals nothing.
        if ($user === null || ! hash_equals($hash, sha1((string) $user->getEmailForVerification()))) {
            throw new NotFoundHttpException;
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        return response()->json(['message' => 'Email verified.']);
    }
}
