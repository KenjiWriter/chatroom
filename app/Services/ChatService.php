<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class ChatService
{
    public function __construct(
        protected LevelService $levelService
    ) {}

    /**
     * Award XP to a user with cooldown.
     * Returns array with 'leveled_up' (bool) and 'new_level' (int|null).
     */
    public function awardXP(User $user): array
    {
        $xpToAdd = rand(5, 15);
        $cacheKey = 'xp_cooldown_' . $user->id;

        // Atomic lock check - add returns true only if key didn't exist
        if (! Cache::add($cacheKey, true, now()->addSeconds(10))) {
            return ['leveled_up' => false, 'new_level' => null];
        }

        $user->increment('xp', $xpToAdd);
        
        // Check for level up
        $currentLevel = $user->level;
        $calculatedLevel = $this->levelService->calculateLevel($user->xp);

        if ($calculatedLevel > $currentLevel) {
            $user->update(['level' => $calculatedLevel]);
            return [
                'leveled_up' => true, 
                'new_level' => $calculatedLevel,
                'xp_gained' => $xpToAdd
            ];
        }

        return [
            'leveled_up' => false, 
            'new_level' => null,
            'xp_gained' => $xpToAdd
        ];
    }
}
