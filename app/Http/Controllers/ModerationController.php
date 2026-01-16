<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\User;
use App\Services\ModerationService;
use Illuminate\Http\Request;

class ModerationController extends Controller
{
    public function __construct(protected ModerationService $moderationService) {}

    public function kick(Request $request, Room $room, User $user)
    {
        if (! auth()->user()->hasPermission('kick_user')) {
             abort(403, 'You do not have permission to kick users.');
        }

        $request->validate(['reason' => 'required|string|max:255']);

        $this->moderationService->kick(auth()->user(), $user, $room, $request->input('reason'));

        return back()->with('success', 'Użytkownik został wyrzucony.');
    }

    public function mute(Request $request, User $user)
    {
        $duration = $request->input('duration');
        // 'permanent' checkbox usually sends 'true' or 'on', define simple bool logic
        $isPermanent = $request->boolean('permanent') || !$duration;
        
        // If permanent, pass null. If duration, pass int.
        $finalDuration = $isPermanent ? null : (int)$duration;

        $room = null;
        if ($request->input('room_id')) {
            $room = Room::findOrFail($request->input('room_id'));
        }

        $this->moderationService->mute(
            auth()->user(), 
            $user, 
            $room, 
            $finalDuration, 
            $request->input('reason')
        );

        return back()->with('success', 'Użytkownik został wyciszony.');
    }

    public function unmute(Request $request, User $user)
    {
        if (!auth()->user()->can('unmute_user')) {
             abort(403);
        }

        $this->moderationService->unmute(auth()->user(), $user);
        
        return back()->with('success', 'Użytkownik został odwyciszony.');
    }
    
    public function ban(Request $request, User $user)
    {
        if (! auth()->user()->hasPermission('ban_room_access')) {
            abort(403, 'You do not have permission to ban users.');
        }

        $request->validate([
            'reason' => 'required|string|max:255',
            'duration' => 'nullable|integer|min:1',
            'room_id' => 'nullable|exists:rooms,id',
        ]);

        $room = null;
        if ($request->input('room_id')) {
            $room = Room::findOrFail($request->input('room_id'));
        }

        $this->moderationService->ban(
            auth()->user(), 
            $user, 
            $request->input('duration') ? (int)$request->input('duration') : null, 
            $request->input('reason'),
            $room
        );

        return back()->with('success', 'Użytkownik został zbanowany.');
    }

    public function unban(Request $request, User $user)
    {
        if (!auth()->user()->can('unban_user') && !auth()->user()->can('ban_room_access')) {
             abort(403, "No permission to unban.");
        }

        $room = null;
        if ($request->input('room_id')) {
             $room = Room::findOrFail($request->input('room_id'));
        }

        $this->moderationService->unban(auth()->user(), $user, $room);

        return back()->with('success', 'Użytkownik został odbanowany.');
    }
}
