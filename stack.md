# Stack Tecnológico

## Backend

- **Laravel** 13.8.0 (PHP 8.3.28, Composer 2.9.8)
- **Sanctum** — autenticación (sesiones web)
- **spatie/laravel-permission** — roles y permisos (`administrador`, `usuario`; permisos `manage users`, `manage areas`, `manage settings`)
- **Base de datos**: SQLite en desarrollo (`config/database.php` conecta por driver). Migrable a MySQL/SQL Server mediante la capa de repositorios.

### Arquitectura backend

- **Repositorios** en `app/Repositories/Contracts` + implementaciones `app/Repositories/Eloquent`; bindings en `app/Providers/RepositoryServiceProvider` (registrado en `bootstrap/providers.php`).
- **Servicios** en `app/Services` (lógica de negocio):
  - `NumberSequenceService` — formato y asignación de correlativos (`prefijo.codigo_area.secuencia/año`).
  - `CommunicationService` — creación/edición/anulación de comunicaciones y archivos.
  - `UserService` (asignación de puestos con historial), `AreaService`, `PositionService`, `BrandSettingsService`.
- **Repositorios**: `AreaRepository`, `PositionRepository`, `UserRepository`, `CommunicationRepository`, `NumberCounterRepository`, `SettingsRepository`, `RoleRepository`.
- **Resources**: `UserResource`, `AreaResource`, `CommunicationResource`.
- **Políticas**: `CommunicationPolicy` (solo el creador edita; anular = editar activa).
- **Middleware** `HandleInertiaRequests` comparte `auth.user`, `brand` y `flash`.

### Rutas

- `/dashboard`, `/profile` (info, avatar, contraseña)
- `/comunicaciones` (index, create, edit, annul, download)
- `/admin/users` (index, create, edit, reset password)
- `/admin/areas` (índice con árbol recursivo y CRUD)
- `/admin/positions` (CRUD de puestos, permiso `manage areas`)
- `/admin/settings` (marca)
- `/usuarios/buscar` — autocomplete JSON (auth)

## Frontend

- **React 19 + TypeScript estricto** (`strict: true` en tsconfig)
- **Inertia.js 2** con **Vite 8** (`@vitejs/plugin-react` 6, `laravel-vite-plugin` 3)
- **Tailwind CSS v4** (config por CSS en `resources/css/app.css`, sin `tailwind.config.js`)
- **shadcn/ui** (Radix UI + CVA + tailwind-merge): button, input, label, textarea, card, badge, dialog, select, dropdown-menu, avatar, checkbox, table, separator, tabs, tooltip, skeleton, switch, breadcrumb
- **lucide-react** — iconos
- **sonner** — notificaciones toast
- **ziggy-js** — helper `route()` tipado

### Estructura frontend

- `resources/js/components` — UI shadcn, layout (AppLayout, Sidebar minimizable, Header, Footer), `UserAvatar`, `AvatarPicker`, `FlashMessages`, `DataTablePagination`, `PageHeader`, `RecipientInput`.
- `resources/js/lib` — `area-tree` (árbol a opciones + `positionOptions`), `app-name`, `navigation`, `utils` (cn).
- `resources/js/pages` — Dashboard, Auth/Login, Profile, Admin/{Users,Areas,Settings}, Communications.
- `resources/js/types` — tipos compartidos (`AreaNode.positions`, `PositionData`, `UserData.current_position`, `CommunicationData.position`) y augment de `PageProps` (`auth.user`, `brand`, `flash`).

## Marca y tema

- Variables CSS de marca: `--brand-primary`, `--brand-secondary` (en `@theme` con defaults). Se sobreescriben en runtime por `BrandThemeProvider` leyendo `brand` de props compartidas.
- Tema claro/oscuro con `ThemeProvider` (persistido en localStorage).

## Comandos

- `npm run dev` — Vite dev
- `npm run build` — `tsc && vite build`
- `npm run lint` — `tsc --noEmit`
- `php artisan serve` — servidor de desarrollo
- `php artisan migrate:fresh --seed` — bases + datos de ejemplo
