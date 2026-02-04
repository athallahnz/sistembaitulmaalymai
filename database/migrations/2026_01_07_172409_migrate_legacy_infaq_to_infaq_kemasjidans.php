<?php

// database/migrations/xxxx_xx_xx_xxxxxx_migrate_legacy_infaq_to_infaq_kemasjidans.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('infaq_kemasjidan_legacy') || !Schema::hasTable('infaq_kemasjidans')) {
            return;
        }

        $bulanMap = [
            1 => 'januari',
            2 => 'februari',
            3 => 'maret',
            4 => 'april',
            5 => 'mei',
            6 => 'juni',
            7 => 'juli',
            8 => 'agustus',
            9 => 'september',
            10 => 'oktober',
            11 => 'november',
            12 => 'desember',
        ];

        $rows = DB::table('infaq_kemasjidan_legacy')->get();

        foreach ($rows as $r) {
            // jika Anda punya "tahun" di sistem lama, ambil dari mana? kalau tidak ada, tentukan default.
            $tahunDefault = (int) date('Y');

            foreach ($bulanMap as $bulan => $col) {
                $nominal = (float) ($r->$col ?? 0);
                if ($nominal <= 0)
                    continue;

                DB::table('infaq_kemasjidans')->insert([
                    'tanggal' => sprintf('%04d-%02d-01', $tahunDefault, $bulan),
                    'tahun' => $tahunDefault,
                    'bulan' => $bulan,
                    'nominal' => $nominal,
                    'metode_bayar' => $r->metode_bayar,
                    'keterangan' => 'Migrasi legacy (warga_id=' . $r->warga_id . ')',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // rollback sederhana: kosongkan hasil migrasi (opsional)
        DB::table('infaq_kemasjidans')->truncate();
    }
};
