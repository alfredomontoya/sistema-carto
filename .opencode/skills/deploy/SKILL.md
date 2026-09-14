---
name: deploy
description: Despliegue del sistema Carto a WAMP (c:/wamp64/www) con reporte por paso. Usar al desplegar a producción, sincronizar archivos al servidor local, correr migraciones o ante "deploy", "despliegue", "pasar a producción", "publicar".
---

# Skill Deploy — Carto a WAMP

Despliegue del repo (`D:\progra\carto`) al directorio WAMP de producción
(`C:\wamp64\www\carto` por defecto). El agente orquesta; la mecánica vive en
[`scripts/sync.ps1`](scripts/sync.ps1).

## Invocación

Comando `/deploy` (ver `.opencode/command/deploy.md`). Flags:

- `--dry-run`: solo preflight + lista de acciones, no ejecuta nada.
- `--skip-build`: reutiliza `public/build` existente (solo-frontend ya compilado).
- `--no-maintenance`: omite `down`/`up` (cambios sin migración).
- `--target <ruta>`: destino alternativo a `C:\wamp64\www\carto`.

## Preflight (falla rápido, sin tocar nada)

1. `git status --short` limpio en origen. Si hay cambios, abortar e indicarlo.
2. PHP del WAMP existe y es >= 8.3 (`C:\wamp64\bin\php\php8.3*\php.exe`).
3. `.env` existe en destino. **Jamás se copia ni sobrescribe.**
4. `APP_DEBUG=false` en el `.env` de destino. Si es `true`, abortar.
5. Espacio libre en C: (> 1 GB).

## Pasos (en orden, stop al primer fallo)

1. **Respaldo BD en destino**: `php artisan app:backup-database`. No negociable antes de migrar.
2. **Build en origen** (una sola vez): `npm ci && npm run build`. Al destino viajan artefactos.
3. **Sync con robocopy `/MIR`** origen → destino, excluyendo directorios (`.git/`, `node_modules/`, `tests/`, `.opencode/`, `storage/app/backups`, `storage/app/public`, `storage/framework`, `storage/logs`) y archivos (`.env*`, `*.log`, `*.md`, `phpunit.xml`, `vite.config.js`, `tsconfig.json`, `.phpunit.result.cache`, `public/hot`). El mirror borra en destino lo que no existe en origen; sin estas exclusiones se perderían respaldos, avatares y sesiones.
4. **Limpieza del destino (`clean-target`)**: borra `public/hot` obsoleto si existe (un `hot` fósil hace que `@vite` sirva URLs del dev server `:5173` aunque el build esté correcto).
4. **Vendor**: si el destino tiene composer, `composer install --no-dev --optimize-autoloader` allá con reintento ×3 (los bloqueos de archivos de Apache/antivirus en Windows hacen fallar el primer intento); si no, se sincroniza `vendor/` desde origen.
5. **Mantenimiento** (salvo `--no-maintenance`): `php artisan down` **después** del sync (el mirror borraría el flag `storage/framework/down` si se hace antes).
6. **Respaldo BD** (tras el sync, antes de migrar) + **Migraciones**: `php artisan migrate --force`. **Prohibido `migrate:fresh`, `migrate:reset` y cualquier `db:seed` completo en producción** (crearían 500 comunicaciones demo y 25 usuarios con clave `password`). Solo seeders puntuales si se piden explícito (`RolesAndPermissionsSeeder`, `AdminUserSeeder` con `ADMIN_PASSWORD` seguro).
7. **Cachés**: `php artisan config:cache && php artisan route:cache && php artisan view:cache`.
8. **Storage**: `php artisan storage:link` (idempotente; crea el enlace si falta).
9. **Levantar mantenimiento**: `php artisan up`.
10. **Smoke test** (después de `up`, pues `/login` da 503 en mantenimiento): `GET /up` → 200 y `GET /login` → 200. Base por defecto `http://localhost:8080` (vhost `splaadm23p-10:8080` → `www/carto/public`); override con `--target`/URL según el servidor.

## Higiene de producción (qué nunca debe haber en destino)

- `node_modules/`, `tests/`, `.opencode/`, `public/hot`, `*.log`, docs `*.md`, `phpunit.xml`, configs de build (`vite.config.js`, `tsconfig.json`).
- El mirror no retira lo ya sincronizado en deploys viejos: si aparecen restos históricos, limpieza manual única con lista confirmada antes de borrar.
- Rotación de crecimiento sin cota: los backups rotan con `--keep=7`; `storage/logs/laravel.log` no rota solo — programar `php artisan log:clear` periódico o rotación externa.

## PowerShell (Windows)

- Encadenar dependientes con `cmd1; if ($?) { cmd2 }` (`&&` no existe aquí).
- Rutas con espacios entrecomilladas; timeouts generosos en build/migrate.
- El `php` a usar es el del WAMP, no el del PATH global.

## Reporte (chat + log, siempre)

Por cada paso: `✓/✗` + duración + últimas líneas de salida. Al final:

- Resumen con total de pasos, tiempo total y hash del commit desplegado.
- Ante fallo: stop inmediato + sección "Reversión" (restaurar backup indicado + `php artisan config:clear`).
- Log en `storage/logs/deploy-YYYY-MM-DD_HH-mm-ss.log` del **origen** (gitignored por `*.log`).
- **Redactar secretos** del log y del chat: patrones `*_PASSWORD`, `*_SECRET`, `*_KEY`, `*_TOKEN`.

## Aprovisionamiento único del servidor (no corre en cada deploy)

- Vhost Apache → `C:/wamp64/www/carto/public`, HTTPS activo.
- `TRUSTED_PROXIES` si hay proxy/CDN delante.
- Tarea programada Windows cada minuto: `schedule:run` (respaldo diario + colas).
- Worker supervisado para `queue:work` (QUEUE_CONNECTION=database).
- Copias de `storage/app/backups` fuera del servidor.
