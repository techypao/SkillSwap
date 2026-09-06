<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OnboardingController;
use App\Http\Middleware\EnsureOnboardingCompleted;


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
    Route::get('/dashboard', function () {
    return view('dashboard');
})
    ->middleware([
        'auth',
        EnsureOnboardingCompleted::class,
    ])
    ->name('dashboard');


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