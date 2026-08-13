---
name: carto
description: Workflows y convenciones para el sistema de comunicaciones Carto (Laravel + Inertia + React + TS). Usar al tocar el backend (repositorios/servicios/seeders), el frontend (páginas/componentes), el correlativo de comunicaciones o la marca.
---

# Skill Carto

Toda la **lógica de negocio** (correlativo y numeración, áreas y puestos, asignaciones, comunicaciones, marca, datos de ejemplo) está documentada en [`domain.md`](../../../domain.md) — fuente canónica. Consúltalo al tocar el dominio.

## Verificación

- `npm run lint` (tsc --noEmit) antes de terminar cualquier cambio.
- `php artisan migrate:fresh --seed` debe funcionar; ejecutar tras crear/modificar migraciones o seeders.

## Backend

- Controladores inyectan repositorios (interfaces). Prohibido Eloquent en controladores.
- Consulta nueva → método en `app/Repositories/Contracts/*` + implementación `Eloquent/*` + binding existente en `RepositoryServiceProvider`.
- Lógica de negocio → servicios (`app/Services`). El correlativo es competencia de `NumberSequenceService` (`format/current/next`).
- Modelos: `HasUuids`. En resources usar `$request->user()?->id`, nunca `$request->user()` como clave `user`.
- Validación en Form Requests; autorización en Policies (ej. `CommunicationPolicy::update` = solo creador y estado activo).

## Frontend

- Imports `@/` en minúsculas siempre: `@/components`, `@/layouts`, `@/pages`, `@/lib`, `@/types`. Mayúsculas rompen `tsc` en Windows.
- UI shadcn en `resources/js/components/ui` (prefabricados: button, input, label, textarea, card, badge, dialog, select, dropdown-menu, avatar, checkbox, table, separator, tabs, tooltip, skeleton, switch, breadcrumb).
- Formularios: `useForm` de `@inertiajs/react`; errores en `errors.<campo>`. Archivos: `FormData` + `forceFormData: true`.
- Marca en runtime: `BrandThemeProvider` aplica `--brand-primary` / `--brand-secondary` desde props `brand`; los default viven en `config/brand.php`.

## Gotchas críticos (resumen de domain.md)

- `User::currentArea` es **accesor**, no relación: cargar con `currentAssignment.position.area`.
- Consultar siempre la asignación actual fresca: `$user->currentAssignment()->first()`.
- `communications.area_id` guarda el área real, no el área de numeración (`numbering_area_id`).
- Anular no libera el número; los correlativos nunca se reutilizan.
