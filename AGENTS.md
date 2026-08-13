# AGENTS.md

Guía para agentes que trabajan en este repositorio.

## Comandos de verificación

- **Typecheck / lint**: `npm run lint` (= `tsc --noEmit`)
- **Build**: `npm run build` (= `tsc && vite build`)
- **Dev**: `npm run dev` + `php artisan serve`
- **Migraciones y seeds**: `php artisan migrate:fresh --seed`
- **Tinker**: `php artisan tinker`

## Convenciones backend

- Los controladores NO tocan Eloquent directamente: usan repositorios vía interfaces (inyectadas). Los servicios orquestan la lógica de negocio.
- Nuevos métodos de consulta → añadir al contrato (`app/Repositories/Contracts`) y a la implementación Eloquent, con paginación y filtros cuando aplique.
- Modelos: UUID (`HasUuids`). No usar `$request->user()` como clave `user` en resources; usar `$request->user()?->id`.
- Reglas de validación en Form Requests; permisos en Policies y en el controlador con `authorize`.
- Cambios de esquema van en nuevas migraciones; `migrate:fresh --seed` debe funcionar siempre.

## Convenciones frontend

- TypeScript estricto. Correr `npm run lint` antes de terminar.
- Importar con alias `@/` SIEMPRE en minúsculas: `@/components/...`, `@/layouts/...`, `@/pages/...`, `@/lib/...`, `@/types`.
  En Windows el sistema de archivos no distingue mayúsculas pero TypeScript sí; mezclar `@/Components` y `@/components` rompe `tsc` (TS1149/TS1261).
- Componentes UI shadcn en `resources/js/components/ui/*`. Prefabricados: button, input, label, textarea, card, badge, dialog, select, dropdown-menu, avatar, checkbox, table, separator, tabs, tooltip, skeleton, switch, breadcrumb.
- Formularios con `useForm` de `@inertiajs/react`; errores de servidor en `errors.<campo>`.
- Subida de archivos: `FormData` + `forceFormData: true`.
- No añadir comentarios al código salvo que se pida.

## Dominio

- **Correlativo**: `prefijo.codigo_area.secuencia/año` (ej. `ci.carto.0001/2026`). Prefijos `ci`/`of` son constantes en `NumberSequenceService` (`TYPE_INTERNAL`/`TYPE_EXTERNAL`). Lo genera `NumberSequenceService`; nunca reutiliza números aunque se anule.
- **Numeración configurable por área**: cada área puede apuntar a otra vía `areas.numbering_area_id` (panel `/admin/areas`). Si está configurada, la comunicación usa la secuencia y el prefijo del área apuntada (ej. un área configurada para numerar como CARTOGRAFIA genera `ci.carto.000X`), pero `communications.area_id` conserva el área real. Resolución de un solo nivel (`numberingArea()` en `NumberSequenceService`); la auto-referencia se descarta en `AreaService::update`. Cada área tiene su propia secuencia por defecto (ej. `ci.taes.0001/2026`).
- **Reinicio de numeración**: `areas.reset_annually` (boolean, default `true`) controla si la secuencia reinicia en `0001` cada año (`ci.carto.0001/2027`) o continúa del año previo (`ci.carto.0011/2027`). El reinicio manual es `POST admin/areas/{area}/reset-numbering`; queda bloqueado si el año actual ya emitió números para esa área o para áreas que numeran como ella. El contador vive en `area_number_counters` (unique `area_id+year+type`); el fallback "continuar" en `EloquentNumberCounterRepository` solo aplica cuando no existe fila del año.
- **Estado**: una comunicación es `activo` o `anulado`. Anular no libera el número. Solo el creador edita.
- **Áreas y puestos**: áreas = departamentos (árbol recursivo); puestos = cargos dentro de un área (`positions.area_id`, único `area_id+code`). El área del usuario se deriva de su puesto actual. Historial en `position_user` (actual = `ended_at NULL`); `User::currentAssignment` (HasOne), `currentPosition` (HasOneThrough), `currentArea` (accesor que lee `currentAssignment.position.area` — NO es relación; cargar con `currentAssignment.position.area`).
- **Asignación de puesto**: `UserService::assignPosition` / `removeFromCurrentPosition`; siempre consultar la asignación actual fresca (`$user->currentAssignment()->first()`) — la relación cacheada causa asignaciones duplicadas.
- **Marca**: defaults en `config/brand.php` y `config/avatars.php`; la pantalla admin (`/admin/settings`) los sobreescribe en BD.

## Datos de ejemplo

- Admin: `admin@example.com` / `password` (variables `ADMIN_EMAIL` / `ADMIN_PASSWORD` del seeder).
- Área semilla: CARTOGRAFIA (raíz); puestos semilla: JEFE, TECNICO, ABOGADO, SECRETARIA, ASISTENTE. Admin asignado al puesto JEFE.
