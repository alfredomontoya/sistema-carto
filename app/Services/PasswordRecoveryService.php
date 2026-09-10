<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\RecoveryResetLink;
use App\Notifications\VerifyRecoveryEmail;
use App\Repositories\Contracts\SettingsRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class PasswordRecoveryService
{
    private const TOKEN_TTL_MINUTES = 60;

    public function __construct(
        private readonly UserService $users,
        private readonly SettingsRepository $settings,
    ) {}

    public function enabled(): bool
    {
        return (bool) $this->settings->get('security.password_recovery_enabled', false);
    }

    public function setEnabled(bool $value): void
    {
        $this->settings->set('security.password_recovery_enabled', $value);
    }

    /**
     * Register or change the user's personal recovery email. A different
     * address resets verification and triggers a confirmation mail.
     */
    public function setRecoveryEmail(User $user, ?string $email): void
    {
        $email = $email !== null && trim($email) !== ''
            ? Str::lower(trim($email))
            : null;

        if ($email === $user->recovery_email) {
            return;
        }

        $this->users->repository()->update($user, [
            'recovery_email' => $email,
            'recovery_email_verified_at' => null,
        ]);

        if ($email !== null) {
            Notification::route('mail', $email)->notify(new VerifyRecoveryEmail($user->fresh()));
        }
    }

    public function verifyRecoveryEmail(User $user): void
    {
        $this->users->repository()->update($user, [
            'recovery_email_verified_at' => now(),
        ]);
    }

    public function findForRecovery(string $email): ?User
    {
        $email = Str::lower(trim($email));

        return User::where('recovery_email', $email)
            ->whereNotNull('recovery_email_verified_at')
            ->where('is_active', true)
            ->first();
    }

    /**
     * Always silent: never reveal whether the address exists.
     */
    public function requestReset(string $email): void
    {
        $this->pruneExpired();

        $user = $this->findForRecovery($email);

        if ($user === null) {
            return;
        }

        DB::table('password_recovery_tokens')->where('email', $user->recovery_email)->delete();

        $token = Str::random(64);

        DB::table('password_recovery_tokens')->insert([
            'email' => $user->recovery_email,
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        Notification::route('mail', $user->recovery_email)->notify(
            new RecoveryResetLink($token, $user->recovery_email)
        );
    }

    /**
     * Reset the password from a valid token. Single use.
     */
    public function resetFromToken(string $email, string $token, string $password): bool
    {
        $this->pruneExpired();

        $email = Str::lower(trim($email));

        $record = DB::table('password_recovery_tokens')->where('email', $email)->first();

        if ($record === null || ! Hash::check($token, $record->token)) {
            return false;
        }

        $user = $this->findForRecovery($email);

        if ($user === null) {
            return false;
        }

        DB::table('password_recovery_tokens')->where('email', $email)->delete();

        $this->users->updatePassword($user, $password);
        $this->users->setMustChangePassword($user, false);
        $user->forceFill(['remember_token' => Str::random(60)])->save();
        DB::table('sessions')->where('user_id', $user->id)->delete();

        return true;
    }

    private function pruneExpired(): void
    {
        DB::table('password_recovery_tokens')
            ->where('created_at', '<', now()->subMinutes(self::TOKEN_TTL_MINUTES))
            ->delete();
    }
}
