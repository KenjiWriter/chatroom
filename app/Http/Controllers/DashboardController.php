<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Services\LevelService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request, LevelService $levelService, \App\Services\FriendshipService $friendshipService)
    {
        $user = $request->user();
        
        // Ensure relationships are loaded if not already handled by middleware or global scopes
        $user->load(['rank']);

        // XP Progress to next level
        $currentLevelXp = $levelService->xpForLevel($user->level);
        $nextLevelXp = $levelService->xpForLevel($user->level + 1);
        
        $relativeXp = max(0, $user->xp - $currentLevelXp);
        $neededForNext = max(1, $nextLevelXp - $currentLevelXp); // Avoid division by zero
        
        $progress = [
            'current_xp' => $user->xp,
            'level_start_xp' => $currentLevelXp,
            'next_level_xp' => $nextLevelXp,
            'relative_xp' => $relativeXp,
            'needed_for_next' => $neededForNext,
            'percent' => min(100, round(($relativeXp / $neededForNext) * 100)),
        ];

        // Active Rooms
        $rooms = Room::where('is_active', true)
            ->with('requiredRank')
            ->orderBy('min_level')
            ->orderBy('name')
            ->get();

        // Social Data
        $friends = $friendshipService->getFriends($user)->map(function($f) {
            return [
                'id' => $f->id,
                'name' => $f->name,
                'avatar_url' => $f->avatar_url,
                'rank_data' => $f->rank_data,
                // We don't expose room unless allowed by their privacy settings.
                // For list, we might just load online status on frontend via Echo.
            ];
        });

        $pendingRequests = $user->receivedFriendships()
            ->where('status', 'pending')
            ->with('sender.rank')
            ->get()
            ->map(function($f) {
                return [
                    'id' => $f->id, // Friendship ID for Accepting
                    'user' => $f->sender,
                    'created_at' => $f->created_at->diffForHumans(),
                ];
            });

        $roomHistory = $user->visits()
            ->with('room')
            ->orderByDesc('last_visited_at')
            ->take(5)
            ->get();

        return Inertia::render('Dashboard', [
            'progress' => $progress,
            'rooms' => $rooms,
            'friends' => $friends,
            'pendingRequests' => $pendingRequests,
            'roomHistory' => $roomHistory,
        ]);
    }
}
