<?php

namespace App\Http\Controllers;

use App\Events\DirectMessageSent;
use App\Models\Conversation;
use App\Models\DirectMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ConversationController extends Controller
{
    public function index(Request $request)
    {
        $conversations = $request->user()->conversations()
            ->with(['users' => function($q) use ($request) {
                $q->where('users.id', '!=', $request->user()->id);
            }, 'lastMessage'])
            ->withCount(['messages as unread_count' => function($q) use ($request) {
                $q->where('sender_id', '!=', $request->user()->id)
                  ->whereNull('read_at');
            }])
            ->orderByDesc('last_message_at')
            ->get();

        return Inertia::render('Conversations/Index', [
            'conversations' => $conversations,
        ]);
    }

    public function show(Conversation $conversation)
    {
        $this->authorizeAccess($conversation);

        $messages = $conversation->messages()
            ->with('sender')
            ->oldest()
            ->get();

        // Mark unread messages as read
        $conversation->messages()
            ->where('sender_id', '!=', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return Inertia::render('Conversations/Show', [
            'conversation' => $conversation->load(['users' => function($q) {
                $q->where('users.id', '!=', auth()->id());
            }]),
            'initialMessages' => $messages,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $otherUserId = $request->user_id;

        if ($otherUserId == auth()->id()) {
            abort(403, 'You cannot message yourself.');
        }

        // Find existing 1-on-1 conversation
        $conversation = auth()->user()->conversations()
            ->whereHas('users', function($q) use ($otherUserId) {
                $q->where('users.id', $otherUserId);
            })
            ->first();

        if (!$conversation) {
            $conversation = Conversation::create(['last_message_at' => now()]);
            $conversation->users()->attach([auth()->id(), $otherUserId]);
        }

        return redirect()->route('conversations.show', $conversation->id);
    }

    public function sendMessage(Request $request, Conversation $conversation)
    {
        $this->authorizeAccess($conversation);

        $request->validate([
            'body' => 'required|string|max:2000',
            'type' => 'nullable|string|in:text,gif',
            'metadata' => 'nullable|array',
        ]);

        $message = $conversation->messages()->create([
            'sender_id' => auth()->id(),
            'body' => $request->body,
            'type' => $request->input('type', 'text'),
            'metadata' => $request->input('metadata'),
        ]);

        $conversation->update(['last_message_at' => now()]);

        broadcast(new DirectMessageSent($message))->toOthers();

        return response()->json([
            'message' => $message->load('sender'),
        ]);
    }

    public function markAsRead(Conversation $conversation)
    {
        $this->authorizeAccess($conversation);

        $conversation->messages()
            ->where('sender_id', '!=', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    protected function authorizeAccess(Conversation $conversation)
    {
        if (!$conversation->users()->where('users.id', auth()->id())->exists()) {
            abort(403);
        }
    }
}
