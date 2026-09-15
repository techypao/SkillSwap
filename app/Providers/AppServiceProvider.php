<?php

namespace App\Providers;

use App\Models\SkillSession;
use App\Models\SwapRequest;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('partials.notification-bell', function (ViewContract $view): void {
            $user = auth()->user();

            $view->with([
                'unreadNotificationCount' => $user?->unreadNotifications()->count() ?? 0,
                'recentNotifications' => $user?->notifications()->limit(6)->get() ?? collect(),
            ]);
        });

        View::composer('partials.current-session-link', function (ViewContract $view): void {
            $user = auth()->user();
            $currentSkillSession = null;

            if ($user !== null) {
                $now = now();

                $currentSkillSession = SkillSession::query()
                    ->where('status', SkillSession::STATUS_CONFIRMED)
                    ->whereBetween('scheduled_at', [
                        $now->copy()->subMinutes(SkillSession::MAX_DURATION_MINUTES),
                        $now,
                    ])
                    ->whereHas('swapRequest', function (Builder $query) use ($user): void {
                        $query->where('status', SwapRequest::STATUS_ACCEPTED)
                            ->where(function (Builder $query) use ($user): void {
                                $query->where('sender_id', $user->id)
                                    ->orWhere('recipient_id', $user->id);
                            });
                    })
                    ->orderBy('scheduled_at')
                    ->orderBy('id')
                    ->get(['id', 'swap_request_id', 'scheduled_at', 'duration_minutes', 'meeting_type'])
                    ->first(fn (SkillSession $skillSession): bool => $skillSession->isInProgress());
            }

            $view->with('currentSkillSession', $currentSkillSession);
        });
    }
}
