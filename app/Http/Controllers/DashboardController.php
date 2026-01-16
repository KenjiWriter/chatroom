<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Services\LevelService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request, LevelService $levelService)
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

        return Inertia::render('Dashboard', [
            'progress' => $progress,
            'rooms' => $rooms,
        ]);
    }
}
