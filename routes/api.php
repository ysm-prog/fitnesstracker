<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\FitnessProfileController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
 * API v1. Mounted at /api/v1 by bootstrap/app.php.
 *
 * Nothing here accepts an ownership identifier from the client: every route
 * below derives the owner from the authenticated session.
 */

Route::prefix('auth')->group(function (): void {
    Route::post('register', [RegisteredUserController::class, 'store'])
        ->middleware(['guest', 'throttle:auth'])
        ->name('auth.register');

    Route::post('login', [AuthenticatedSessionController::class, 'store'])
        ->middleware(['guest', 'throttle:auth'])
        ->name('auth.login');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware(['guest', 'throttle:password-reset'])
        ->name('auth.password.email');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->middleware(['guest', 'throttle:password-reset'])
        ->name('auth.password.reset');

    Route::get('email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:auth'])
        ->name('verification.verify');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('auth.logout');

        Route::post('email/verification-notification', [EmailVerificationController::class, 'send'])
            ->middleware('throttle:auth')
            ->name('verification.send');
    });
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('account', [ProfileController::class, 'destroy'])->name('account.destroy');

    Route::get('profile/fitness', [FitnessProfileController::class, 'show'])->name('profile.fitness.show');
    Route::put('profile/fitness', [FitnessProfileController::class, 'update'])->name('profile.fitness.update');
});
