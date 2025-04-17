<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class UpdateLastActivity
{
    public function handle($request, Closure $next)
    {
        if (Auth::check()) {
            Auth::user()->update([
                'last_activity_at' => Carbon::now(),
                'is_active' => true
            ]);
        }

        return $next($request);
    }
}

