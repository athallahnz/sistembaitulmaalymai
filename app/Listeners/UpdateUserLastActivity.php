<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Authenticated;
use Carbon\Carbon;
use Log;

class UpdateUserLastActivity
{
    /**
     * Handle the event.
     *
     * @param  \Illuminate\Auth\Events\Authenticated  $event
     * @return void
     */
    public function handle(Authenticated $event)
    {
        // Mendapatkan user yang baru saja login
        $user = $event->user;

        // Menambahkan log untuk memastikan event dipicu dengan benar
        \Log::info('User Logged In: ' . $user->name);

        // Perbarui last_activity_at dan last_login_at
        $updated = $user->update([
            'last_activity_at' => Carbon::now(),
            'is_active' => true, // ⬅ Tambahkan ini
        ]);

        // Log untuk memeriksa apakah update berhasil
        \Log::info('User Last Activity and Last Login Updated: ' . ($updated ? 'Success' : 'Failed'));

        // Jika update gagal, tampilkan alasan kenapa
        if (!$updated) {
            \Log::error('Failed to update user activity data.');
        }
    }
}

