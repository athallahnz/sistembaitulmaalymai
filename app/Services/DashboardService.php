<?php

namespace App\Services;

use App\Models\AkunKeuangan;
use Illuminate\Support\Carbon;

class DashboardService
{
    public function __construct(
        protected LaporanKeuanganService $laporanKeuanganService
    ) {}

    /**
     * $scope: 'BIDANG' | 'BENDAHARA' | 'YAYASAN'
     * $bidangId: int|null (untuk BIDANG saja)
     * $period: ['start' => Carbon, 'end' => Carbon]
     *
     * Return:
     * [
     *   'revenue'  => [cards...],
     *   'expense'  => [cards...],
     *   'liability'=> [cards...],
     *   'asset'    => [cards...],
     *   'equity'   => [cards...],
     * ]
     */
    public function getCards(string $scope, ?int $bidangId, array $period): array
    {
        $scope = strtoupper($scope);

        $periodStart = ($period['start'] ?? now()->copy()->startOfMonth())->copy()->startOfDay();
        $periodEnd   = ($period['end'] ?? now())->copy()->endOfDay();

        // 1) Ambil metadata akun yang ditandai tampil dashboard untuk scope ini
        $akunCards = AkunKeuangan::query()
            ->where('show_on_dashboard', true)
            ->where(function ($q) use ($scope) {
                $q->whereNull('dashboard_scope')
                    ->orWhereIn('dashboard_scope', [$scope, 'BOTH']);
            })
            ->orderBy('dashboard_section')
            ->orderBy('dashboard_order')
            ->get([
                'id',
                'nama_akun',
                'saldo_normal',
                'dashboard_scope',
                'dashboard_section',
                'dashboard_calc',
                'dashboard_order',
                'dashboard_title',
                'dashboard_icon',
                'dashboard_format',
                'dashboard_masked',
                'dashboard_link_route',
                'dashboard_link_param',
            ]);

        if ($akunCards->isEmpty()) {
            return [];
        }

        // 2) Kelompokkan parentIds berdasarkan calc (karena window period beda)
        $parentIdsByCalc = [];
        foreach ($akunCards as $akun) {
            $calc = $akun->dashboard_calc ?: 'rollup_children_period';
            $parentIdsByCalc[$calc][] = (int) $akun->id;
        }

        // 3) Tentukan mode filter bidang berdasarkan scope
        $bidangFilterMode = match ($scope) {
            'BIDANG'    => 'ID',     // bidang spesifik
            'BENDAHARA' => 'NULL',   // transaksi pusat (bidang_id IS NULL)
            'YAYASAN'   => 'ALL',    // konsolidasi (tanpa filter)
            default     => 'ALL',
        };

        // 4) Hitung totals per parent (per calc window)
        $totals = []; // [parent_id => value]
        foreach ($parentIdsByCalc as $calc => $parentIds) {

            // Window per calc
            [$start, $end] = $this->resolveWindowByCalc($calc, $periodStart, $periodEnd);

            $chunkTotals = $this->laporanKeuanganService->getDashboardTotalsByParents(
                $start,
                $end,
                $bidangFilterMode,
                $bidangId,
                array_values(array_unique($parentIds))
            );

            // merge
            foreach ($chunkTotals as $pid => $val) {
                $totals[(int) $pid] = (float) $val;
            }
        }

        // 5) Build card payload untuk Blade (100% generic)
        $cardsBySection = [];

        foreach ($akunCards as $akun) {
            $value = $totals[(int) $akun->id] ?? 0.0;

            $section = $akun->dashboard_section ?: 'other';

            $cardsBySection[$section][] = [
                'id'     => (int) $akun->id,
                'title'  => $akun->dashboard_title ?: $akun->nama_akun,
                'icon'   => $akun->dashboard_icon ?: 'bi-question-circle',
                'value'  => $value,
                'format' => $akun->dashboard_format ?: 'currency',
                'masked' => (bool) ($akun->dashboard_masked ?? false),
                'order'  => (int) ($akun->dashboard_order ?? 999),

                // label kecil biar user paham angka berdasar calc
                'label'  => $this->makeLabel($akun->dashboard_calc, $periodStart, $periodEnd),

                // link ditentukan runtime (sesuai scope)
                'link'   => $this->makeLink($scope, $akun->dashboard_link_route, $akun->dashboard_link_param, (int) $akun->id),
            ];
        }

        // 6) Sort per section by order
        foreach ($cardsBySection as $sec => &$items) {
            usort($items, fn($a, $b) => $a['order'] <=> $b['order']);
        }

        return $cardsBySection;
    }

    private function resolveWindowByCalc(string $calc, Carbon $periodStart, Carbon $periodEnd): array
    {
        $calc = strtolower($calc);

        $ps = $periodStart->copy()->startOfDay();
        $pe = $periodEnd->copy()->endOfDay();

        return match ($calc) {
            'rollup_children_ytd'    => [$pe->copy()->startOfYear()->startOfDay(), $pe],
            'rollup_children_period' => [$ps, $pe],
            default                 => [$ps, $pe],
        };
    }

    private function makeLabel(?string $calc, Carbon $periodStart, Carbon $periodEnd): string
    {
        $calc = strtolower($calc ?? '');

        // Anda minta yang lebih simpel dari 'd M Y' -> pakai "d M" atau "M Y"
        $endPretty   = $periodEnd->translatedFormat('d M Y');
        $monthPretty = $periodEnd->translatedFormat('M Y');
        $rangePretty = $periodStart->isSameMonth($periodEnd)
            ? $monthPretty
            : ($periodStart->translatedFormat('d M') . ' – ' . $periodEnd->translatedFormat('d M Y'));

        return match ($calc) {
            'rollup_children_ytd'    => "YTD s/d {$endPretty}",
            'rollup_children_period' => "Periode {$rangePretty}",
            default                  => "s/d {$endPretty}",
        };
    }

    private function makeLink(string $scope, ?string $routeOverride, ?string $paramName, int $parentAkunId): ?string
    {
        // kalau akun punya override route di DB, pakai itu
        $route = $routeOverride;

        // default route by scope
        if (!$route) {
            $route = match (strtoupper($scope)) {
                'BIDANG'    => 'bidang.detail',
                'BENDAHARA' => 'bendahara.detail',
                'YAYASAN'   => 'bendahara.detail', // yayasan masih di modul bendahara
                default     => null,
            };
        }

        if (!$route) return null;

        $param = $paramName ?: 'parent_akun_id';

        return route($route, [$param => $parentAkunId]);
    }
}
