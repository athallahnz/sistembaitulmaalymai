<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function markAllAsRead()
    {
        $user = Auth::user();

        if ($user) {
            // unreadNotifications is a DatabaseNotificationCollection; use its helper to mark as read
            $user->unreadNotifications->markAsRead();
        }

        return back();
    }

    public function markOneAsRead(string $id)
    {
        $user = Auth::user();

        if (! $user) {
            return back();
        }

        $notification = DatabaseNotification::where('id', $id)
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', get_class($user))
            ->firstOrFail();

        $notification->markAsRead();

        $url = data_get($notification->data, 'url');
        return $url ? redirect($url) : back();
    }
}
