<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CheckSnapshotRunning
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Cache::has('is_calculating_snapshot')) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'System is currently running daily bank calculations. Transactions are temporarily locked. Please try again shortly.');
        }

        return $next($request);
    }
}
