<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WargaAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!session()->has('warga_id')) {
            return redirect()->route('tracking.login.form')
                ->with('error', 'Silakan login terlebih dahulu.');
        }
        return $next($request);
    }
}
