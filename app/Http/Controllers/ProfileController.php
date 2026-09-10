<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\PasswordRecoveryService;
use App\Services\UserService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(
        private readonly UserService $users,
        private readonly PasswordRecoveryService $recovery,
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
            'password_days_left' => $this->users->passwordDaysLeft($request->user()),
            'password_expiry_days' => $this->users->passwordExpiryDays(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['recovery_email']);

        $this->users->updateProfile($request->user(), $data);

        if ($request->has('recovery_email')) {
            $this->recovery->setRecoveryEmail($request->user(), $request->input('recovery_email'));
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

        if (Hash::check($request->input('password'), $request->user()->password)) {
            return Redirect::route('profile.edit', ['tab' => 'password'])
                ->withErrors(['password' => 'La nueva contraseña debe ser diferente a la actual.']);
        }

        $this->users->updatePassword($request->user(), $request->input('password'));
        $this->users->setMustChangePassword($request->user(), false);

        return Redirect::route('profile.edit')->with('success', 'Contraseña actualizada correctamente.');
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

        $filename = $user->id.'_'.time().'_'.Str::random(6).'.'.strtolower($file->getClientOriginalExtension());
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
