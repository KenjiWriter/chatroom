<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Rank;
use App\Models\Room;
use Inertia\Inertia;

class AdminDashboardController extends Controller
{
    public function index()
    {
        // Security check for manage_rooms or manage_ranks permissions
        if (! auth()->user()->hasPermission('manage_rooms') && ! auth()->user()->hasPermission('manage_ranks')) {
            abort(403);
        }

        return Inertia::render('Admin/Settings', [
            'rooms' => Room::with('requiredRank')->get(),
            'ranks' => Rank::with('permissions')->orderBy('priority', 'desc')->get(),
            'permissions' => Permission::all(),
        ]);
    }
}
