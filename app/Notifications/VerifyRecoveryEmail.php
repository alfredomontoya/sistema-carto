<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class VerifyRecoveryEmail extends Notification
{
    use Queueable;

    public function __construct(
        private readonly User $user,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = URL::temporarySignedRoute(
            'recovery.verify',
            now()->addHours(24),
            ['user' => $this->user->id],
        );

        return (new MailMessage)
            ->subject('Confirma tu correo de recuperación')
            ->greeting("Hola {$this->user->name},")
            ->line('Registraste este correo para recuperar tu contraseña en el sistema de comunicaciones.')
            ->action('Confirmar correo', $url)
            ->line('El enlace vence en 24 horas. Si no fuiste tú, ignora este mensaje.');
    }
}
