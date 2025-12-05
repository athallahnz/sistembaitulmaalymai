<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class LogUserActivity
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Lanjutkan request dulu
        $response = $next($request);

        // Hanya log kalau user login
        if (Auth::check()) {
            $user = Auth::user();

            // Update last_activity_at & is_active (pastikan kolom ini ada di tabel users)
            try {
                $user->update([
                    'last_activity_at' => Carbon::now(),
                    'is_active' => true,
                ]);
            } catch (\Throwable $e) {
                Log::error('Gagal update last_activity user', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Simpan log activity – dibungkus try/catch biar kalau error nggak ganggu response
            try {
                ActivityLog::create([
                    'user_id' => $user->id,
                    'action' => $this->getActionText($request),
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            } catch (\Throwable $e) {
                Log::error('Gagal simpan activity log', [
                    'user_id' => $user->id,
                    'url' => $request->fullUrl(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $response;
    }

    /**
     * Buat teks deskripsi aksi (aman untuk route null).
     */
    protected function getActionText(Request $request): string
    {
        $route = $request->route();

        if ($route && $route->getName()) {
            return 'Mengakses route: ' . $route->getName();
        }

        return 'Mengakses URL: ' . $request->path();
    }
}
