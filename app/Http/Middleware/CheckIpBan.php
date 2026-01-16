<?php

namespace App\Http\Middleware;

use App\Models\Ban;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckIpBan
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();

        // Check for active bans where IP matches OR user_id matches (if logged in)
        // Note: For now, we perform a straightforward IP check.
        // If a user is logged in, their user-level ban should have prevented login or should be checked here too.
        
        $isBanned = Ban::active()
            ->where(function ($query) use ($ip, $request) {
                $query->where('ip_address', $ip);
                if ($request->user()) {
                    $query->orWhere('user_id', $request->user()->id);
                }
            })
            ->exists();

        if ($isBanned) {
            abort(403, 'Your access has been restricted.');
        }

        return $next($request);
    }
}
