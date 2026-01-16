<?php

namespace App\Services;

class LevelService
{
    /**
     * Calculate level based on XP.
     */
    public function calculateLevel(int $xp): int
    {
        $coefficient = config('chat.leveling.coefficient', 100);

        if ($xp < 0) {
            return 1;
        }

        if ($coefficient <= 0) {
            return 1;
        }

        // Formula: XP = level^2 * coefficient
        // Level = sqrt(XP / coefficient)
        return (int) floor(sqrt($xp / $coefficient)) ?: 1;
    }

    /**
     * Calculate XP required for a specific level.
     */
    public function xpForLevel(int $level): int
    {
        $coefficient = config('chat.leveling.coefficient', 100);
        return ($level ** 2) * $coefficient;
    }

    /**
     * Calculate XP needed for the next level.
     */
    public function xpForNextLevel(int $currentLevel): int
    {
        return $this->xpForLevel($currentLevel + 1);
    }
}
