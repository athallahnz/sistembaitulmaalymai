<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Piutang;

class HutangJatuhTempo extends Notification
{
    use Queueable;

    protected $piutang;

    public function __construct(Piutang $piutang)
    {
        $this->piutang = $piutang;
    }

    public function via($notifiable)
    {
        return ['database']; // Bisa ditambah 'sms', 'whatsapp' jika ada API
    }

    public function toArray($notifiable)
    {
        return [
            'message' => 'Hutang sebesar Rp' . number_format($this->piutang->jumlah, 2) . ' jatuh tempo pada ' . $this->piutang->tanggal_jatuh_tempo,
            'url' => url('/hutang'),
            'piutang_id' => $this->piutang->id, // Simpan piutang_id
        ];
    }

}

