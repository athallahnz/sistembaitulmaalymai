<?php

namespace App\Helpers;

use App\Models\TagihanSpp;

class TagihanHelper
{
    public static function getStatusTagihan(int $studentId): array
    {
        $tagihans = TagihanSpp::where('student_id', $studentId)->get();

        if ($tagihans->isEmpty()) {
            return [
                'status' => 'belum_ada',
                'message' => 'Belum ada tagihan yang dicatat.',
                'total' => 0,
                'total_bayar' => 0,
                'sisa' => 0,
                'tagihan_count' => 0,
                'tagihan' => [],
            ];
        }

        $total = $tagihans->sum('jumlah');
        $totalBayar = $tagihans->where('status', 'lunas')->sum('jumlah');
        $sisa = $tagihans->where('status', 'belum_lunas')->sum('jumlah');

        if ($tagihans->every(fn($t) => $t->status === 'lunas')) {
            $status = 'lunas';
            $message = 'Semua tagihan sudah lunas.';
        } else {
            $status = 'belum_lunas';
            $message = 'Masih ada tagihan yang belum dibayar.';
        }

        return (array) [
            'status' => $status,
            'message' => $message,
            'total' => (int) $total,
            'total_bayar' => (int) $totalBayar,
            'sisa' => (int) $sisa,
            'tagihan_count' => $tagihans->count(),
            'tagihan' => $tagihans,
        ];
    }
}
