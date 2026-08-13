---
name: carto
description: Workflows y convenciones para el sistema de comunicaciones Carto (Laravel + Inertia + React + TS). Usar al tocar el backend (repositorios/servicios/seeders), el frontend (páginas/componentes), el correlativo de comunicaciones o la marca.
---

# Skill Carto

## Verificación

- `npm run lint` (tsc --noEmit) antes de terminar cualquier cambio.
- `php artisan migrate:fresh --seed` debe funcionar; ejecutar tras crear/modificar migraciones o seeders.

## Backend

- Controladores inyectan repositorios (interfaces). Prohibido Eloquent en controladores.
- Consulta nueva → método en `app/Repositories/Contracts/*` + implementación `Eloquent/*` + binding existente en `RepositoryServiceProvider`.
- Lógica de negocio → servicios (`app/Services`). El correlativo es competencia de `NumberSequenceService` (`format/current/next`).
- Modelos: `HasUuids`. En resources usar `$request->user()?->id`, nunca `$request->user()` como clave `user`.
- Validación en Form Requests; autorización en Policies (ej. `CommunicationPolicy::update` = solo creador y estado activo).

## Correlativo

- Formato: `prefijo.codigo_area.secuencia/año` (ej. `ci.carto.0001/2026`). Prefijos `ci` y `of` en constantes de `NumberSequenceService` (`TYPE_INTERNAL`/`TYPE_EXTERNAL`).
- Contador por área/año/tipo (`area_number_counters`). Nunca reutilizar un número, aunque la comunicación se anule.
- Numeración configurable por área (`areas.numbering_area_id`): si un área apunta a CARTOGRAFIA usa la secuencia y el prefijo de CARTO (`ci.carto.000X`), pero `communications.area_id` guarda el área real. Resolución de un solo nivel en `NumberSequenceService::numberingArea()`; `AreaService::update` descarta la auto-referencia.
- Reinicio por año: `areas.reset_annually` (default `true`); si es `false` la secuencia continúa del año previo (fallback en `EloquentNumberCounterRepository` solo si no existe fila del año). Reinicio manual: `POST admin/areas/{area}/reset-numbering` (bloqueado si el año actual ya emitió números).

## Áreas y puestos

- Áreas = departamentos (árbol recursivo). Puestos = cargos dentro de un área (`positions.area_id`, único `area_id+code`). El área del usuario se deriva de su puesto actual.
- Historial de asignaciones en `position_user` (actual = `ended_at NULL`). `User::currentAssignment` (HasOne), `currentPosition` (HasOneThrough), `currentArea` es un ACCESOR (`currentAssignment.position.area`), no una relación: cargar con `currentAssignment.position.area`.
- `UserService::assignPosition` / `removeFromCurrentPosition`: usar `$user->currentAssignment()->first()` (fresca); la relación cacheada causa asignaciones duplicadas.
- CRUD de puestos en el panel `/admin/areas` (rutas `admin/positions.*`, permiso `manage areas`).

## Frontend

- Imports `@/` en minúsculas siempre: `@/components`, `@/layouts`, `@/pages`, `@/lib`, `@/types`. Mayúsculas rompen `tsc` en Windows.
- UI shadcn en `resources/js/components/ui` (prefabricados: button, input, label, textarea, card, badge, dialog, select, dropdown-menu, avatar, checkbox, table, separator, tabs, tooltip, skeleton, switch, breadcrumb).
- Formularios: `useForm` de `@inertiajs/react`; errores en `errors.<campo>`. Archivos: `FormData` + `forceFormData: true`.
- Marca en runtime: `BrandThemeProvider` aplica `--brand-primary` / `--brand-secondary` desde props `brand`; los default viven en `config/brand.php`.

## Flujos típicos

- Alta de usuario: pantalla admin → `UserForm` (rol, puesto, contraseña inicial, activo).
- Cambio de puesto: `UserService::assignPosition` cierra la asignación anterior con `ended_at` y crea una nueva en `position_user`.
- Crear comunicación: se muestra el "siguiente número" antes de guardar; el guardado asigna y persiste el número y el `position_id` del creador.
- Ajustes de marca: `SettingsController` guarda en BD; `BrandSettingsService` prioriza BD sobre `config/brand.php`.
