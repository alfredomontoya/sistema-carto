# Carto

Sistema de información para la gestión de **comunicaciones internas (`ci`)** y **oficios externos (`of`)** con numeración correlativa por área de trabajo.

**Stack**: Laravel 13 + Inertia 2 + React 19 + TypeScript (estricto) + Tailwind CSS v4 + shadcn/ui.

## Documentación

| Archivo | Contenido |
| --- | --- |
| [`domain.md`](./domain.md) | **Lógica de negocio** (fuente canónica): correlativo y numeración, áreas y puestos, asignaciones, comunicaciones, marca, datos de ejemplo. |
| [`stack.md`](./stack.md) | Stack tecnológico, arquitectura backend (repositorios/servicios), estructura frontend y comandos. |
| [`requirements.md`](./requirements.md) | Requisitos funcionales del sistema. |
| [`AGENTS.md`](./AGENTS.md) | Guía para agentes de IA: comandos y convenciones de backend/frontend. |

## Puesta en marcha

```bash
composer install
npm install
cp .env.example .env

php artisan key:generate
php artisan migrate:fresh --seed

npm run dev       # Vite dev
php artisan serve # servidor de desarrollo
```

Credenciales de ejemplo (ver `domain.md` → Datos de ejemplo): `admin` / `password`.

## Verificación

- `npm run lint` — typecheck TypeScript (`tsc --noEmit`)
- `npm run build` — `tsc && vite build`
- `php artisan migrate:fresh --seed` — bases + datos de ejemplo

## Rutas principales

- `/dashboard` — inicio
- `/comunicaciones` — listado, creación y edición de comunicaciones
- `/admin/users` — gestión de usuarios
- `/admin/areas` — áreas (árbol), puestos y reinicio de numeración
- `/admin/settings` — ajustes de marca
- `/profile` — perfil, avatar y contraseña
