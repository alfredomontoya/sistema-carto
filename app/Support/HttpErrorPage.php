<?php

namespace App\Support;

class HttpErrorPage
{
    /**
     * @return array{status: int, title: string, message: string, action: string, action_url: string}
     */
    public static function for(int $status): array
    {
        return match ($status) {
            403 => [
                'status' => 403,
                'title' => 'No tienes acceso a esta sección',
                'message' => 'Tu usuario no cuenta con los permisos necesarios para ver este contenido. Si consideras que es un error, solicita acceso al administrador del sistema.',
                'action' => 'Volver',
                'action_url' => '/dashboard',
            ],
            404 => [
                'status' => 404,
                'title' => 'No encontramos esta página',
                'message' => 'El enlace puede estar roto o el registro que buscas ya no existe. Revisa la dirección o regresa al panel principal.',
                'action' => 'Volver',
                'action_url' => '/dashboard',
            ],
            419 => [
                'status' => 419,
                'title' => 'Tu sesión expiró',
                'message' => 'Por seguridad cerramos tu sesión por inactividad. Inicia sesión nuevamente para continuar donde estabas.',
                'action' => 'Iniciar sesión',
                'action_url' => '/login',
            ],
            429 => [
                'status' => 429,
                'title' => 'Demasiadas solicitudes',
                'message' => 'Realizaste varias solicitudes en muy poco tiempo. Espera unos segundos y vuelve a intentarlo.',
                'action' => 'Volver',
                'action_url' => '/dashboard',
            ],
            500 => [
                'status' => 500,
                'title' => 'Ocurrió un error inesperado',
                'message' => 'El sistema no pudo completar la operación. Intenta nuevamente; si el problema persiste, contacta al administrador.',
                'action' => 'Volver',
                'action_url' => '/dashboard',
            ],
            503 => [
                'status' => 503,
                'title' => 'Sistema en mantenimiento',
                'message' => 'Estamos realizando tareas de mantenimiento programadas. Vuelve a intentarlo en unos minutos.',
                'action' => 'Reintentar',
                'action_url' => '/',
            ],
            default => [
                'status' => $status,
                'title' => 'No se pudo completar la solicitud',
                'message' => 'El sistema devolvió un estado inesperado. Regresa al panel principal e intenta nuevamente.',
                'action' => 'Volver',
                'action_url' => '/dashboard',
            ],
        };
    }
}
