<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AkunKeuangan;
use Illuminate\Support\Facades\DB;

class AkunKeuanganSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cards = [
            // Pendapatan (parent 201–207)
            201 => ['section' => 'revenue', 'calc' => 'rollup_children_ytd', 'order' => 10, 'title' => 'Pendapatan PMB', 'icon' => 'bi bi-journal-text'],
            202 => ['section' => 'revenue', 'calc' => 'rollup_children_ytd', 'order' => 11, 'title' => 'Pendapatan SPP', 'icon' => 'bi bi-journal-check'],
            203 => ['section' => 'revenue', 'calc' => 'rollup_children_ytd', 'order' => 12, 'title' => 'Pendapatan Lain Pendidikan', 'icon' => 'bi bi-mortarboard'],
            204 => ['section' => 'revenue', 'calc' => 'rollup_children_ytd', 'order' => 13, 'title' => 'Infaq Tidak Terikat', 'icon' => 'bi bi-heart'],
            205 => ['section' => 'revenue', 'calc' => 'rollup_children_ytd', 'order' => 14, 'title' => 'Infaq / Zakat Terikat', 'icon' => 'bi bi-heart-pulse'],
            206 => ['section' => 'revenue', 'calc' => 'rollup_children_ytd', 'order' => 15, 'title' => 'Pendapatan Usaha', 'icon' => 'bi bi-shop'],
            207 => ['section' => 'revenue', 'calc' => 'rollup_children_ytd', 'order' => 16, 'title' => 'Pendapatan Bendahara Umum', 'icon' => 'bi bi-bank2'],

            // Beban (parent 302–309) + 310
            302 => ['section' => 'expense', 'calc' => 'rollup_children_period', 'order' => 30, 'title' => 'Beban Gaji & Tunjangan', 'icon' => 'bi bi-people'],
            303 => ['section' => 'expense', 'calc' => 'rollup_children_period', 'order' => 31, 'title' => 'Biaya Operasional', 'icon' => 'bi bi-gear'],
            304 => ['section' => 'expense', 'calc' => 'rollup_children_period', 'order' => 32, 'title' => 'Biaya Kegiatan', 'icon' => 'bi bi-calendar-check'],
            305 => ['section' => 'expense', 'calc' => 'rollup_children_period', 'order' => 33, 'title' => 'Biaya Konsumsi', 'icon' => 'bi bi-cup-hot'],
            306 => ['section' => 'expense', 'calc' => 'rollup_children_period', 'order' => 34, 'title' => 'Biaya Pemeliharaan', 'icon' => 'bi bi-tools'],
            307 => ['section' => 'expense', 'calc' => 'rollup_children_period', 'order' => 35, 'title' => 'Pengeluaran Terikat', 'icon' => 'bi bi-heart-pulse'],
            308 => ['section' => 'expense', 'calc' => 'rollup_children_period', 'order' => 36, 'title' => 'Biaya Lain-lain', 'icon' => 'bi bi-three-dots'],
            309 => ['section' => 'expense', 'calc' => 'rollup_children_period', 'order' => 37, 'title' => 'Pengeluaran Bendahara', 'icon' => 'bi bi-building-fill-gear'],
            310 => ['section' => 'expense', 'calc' => 'rollup_children_period', 'order' => 38, 'title' => 'Biaya Dibayar Dimuka', 'icon' => 'bi bi-clock-history'],

            // Liabilitas pendidikan (contoh)
            50011 => ['section' => 'liability', 'calc' => 'rollup_children_period', 'order' => 21, 'title' => 'PBD SPP', 'icon' => 'bi bi-hourglass-split'],
            50012 => ['section' => 'liability', 'calc' => 'rollup_children_period', 'order' => 20, 'title' => 'PBD PMB', 'icon' => 'bi bi-hourglass-split'],
        ];

        foreach ($cards as $id => $meta) {
            AkunKeuangan::where('id', $id)->update([
                'show_on_dashboard' => true,
                'dashboard_scope' => 'BOTH',
                'dashboard_section' => $meta['section'],
                'dashboard_calc' => $meta['calc'],
                'dashboard_order' => $meta['order'],
                'dashboard_title' => $meta['title'],
                'dashboard_icon' => $meta['icon'],
                'dashboard_link_route' => null,         // set di runtime (bidang/bendahara)
                'dashboard_link_param' => 'parent_akun_id',
                'dashboard_format' => 'currency',
                'dashboard_masked' => false,
            ]);
        }
    }
}
