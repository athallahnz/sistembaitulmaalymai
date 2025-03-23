<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, $role = null)
    {
        // Periksa apakah pengguna sudah terautentikasi
        if (!auth()->check()) {
            return redirect()->route('login'); // Arahkan ke halaman login jika belum terautentikasi
        }

        // Periksa apakah user memiliki salah satu role yang diperbolehkan
        $user = auth()->user();

        if ($user->hasRole(['Admin', 'Manajer Keuangan', 'Ketua Yayasan', 'Bendahara', 'Bidang'])) {
            return $next($request); // Lanjutkan jika memiliki role yang sesuai
        }

        // Jika tidak memiliki izin, abort dengan status 403 dan pesan akses ditolak
        abort(403, 'Anda tidak memiliki izin untuk mengakses halaman ini.');
    }
}
