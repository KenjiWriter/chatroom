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

        return redirect()->route('dashboard')->with('punishment', [
            'type' => 'kick',
            'admin' => auth()->user()->name,
            'reason' => $request->input('reason'),
            'room' => $room->name
        ]);
    }

    public function mute(Request $request, User $user)
    {
        $duration = $request->input('duration');
        $isPermanent = $request->boolean('is_permanent') || $duration === null;

        if ($isPermanent) {
            if (! auth()->user()->hasPermission('mute_perm')) {
                abort(403, 'You do not have permission for permanent mutes.');
            }
        } else {
            if (! auth()->user()->hasPermission('mute_temp')) {
                abort(403, 'You do not have permission for temporary mutes.');
            }
        }

        $request->validate([
            'reason' => 'required|string|max:255',
            'duration' => 'nullable|integer|min:1',
            'room_id' => 'nullable|exists:rooms,id'
        ]);

        $room = $request->input('room_id') ? Room::find($request->input('room_id')) : null;

        $this->moderationService->mute(
            auth()->user(), 
            $user, 
            $room, 
            $isPermanent ? null : (int)$duration, 
            $request->input('reason')
        );

        return back()->with('punishment_notified', true)->with('punishment', [
            'type' => 'mute',
            'admin' => auth()->user()->name,
            'reason' => $request->input('reason'),
            'room' => $room?->name ?? 'Global',
            'duration' => $isPermanent ? 'Permanent' : "{$duration} minutes"
        ]);
    }
    
    public function ban(Request $request, User $user)
    {
        if (! auth()->user()->hasPermission('ban_room_access')) {
            abort(403, 'You do not have permission to ban users.');
        }

        $request->validate([
            'reason' => 'required|string|max:255',
            'duration' => 'nullable|integer|min:1',
        ]);

        $this->moderationService->ban(
            auth()->user(), 
            $user, 
            $request->input('duration') ? (int)$request->input('duration') : null, 
            $request->input('reason')
        );

        return redirect()->route('dashboard')->with('punishment', [
            'type' => 'ban',
            'admin' => auth()->user()->name,
            'reason' => $request->input('reason'),
            'duration' => $request->input('duration') ? $request->input('duration') . " minutes" : 'Permanent'
        ]);
    }
    
    // Ban implementation similar...
}
