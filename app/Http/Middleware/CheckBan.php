<?php

namespace App\Http\Middleware;

use App\Models\Ban;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckBan
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();

        // 1. Bypass for Admins/Mods
        if ($request->user() && ($request->user()->hasPermission('admin.access') || $request->user()->hasPermission('ban_room_access'))) {
            return $next($request);
        }

        // 2. Identify Context (Global vs Room)
        // Accessing route parameter 'room' (which might be a Room model or slug/ID)
        $routeRoom = $request->route('room'); 
        $roomId = null;

        if ($routeRoom instanceof \App\Models\Room) {
            $roomId = $routeRoom->id;
        } elseif (is_string($routeRoom) || is_numeric($routeRoom)) {
             // If slug or ID, try to resolve? Or rely on explicit room bindings.
             // Usually route model binding handles this.
             // If it's a slug, we might not have ID yet if binding didn't happen ??
             // Actually middleware runs after binding normally?
             // 'substituteBindings' runs in 'web' group. If this runs after, we are good.
             // Assuming $routeRoom is resolved model.
        }

        // 3. Check Bans
        $isBanned = \App\Models\Ban::active()
            ->where(function ($query) use ($ip, $request, $roomId) {
                // Determine user identity
                $userId = $request->user()?->id;

                if ($userId) {
                    $query->where('user_id', $userId);
                    // Optional: checking IP for logged in users?
                    // $query->orWhere('ip_address', $ip); // Let's avoid IP for logged in users to avoid self-ban issues on shared IP
                } else {
                    $query->where('ip_address', $ip);
                }
            })
            ->where(function ($query) use ($roomId) {
                // Check Global Ban (room_id is NULL)
                $query->whereNull('room_id');
                
                // OR Check Room Ban if we are entering a room
                if ($roomId) {
                    $query->orWhere('room_id', $roomId);
                }
            })
            ->exists();

        if ($isBanned) {
            if ($roomId) {
                // Room Ban -> Redirect to Dashboard
                return redirect()->route('dashboard')->with('error', 'You are banned from this room.');
            } else {
                // Global Ban -> Full Stop
                abort(403, 'Your access has been restricted.');
            }
        }

        return $next($request);
    }
}
