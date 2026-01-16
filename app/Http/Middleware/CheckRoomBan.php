<?php

namespace App\Http\Middleware;

use App\Models\Room;
use App\Services\ModerationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRoomBan
{
    public function __construct(protected ModerationService $moderationService) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $room = $request->route('room');
        
        if ($room instanceof Room && auth()->check()) {
            if ($this->moderationService->isMuted(auth()->user(), $room)) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'You are muted in this room.'], 403);
                }
                return back()->with('error', 'You are muted in this room.');
            }
        }

        return $next($request);
    }
}
