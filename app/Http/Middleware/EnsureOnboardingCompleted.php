<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingCompleted
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Just in case the route is accessed without authentication.
        if (!$user) {
            return redirect()->route('login');
        }

        // Admins do not go through user onboarding.
        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        // Normal users must complete onboarding first.
        if (!$user->onboarding_completed) {
            return redirect()->route('onboarding.welcome');
        }

        return $next($request);
    }
}