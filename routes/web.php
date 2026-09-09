<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DiscoverController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SkillSessionCompletionController;
use App\Http\Controllers\SkillSessionController;
use App\Http\Controllers\SwapChatController;
use App\Http\Controllers\SwapMessageController;
use App\Http\Controllers\SwapRequestController;
use App\Http\Controllers\UserDashboardController;
use App\Http\Middleware\EnsureOnboardingCompleted;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Guest Routes
Route::middleware('guest')->group(function () {

    Route::get('/register', [RegisterController::class, 'create'])
        ->name('register');

    Route::post('/register', [RegisterController::class, 'store'])
        ->name('register.store');

    Route::get('/login', [LoginController::class, 'create'])
        ->name('login');

    Route::post('/login', [LoginController::class, 'store'])
        ->name('login.store');
});

// Authenticated Routes
Route::middleware('auth')->group(function () {

    // User Dashboard
    Route::get('/dashboard', [UserDashboardController::class, 'index'])
        ->middleware(EnsureOnboardingCompleted::class)
        ->name('dashboard');

    // Discover
    Route::get('/discover', [DiscoverController::class, 'index'])
        ->middleware(EnsureOnboardingCompleted::class)
        ->name('discover.index');

    // Match Details
    Route::get('/matches/{user}', [MatchController::class, 'show'])
        ->middleware(EnsureOnboardingCompleted::class)
        ->name('matches.show');

    Route::post('/swap-requests', [SwapRequestController::class, 'store'])
        ->middleware(EnsureOnboardingCompleted::class)
        ->name('swap-requests.store');

    Route::patch('/swap-requests/{swapRequest}/accept', [SwapRequestController::class, 'accept'])
        ->middleware(EnsureOnboardingCompleted::class)
        ->name('swap-requests.accept');

    Route::patch('/swap-requests/{swapRequest}/reject', [SwapRequestController::class, 'reject'])
        ->middleware(EnsureOnboardingCompleted::class)
        ->name('swap-requests.reject');

    Route::get('/swap-requests/{swapRequest}/chat', [SwapChatController::class, 'show'])
        ->middleware(EnsureOnboardingCompleted::class)
        ->name('swap-requests.chat');

    Route::post('/swap-requests/{swapRequest}/messages', [SwapMessageController::class, 'store'])
        ->middleware(EnsureOnboardingCompleted::class)
        ->name('swap-requests.messages.store');

    Route::get('/swap-requests/{swapRequest}/schedule', [SkillSessionController::class, 'create'])
        ->middleware(EnsureOnboardingCompleted::class)
        ->name('skill-sessions.create');

    Route::post('/swap-requests/{swapRequest}/schedule', [SkillSessionController::class, 'store'])
        ->middleware(EnsureOnboardingCompleted::class)
        ->name('skill-sessions.store');

    Route::patch('/skill-sessions/{skillSession}/agree', [SkillSessionController::class, 'agree'])
        ->middleware(EnsureOnboardingCompleted::class)
        ->name('skill-sessions.agree');

    Route::patch('/skill-sessions/{skillSession}/decline', [SkillSessionController::class, 'decline'])
        ->middleware(EnsureOnboardingCompleted::class)
        ->name('skill-sessions.decline');

    Route::patch('/skill-sessions/{skillSession}/confirm-completion', SkillSessionCompletionController::class)
        ->middleware(EnsureOnboardingCompleted::class)
        ->name('skill-sessions.completion.store');

    Route::get('/skill-sessions/{skillSession}/review', [ReviewController::class, 'create'])
        ->middleware(EnsureOnboardingCompleted::class)
        ->name('reviews.create');

    Route::post('/skill-sessions/{skillSession}/review', [ReviewController::class, 'store'])
        ->middleware(EnsureOnboardingCompleted::class)
        ->name('reviews.store');

    // Admin Dashboard
    Route::get('/admin/dashboard', function () {
        return view('admin.dashboard');
    })
        ->middleware('admin')
        ->name('admin.dashboard');

    // Logout
    Route::post('/logout', [LoginController::class, 'destroy'])
        ->name('logout');

    Route::get('/onboarding', [OnboardingController::class, 'welcome'])
        ->name('onboarding.welcome');

    Route::get('/onboarding/profile', [OnboardingController::class, 'profile'])
        ->name('onboarding.profile');

    Route::post('/onboarding/profile', [OnboardingController::class, 'storeProfile'])
        ->name('onboarding.profile.store');

    Route::get('/onboarding/skills', [OnboardingController::class, 'skills'])
        ->name('onboarding.skills');

    Route::post('/onboarding/skills', [OnboardingController::class, 'storeSkills'])
        ->name('onboarding.skills.store');

    Route::get('/onboarding/availability', [OnboardingController::class, 'availability'])
        ->name('onboarding.availability');

    Route::post('/onboarding/availability', [OnboardingController::class, 'storeAvailability'])
        ->name('onboarding.availability.store');

});
