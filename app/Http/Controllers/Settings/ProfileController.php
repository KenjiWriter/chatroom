<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/Profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        // Handle Avatar
        if ($request->hasFile('avatar')) {
             // Delete old
             if ($user->avatar_url) {
                 // Check if it's a local file path (not external URL)
                 $path = str_replace('/storage/', '', parse_url($user->avatar_url, PHP_URL_PATH));
                 if ($path && \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                     \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
                 }
             }
             $path = $request->file('avatar')->store('avatars', 'public');
             $validated['avatar_url'] = \Illuminate\Support\Facades\Storage::url($path);
        } elseif ($request->boolean('delete_avatar')) {
             if ($user->avatar_url) {
                 $path = str_replace('/storage/', '', parse_url($user->avatar_url, PHP_URL_PATH));
                 if ($path && \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                     \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
                 }
             }
             $validated['avatar_url'] = null;
        }

        // Handle Banner
        if ($request->hasFile('banner')) {
             if ($user->banner_url) {
                 $path = str_replace('/storage/', '', parse_url($user->banner_url, PHP_URL_PATH));
                 if ($path && \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                     \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
                 }
             }
             $path = $request->file('banner')->store('banners', 'public');
             $validated['banner_url'] = \Illuminate\Support\Facades\Storage::url($path);
        } elseif ($request->boolean('delete_banner')) {
             if ($user->banner_url) {
                 $path = str_replace('/storage/', '', parse_url($user->banner_url, PHP_URL_PATH));
                 if ($path && \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                     \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
                 }
             }
             $validated['banner_url'] = null;
        }

        // Remove file objects from validated data before filling model
        unset($validated['avatar'], $validated['banner'], $validated['delete_avatar'], $validated['delete_banner']);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return to_route('profile.edit');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
