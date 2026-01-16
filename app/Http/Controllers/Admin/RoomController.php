<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class RoomController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(function ($request, $next) {
                if (! auth()->user()->hasPermission('manage_rooms')) {
                    abort(403);
                }
                return $next($request);
            }),
        ];
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'min_level' => 'required|integer|min:0',
            'required_rank_id' => 'nullable|exists:ranks,id',
            'is_active' => 'required|boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        Room::create($validated);

        return redirect()->route('admin.settings')->with('success', 'Room created successfully.');
    }

    public function update(Request $request, Room $room)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'min_level' => 'required|integer|min:0',
            'required_rank_id' => 'nullable|exists:ranks,id',
            'is_active' => 'required|boolean',
        ]);

        if ($room->name !== $validated['name']) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $room->update($validated);

        return redirect()->route('admin.settings')->with('success', 'Room updated successfully.');
    }

    public function destroy(Room $room)
    {
        $room->delete();

        return redirect()->route('admin.settings')->with('success', 'Room deleted successfully.');
    }
}
