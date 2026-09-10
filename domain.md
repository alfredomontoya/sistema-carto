# Lógica de Negocio — Carto

Fuente canónica de la lógica de negocio del sistema de comunicaciones Carto.
Este archivo es el *single source of truth* del dominio; `AGENTS.md`, el skill `carto` y `requirements.md` apuntan aquí.

## Visión general

Sistema para gestionar comunicaciones internas (`ci`) y oficios externos (`of`) con numeración correlativa por área de trabajo. Cada comunicación pertenece al usuario que la creó, se origina en su puesto/área y recibe un número que no se reutiliza jamás.

- **Usuarios** → se asignan a un **puesto** → el puesto pertenece a un **área**.
- **Áreas** = departamentos reales, organizados en un árbol recursivo.
- **Puestos** = cargos dentro de un área.
- **Comunicaciones** = documentos con correlativo, creados por un usuario desde su puesto actual.

## Roles y permisos (Spatie)

- **administrador**: gestiona usuarios (alta, roles, reset de contraseña), áreas, puestos y ajustes de marca. También crea comunicaciones y pertenece a un puesto.
- **usuario**: inicia sesión, consulta/actualiza su perfil, cambia su contraseña y avatar, crea y edita comunicaciones propias.
- Permisos Spatie: `manage users`, `manage areas`, `manage settings` — solo rol `administrador`.
- **No existe** registro público ni recuperación de contraseña: solo el administrador crea cuentas y resetea contraseñas.

## Usuarios

- Login y alta usan solo el **usuario** (ej. `amontoya`); el correo completo se deriva como `usuario@USER_DOMAIN` (`config/auth.php` `user_domain`, default `carto.com`). `UserService::emailFor()` centraliza la derivación.
- Usuarios desactivados (`is_active = false`) no pueden iniciar sesión.
- **Reset de contraseña** (admin): actualiza contraseña e invalida las sesiones existentes (`sessions` del usuario). `UserService::resetPassword`.
- **Desactivar** un usuario también invalida sus sesiones. `UserService::toggleActive`.
- **Eliminar** un usuario se bloquea si creó comunicaciones (para nunca perder correlativos por cascada). `UserService::delete`.

## Áreas y puestos

- Áreas = departamentos (árbol recursivo, raíz CARTOGRAFIA en semilla).
- Puestos = cargos dentro de un área (`positions.area_id`, único `area_id+code`). Un puesto pertenece a una sola área; un área tiene muchos puestos.
- El **área del usuario** se deriva de su **puesto actual**.
- No se puede eliminar un área con hijos o puestos; no se puede eliminar un puesto con asignaciones (historial). No se permiten ciclos al mover subáreas.
- CRUD de puestos en `/admin/areas` (rutas `admin/positions.*`, permiso `manage areas`).

### Historial de asignaciones (`position_user`)

- La pertenencia se registra en `position_user` con `started_at` / `ended_at`; el **puesto actual** es la asignación con `ended_at = NULL`.
- Modelo:
  - `User::currentAssignment` — HasOne (asignación actual).
  - `User::currentPosition` — HasOneThrough.
  - `User::currentArea` — **accesor** que lee `currentAssignment.position.area`. **NO es una relación**: cargar con `currentAssignment.position.area`.
- **Cambio de puesto**: `UserService::assignPosition` cierra la asignación anterior (`ended_at = now()`) y crea una nueva. Asignar el mismo puesto es no-op.
- **Quitar puesto**: `UserService::removeFromCurrentPosition` marca `ended_at` sin borrar el historial.
- ⚠️ **Gotcha**: siempre consultar la asignación actual fresca con `$user->currentAssignment()->first()`. La relación cacheada (`$user->currentAssignment`) causa asignaciones duplicadas.

## Comunicaciones / Oficios

- Un usuario crea una comunicación interna (`ci`) u oficio externo (`of`).
- Campos:
  - **Área**: la del puesto del usuario logueado (no editable; puede diferir del área de numeración).
  - **Puesto**: el del usuario al crear (se guarda `position_id`; si cambia de puesto después, la comunicación conserva el puesto original).
  - **Referencia**: texto libre (input de una línea).
  - **Remitente**: nombre y puesto del usuario logueado (no editable).
  - **Destinatario**: nombre y puesto (buscador de usuarios internos + texto libre). Ambos obligatorios.
  - **Área destino**: opcional siempre (puede quedar vacía), con doble modalidad — selección de un área registrada (autocomplete `/areas/buscar`, top 10, navegable con ↑/↓/Enter/click, limpiable a nulo) o nombre en texto libre. Se guarda `area_destino_id` (FK nullable) + `area_destino_nombre`. Sin área registrada, apunta al área raíz `OTRO` (creada por semilla y por demanda): con texto libre se conserva el texto, vacía se etiqueta `OTRO`.
  - **Adjunto**: opcional, PDF, Word o imagen.
- **Estado**: `activo` o `anulado`. **Anular no libera el número**.
- **Solo el creador o un administrador puede editar/anular** una comunicación mientras esté activa (`CommunicationPolicy::edit`).
- La anulación deja la comunicación consultable (histórico) pero oculta del listado por defecto.
- Búsqueda por remitente, destinatario, número, y rango de fechas; filtros por tipo, estado y área.

## Correlativo

- **Formato**: `prefijo.codigo_area.secuencia/año` (ej. `ci.carto.0001/2026`).
- Prefijos `ci`/`of` son constantes `TYPE_INTERNAL`/`TYPE_EXTERNAL` (en `NumberSequenceService` y `Communication`).
- El formato lo genera `NumberSequenceService::format()` (código de área en minúsculas, secuencia con 4 dígitos).
- `NumberSequenceService::parse()` extrae `type`/`area_code`/`sequence`/`year` desde un número (uso en auditoría/reparación).
- El contador vive en `area_number_counters` (unique `area_id+year+type`).
- **Nunca se reutiliza un número**, aunque la comunicación se anule.
- La asignación es atómica (`incrementAndGet`) para evitar duplicados concurrentes.

### Numeración configurable por área

- Cada área puede apuntar a otra vía `areas.numbering_area_id` (panel `/admin/areas`).
- Si está configurada, la comunicación usa la **secuencia y el prefijo del área apuntada** (ej. un área configurada para numerar como CARTOGRAFIA genera `ci.carto.000X`), pero `communications.area_id` conserva el **área real**.
- Resolución de **un solo nivel** (`NumberSequenceService::numberingArea()`): el área apuntada se usa tal cual, no se sigue resolviendo.
- La auto-referencia se descarta en `AreaService::update`.

### Reinicio de numeración

- `areas.reset_annually` (boolean, default `true`): controla si la secuencia reinicia en `0001` cada año (`ci.carto.0001/2027`) o continúa del año previo (`ci.carto.0011/2027`).
- El fallback "continuar" en `EloquentNumberCounterRepository` solo aplica cuando **no existe fila del año**.
- **Reinicio manual**: `POST admin/areas/{area}/reset-numbering`. Por defecto queda bloqueado si el año actual ya emitió números para esa área o para áreas que numeran como ella.
- El request valida `types` (`ci`/`of`, mínimo 1, Form Request `ResetAreaNumberingRequest`) y acepta `force` (switch "Reiniciar de todos modos" en `/admin/areas`).
- Con `force=true` reinicia igual conservando las comunicaciones: los nuevos correlativos **pueden duplicar** números ya emitidos (`communications.number` ya no es única; solo índice).
- `NumberSequenceService::resetForYear` / `resetAll` resetean contadores.

## Marca y tema

- Defaults en `config/brand.php` y `config/avatars.php`.
- La pantalla admin (`/admin/settings`) solo expone el **nombre del sistema** (`brand.app_name`); colores, logo y favicon guardados previamente se siguen aplicando pero ya no son editables desde la UI.
- Variables CSS de marca: `--brand-primary`, `--brand-secondary`; en runtime `BrandThemeProvider` las aplica desde las props compartidas `brand`.
- El color primario se aplica como acento de la interfaz (botones, enlaces, selección activa) y al progreso de navegación Inertia.
- `BrandSettingsService` cachea la marca (`brand.settings`, TTL 1 hora) para evitar consultas repetidas por request; `update()` invalida la caché con `Cache::forget`.

## Portabilidad

- Toda la lógica de negocio vive detrás de **repositorios e interfaces** (`app/Repositories/Contracts` + `app/Repositories/Eloquent`), lo que permite migrar la persistencia (MySQL, SQL Server, etc.) sin tocar servicios/controladores.
- La base de desarrollo es SQLite; `php artisan migrate:fresh --seed` debe funcionar siempre.

## Datos de ejemplo (seeders)

- Admin: `admin` / `password` (login por usuario; correo derivado `admin@carto.com` según `USER_DOMAIN`). Variables del seeder: `ADMIN_USERNAME` / `ADMIN_EMAIL` / `ADMIN_PASSWORD`.
- Área semilla: CARTOGRAFIA (raíz).
- Puestos semilla: JEFE, TECNICO, ABOGADO, SECRETARIA, ASISTENTE. Admin asignado al puesto JEFE.

## Invariantes clave (checklist)

- Anular no libera el número; los correlativos nunca se reutilizan.
- `communications.area_id` guarda el área real, no el área de numeración.
- El área destino es opcional en `ci` y `of`; puede quedar vacía, ser área registrada o texto libre.
- `User::currentArea` es accesor, no relación: cargar con `currentAssignment.position.area`.
- Siempre consultar la asignación actual con `$user->currentAssignment()->first()` (no la relación cacheada).
- Eliminar un usuario con comunicaciones creadas está bloqueado.
- Resetear contraseña o desactivar un usuario invalida sus sesiones.
