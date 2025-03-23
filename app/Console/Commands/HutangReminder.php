<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Hutang;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;
use App\Notifications\HutangJatuhTempo;

class HutangReminder extends Command
{
    protected $signature = 'hutang:reminder';
    protected $description = 'Kirim notifikasi pengingat hutang jatuh tempo';

    public function handle()
    {
        $hutangs = Hutang::where('status', 'belum_lunas')
            ->whereDate('tanggal_jatuh_tempo', '<=', Carbon::now()->addDays(3)) // 3 hari sebelum jatuh tempo
            ->get();

        foreach ($hutangs as $hutang) {
            $user = User::find($hutang->user_id);
            if ($user) {
                Notification::send($user, new HutangJatuhTempo($hutang));
            }
        }

        $this->info('Notifikasi hutang jatuh tempo telah dikirim.');
    }
}
