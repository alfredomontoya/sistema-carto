<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RecoveryResetLink extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $token,
        private readonly string $email,
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
        $url = route('recovery.reset', ['token' => $this->token]).'?email='.urlencode($this->email);

        return (new MailMessage)
            ->subject('Recupera tu contraseña')
            ->greeting('Hola,')
            ->line('Recibimos una solicitud para restablecer tu contraseña de acceso al sistema de comunicaciones.')
            ->action('Restablecer contraseña', $url)
            ->line('El enlace vence en 60 minutos y solo puede usarse una vez. Si no fuiste tú, ignora este mensaje.');
    }
}
