<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user() ? [
                    ...$request->user()->loadMissing('rank')->toArray(), // includes appended rank_data
                    'permissions' => function () use ($request) {
                        return $request->user()->rank ? $request->user()->rank->permissions->pluck('slug') : [];
                    },
                ] : null,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'translations' => function () {
                return \Illuminate\Support\Facades\Cache::rememberForever('translations_en', function () {
                    $path = lang_path('en.json');
                    return file_exists($path) ? json_decode(file_get_contents($path), true) : [];
                });
            },
            'flash' => [
                'message' => fn () => $request->session()->get('message'), // Generic interface
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'level_up' => fn () => $request->session()->get('level_up'), // Structured object
            ],
            'conversations' => function () use ($request) {
                if (! $request->user()) return [];
                return $request->user()->conversations()
                    ->with(['users' => function($q) use ($request) {
                        $q->where('users.id', '!=', $request->user()->id);
                    }, 'lastMessage'])
                    ->withCount(['messages as unread_count' => function($q) use ($request) {
                        $q->where('sender_id', '!=', $request->user()->id)
                          ->whereNull('read_at');
                    }])
                    ->orderByDesc('last_message_at')
                    ->take(10)
                    ->get();
            },
            'ziggy' => function () use ($request) {
                return array_merge((new \Tighten\Ziggy\Ziggy)->toArray(), [
                    'location' => $request->url(),
                ]);
            },
        ];
    }
}
