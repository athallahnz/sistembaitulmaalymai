<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;

class HutangReminder extends Notification
{
    use Queueable;

    protected $hutang;

    public function __construct($hutang)
    {
        $this->hutang = $hutang;
    }

    public function via($notifiable)
    {
        return ['database']; // Simpan ke database
    }

    public function toArray($notifiable)
    {
        $user = User::find($this->hutang->user_id); // Ambil data user berdasarkan user_id

        return [
            'message' => 'Anda memiliki hutang sebesar Rp' . number_format($this->hutang->jumlah) .
                ' kepada: ' . ($user ? $user->name : 'Tidak Diketahui') .
                ' yang jatuh tempo pada ' . $this->hutang->tanggal_jatuh_tempo,
            'url' => url('/hutang/' . $this->hutang->id), // Link ke detail hutang
            'hutang_id' => $this->hutang->id,
        ];
    }
}

