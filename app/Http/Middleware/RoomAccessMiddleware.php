<?php

namespace App\Http\Middleware;

use App\Models\Room;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoomAccessMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $room = $request->route('room');
        
        // If the route has a room parameter (could be slug or ID depending on binding)
        if ($room instanceof Room) {
            if (! $room->checkAccess(auth()->user())) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Access denied.'], 403);
                }
                return redirect()->route('chat.index')->with('error', 'You do not have access to this room.');
            }
        }

        return $next($request);
    }
}
