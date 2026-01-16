<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRankRequest;
use App\Http\Requests\UpdateRankRequest;
use App\Models\Permission;
use App\Models\Rank;
use Inertia\Inertia;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class RankController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(function ($request, $next) {
                if (! auth()->user()->hasPermission('manage_ranks')) {
                    abort(403);
                }
                return $next($request);
            }),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Admin/Ranks/Index', [
            'ranks' => Rank::orderBy('priority', 'desc')->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Admin/Ranks/Create', [
            'permissions' => Permission::all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRankRequest $request)
    {
        $rank = Rank::create($request->validated());

        if ($request->has('permissions')) {
            $rank->permissions()->sync($request->input('permissions'));
        }

        return redirect()->route('admin.settings')->with('success', 'Rank created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Rank $rank)
    {
        return Inertia::render('Admin/Ranks/Edit', [
            'rank' => $rank->load('permissions'),
            'permissions' => Permission::all(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRankRequest $request, Rank $rank)
    {
        $rank->update($request->validated());

        if ($request->has('permissions')) {
            $rank->permissions()->sync($request->input('permissions'));
        }

        return redirect()->route('admin.settings')->with('success', 'Rank updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Rank $rank)
    {
        $rank->delete();

        return redirect()->route('admin.settings')->with('success', 'Rank deleted successfully.');
    }
}
