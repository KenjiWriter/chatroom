<?php

namespace App\Listeners;

use App\Models\Rank;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class UpgradeUserRankAfterVerification
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Verified $event): void
    {
        $user = $event->user;

        // Find the 'User' rank
        $userRank = Rank::where('name', 'User')->first();

        if ($userRank) {
            // Only upgrade if they are currently Guest or have no rank (optional safety)
            // Or just force set it if that's the rule.
            // Requirement: "remove the "Guest" rank" implies they have Guest.
            // We'll just set it to User rank as it's the target state.
            
            $user->rank_id = $userRank->id;
            $user->save();

            Log::info("User {$user->email} verified email and was upgraded to 'User' rank.");
        } else {
            Log::error("Could not upgrade user {$user->email} to 'User' rank: Rank not found.");
        }
    }
}
