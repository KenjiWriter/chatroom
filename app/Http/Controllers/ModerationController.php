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
        $this->authorize('kick', $user); // Define Policy or Gates later. For now, check permission manually if needed.
        // Actually, let's rely on Service hierarchy check + basic permission check here.
        if (! auth()->user()->hasPermission('kick_user')) {
             abort(403);
        }

        $request->validate(['reason' => 'required|string|max:255']);

        $this->moderationService->kick(auth()->user(), $user, $room, $request->input('reason'));

        return back()->with('success', 'User kicked.');
    }

    public function mute(Request $request, User $user)
    {
        if (! auth()->user()->hasPermission('mute_user')) {
             abort(403);
        }

        $request->validate([
            'reason' => 'required|string|max:255',
            'duration' => 'nullable|integer|min:1', // Minutes
            'room_id' => 'nullable|exists:rooms,id'
        ]);

        $room = $request->input('room_id') ? Room::find($request->input('room_id')) : null;

        $this->moderationService->mute(
            auth()->user(), 
            $user, 
            $room, 
            $request->input('duration'), 
            $request->input('reason')
        );

        return back()->with('success', 'User muted.');
    }
    
    // Ban implementation similar...
}
