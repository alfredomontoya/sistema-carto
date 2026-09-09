<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\UserService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(
        private readonly UserService $users,
    ) {}

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $request->user()->load(['currentAssignment.position.area', 'roles']);

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'avatar_gallery' => config('avatars.gallery'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if (array_key_exists('username', $data)) {
            $data['email'] = UserService::emailFor($data['username']);
            unset($data['username']);
        }

        $this->users->updateProfile($request->user(), $data);

        if ($request->user()->wasChanged('email')) {
            $request->user()->forceFill(['email_verified_at' => null])->save();
        }

        return Redirect::route('profile.edit');
    }

    /**
     * Update the user's password.
     */
    public function password(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $this->users->updatePassword($request->user(), $request->input('password'));

        return Redirect::route('profile.edit');
    }

    /**
     * Upload a custom avatar image.
     */
    public function avatar(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048'],
        ]);

        $user = $request->user();
        $file = $request->file('avatar');

        if ($user->avatar_kind === 'upload' && $user->avatar_value !== null) {
            \Illuminate\Support\Facades\Storage::disk('public')
                ->delete('avatars/'.$user->avatar_value);
        }

        $filename = $user->id.'.'.$file->getClientOriginalExtension();
        $file->storeAs('avatars', $filename, 'public');

        $this->users->updateProfile($user, [
            'avatar_kind' => 'upload',
            'avatar_value' => $filename,
        ]);

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $deleted = $this->users->delete($user);

        if (! $deleted) {
            return Redirect::route('profile.edit')
                ->with('error', 'No se puede eliminar la cuenta: tiene comunicaciones registradas.');
        }

        return Redirect::to('/');
    }
}
