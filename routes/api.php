<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ExerciseController;
use App\Http\Controllers\FitnessProfileController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\TemplateExerciseController;
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

    // Exercise library. System exercises are readable by everyone and writable
    // by nobody; the policy, not the route, enforces that.
    Route::get('exercises', [ExerciseController::class, 'index'])->name('exercises.index');
    Route::post('exercises', [ExerciseController::class, 'store'])->name('exercises.store');
    Route::get('exercises/{exercise}', [ExerciseController::class, 'show'])->name('exercises.show');
    Route::patch('exercises/{exercise}', [ExerciseController::class, 'update'])->name('exercises.update');
    Route::delete('exercises/{exercise}', [ExerciseController::class, 'destroy'])->name('exercises.destroy');
    Route::post('exercises/{exercise}/restore', [ExerciseController::class, 'restore'])->name('exercises.restore');

    // Programs, and the prescriptions inside them.
    Route::get('programs', [ProgramController::class, 'index'])->name('programs.index');
    Route::post('programs', [ProgramController::class, 'store'])->name('programs.store');
    Route::get('programs/{program}', [ProgramController::class, 'show'])->name('programs.show');
    Route::patch('programs/{program}', [ProgramController::class, 'update'])->name('programs.update');
    Route::delete('programs/{program}', [ProgramController::class, 'destroy'])->name('programs.destroy');
    Route::post('programs/{program}/restore', [ProgramController::class, 'restore'])->name('programs.restore');
    Route::post('programs/{program}/duplicate', [ProgramController::class, 'duplicate'])->name('programs.duplicate');

    Route::post('programs/{program}/exercises', [TemplateExerciseController::class, 'store'])
        ->name('programs.exercises.store');
    Route::put('programs/{program}/exercises/reorder', [TemplateExerciseController::class, 'reorder'])
        ->name('programs.exercises.reorder');
    Route::patch('programs/{program}/exercises/{templateExercise}', [TemplateExerciseController::class, 'update'])
        ->name('programs.exercises.update');
    Route::delete('programs/{program}/exercises/{templateExercise}', [TemplateExerciseController::class, 'destroy'])
        ->name('programs.exercises.destroy');
});
