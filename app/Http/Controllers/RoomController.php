<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Message;
use App\Models\Room;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RoomController extends Controller
{
    public function index()
    {
        return Inertia::render('Chat/Index', [
            'rooms' => Room::with('requiredRank')->where('is_active', true)->get(),
        ]);
    }

    public function show(Room $room)
    {
        if (! $room->checkAccess(auth()->user())) {
             return redirect()->route('chat.index')
                ->with('flash', [
                    'message' => 'You do not have access to this room.',
                    'type' => 'error'
                ]);
        }
        
        // Log visit
        \App\Models\RoomVisit::updateOrCreate(
            ['user_id' => auth()->id(), 'room_id' => $room->id],
            ['last_visited_at' => now()]
        );
        
        // Eager load rank for the message author to use RankedUserLabel
        $messages = $room->messages()
            ->with(['user.rank'])
            ->latest()
            ->take(50)
            ->get()
            ->reverse()
            ->values();

        return Inertia::render('Chat/Room', [
            'room' => $room,
            'initialMessages' => $messages,
        ]);
    }

    public function storeMessage(Request $request, Room $room, ChatService $chatService, \App\Services\ModerationService $moderationService)
    {
        if (! $room->checkAccess(auth()->user())) {
            abort(403, 'Unauthorized.');
        }

        if ($moderationService->isMuted(auth()->user(), $room)) {
            return response()->json(['message' => 'You are muted.'], 403);
        }

        $request->validate([
            'content' => ['required', 'string', 'max:1000'],
        ]);

        $message = $room->messages()->create([
            'user_id' => auth()->id(),
            'content' => $request->input('content'),
        ]);

        // Award XP
        $xpResult = $chatService->awardXP(auth()->user());
        
        // Broadcast
        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'message' => $message->load(['user.rank']),
            'xp_result' => $xpResult,
        ]); 
    }
}
