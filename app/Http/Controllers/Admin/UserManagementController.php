<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Events\UserPromoted;
use App\Models\Rank;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class UserManagementController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(function ($request, $next) {
                if (! auth()->user()->hasPermission('manage_user_ranks')) {
                    abort(403, 'You do not have permission to manage user ranks.');
                }
                return $next($request);
            }),
        ];
    }

    /**
     * Update the user's rank with hierarchy protection.
     */
    public function updateRank(Request $request, User $user)
    {
        $validated = $request->validate([
            'rank_id' => 'required|exists:ranks,id',
        ]);

        $newRank = Rank::findOrFail($validated['rank_id']);
        $me = auth()->user();

        // 1. Can I manage this target user? (My Priority > Their Priority)
        if (!$me->canManage($user)) {
            abort(403, 'You cannot manage a user with a higher or equal rank.');
        }

        // 2. Can I assign this specific rank? (My Priority > New Rank Priority)
        if (!$me->canAssignRank($newRank)) {
            abort(403, 'You cannot assign a rank higher than or equal to your own.');
        }

        $oldRank = $user->rank;
        $user->rank_id = $newRank->id;
        $user->save();

        if (!$oldRank || $newRank->priority > $oldRank->priority) {
            event(new UserPromoted($user, $newRank, $oldRank));
        }

        return back()->with('success', "Updated {$user->name}'s rank to {$newRank->name}.");
    }

    /**
     * Get list of ranks assignable by the current user.
     */
    public function assignableRanks()
    {
        $me = auth()->user();
        $ranks = Rank::all()->filter(function ($rank) use ($me) {
            return $me->canAssignRank($rank);
        })->values();

        return response()->json($ranks);
    }
}
