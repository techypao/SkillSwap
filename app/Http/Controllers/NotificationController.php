<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()->notifications()->simplePaginate(20);

        return view('notifications.index', compact('notifications'));
    }

    /**
     * Mark one of the current user's notifications as read and follow it.
     */
    public function read(Request $request, string $notification): RedirectResponse
    {
        $userNotification = $request->user()->notifications()->findOrFail($notification);
        $userNotification->markAsRead();

        return redirect($this->safeDestination($userNotification->data['url'] ?? null));
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back(fallback: route('notifications.index'));
    }

    /**
     * Only follow relative in-app paths so stored data can never cause an open redirect.
     */
    private function safeDestination(mixed $url): string
    {
        if (is_string($url) && str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return $url;
        }

        return route('dashboard');
    }
}
