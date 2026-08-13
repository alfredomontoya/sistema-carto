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

Toda la lógica de negocio (correlativo y numeración, áreas y puestos, asignaciones, comunicaciones, marca, datos de ejemplo) está documentada en **[`domain.md`](./domain.md)** — fuente canónica. Consúltalo al tocar el dominio.

Gotchas críticos que evitan bugs (resumen rápido):

- `User::currentArea` es **accesor**, no relación: cargar con `currentAssignment.position.area`.
- Consultar siempre la asignación actual fresca: `$user->currentAssignment()->first()` (la relación cacheada causa asignaciones duplicadas).
- `communications.area_id` guarda el área real, no el área de numeración (`numbering_area_id`).
- Anular no libera el número; los correlativos nunca se reutilizan.

## Datos de ejemplo

- Admin: `admin` / `password` (login por usuario; correo derivado `admin@carto.com` según `USER_DOMAIN`). Variables del seeder: `ADMIN_USERNAME` / `ADMIN_EMAIL` / `ADMIN_PASSWORD`.
- Usuarios: el login y el alta usan solo el usuario (ej. `amontoya`); el correo completo se deriva como `usuario@USER_DOMAIN` (`config/auth.php` `user_domain`, default `carto.com`). `UserService::emailFor()` centraliza la derivación.
- Área semilla: CARTOGRAFIA (raíz); puestos semilla: JEFE, TECNICO, ABOGADO, SECRETARIA, ASISTENTE. Admin asignado al puesto JEFE.
