<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, $role)
    {
        // Periksa apakah user memiliki salah satu role yang diperbolehkan
        if (auth()->user()->hasRole(['Admin','Manajer Keuangan','Ketua Yayasan','Bendahara', 'Bidang'])) {
            return $next($request);
        }

        // Jika tidak memiliki izin, abort dengan status 403
        abort(403, 'Anda tidak memiliki izin untuk mengakses halaman ini.');
    }
}
