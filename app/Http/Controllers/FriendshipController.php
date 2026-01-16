<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\FriendshipService;
use Illuminate\Http\Request;

class FriendshipController extends Controller
{
    protected $friendshipService;

    public function __construct(FriendshipService $friendshipService)
    {
        $this->friendshipService = $friendshipService;
    }

    public function store(Request $request, User $user)
    {
        try {
            $this->friendshipService->sendRequest($request->user(), $user);
            return back()->with('flash', ['message' => 'Friend request sent.', 'type' => 'success']);
        } catch (\Exception $e) {
            return back()->with('flash', ['message' => $e->getMessage(), 'type' => 'error']);
        }
    }

    public function update(Request $request, $id)
    {
        // Accept Request
        try {
            $this->friendshipService->acceptRequest($request->user(), $id);
            return back()->with('flash', ['message' => 'Friend request accepted.', 'type' => 'success']);
        } catch (\Exception $e) {
             return back()->with('flash', ['message' => 'Failed to accept request.', 'type' => 'error']);
        }
    }

    public function destroy(Request $request, $id)
    {
        // Decline / Cancel / Unfriend
        try {
            // Note: Service destroy logic needs ID of friendship. 
            // If passed ID is user ID (unfriend), we need to find friendship.
            // Assuming route passes Friendship ID for destroy? 
            // Usually frontend sends Friendship ID or User ID.
            // Let's assume Friendship ID for consistency with Resource controller, 
            // BUT for "Unfriend" on profile, we might only have User ID.
            // The service `declineRequest` takes Friendship ID.
            // Let's implement unfriend by User ID logic in service or handle here.
            
            // To simplify, let's assume standard Resource Route: DELETE /friendships/{friendship}
            $this->friendshipService->declineRequest($request->user(), $id);
             return back()->with('flash', ['message' => 'Removed.', 'type' => 'success']);
        } catch (\Exception $e) {
            return back()->with('flash', ['message' => 'Failed to remove.', 'type' => 'error']);
        }
    }
}
