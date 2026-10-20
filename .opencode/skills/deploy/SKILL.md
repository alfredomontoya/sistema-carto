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
- `--skip-git-sync`: no hace `fetch`/`merge` del repo local en el destino.
- `--target <ruta>`: destino alternativo a `C:\wamp64\www\carto`.

## Preflight (falla rápido, sin tocar nada)

1. `git status --short` limpio en origen. Si hay cambios, abortar e indicarlo.
2. Commit del origen **publicado en `origin/main`** (consulta `git ls-remote`). Si el remoto no responde, se avisa y se continúa; si responde y difiere, se avisa: el despliegue no queda respaldado en remoto.
3. PHP del WAMP existe y es >= 8.3 (`C:\wamp64\bin\php\php8.3*\php.exe`).
4. `.env` existe en destino. **Jamás se copia ni sobrescribe.**
5. `APP_DEBUG=false` en el `.env` de destino. Si es `true`, abortar.
6. Espacio libre en C: (> 1 GB).
7. Destino con `git status --short` limpio: si tiene cambios locales, el `merge --ff-only` fallaría y el destino quedaría inconsistente. Abortar.
8. Servicio Apache `wampapache64` disponible (si no, se avisa y se reinicia con `httpd.exe -k restart`).

## Pasos (en orden, stop al primer fallo)

1. **Respaldo BD en destino**: `php artisan app:backup-database`. No negociable antes de migrar.
2. **Build en origen** (una sola vez): `npm ci && npm run build`. Al destino viajan artefactos. Si `npm ci` falla por red pero existe `node_modules`, se avisa y se reutiliza el existente.
3. **`git-sync`**: `git fetch --no-tags <origen> main` + `git merge --ff-only FETCH_HEAD` **en el destino**. Traslada el historial y deja el árbol del destino consistente con su propio `HEAD`. Va **antes** del mirror porque `.git` está excluido del `/MIR`.
4. **Sync con robocopy `/MIR`** origen → destino, excluyendo directorios (`.git/`, `node_modules/`, `tests/`, `.opencode/`, `vendor/` si hay composer, `storage/app/backups`, `storage/app/public`, `storage/framework`, `storage/logs`) y archivos (`.env*`, `*.log`, `*.md`, `phpunit.xml`, `vite.config.js`, `tsconfig.json`, `.phpunit.result.cache`, `public/hot`). El mirror borra en destino lo que no existe en origen; sin estas exclusiones se perderían respaldos, avatares y sesiones.
5. **Limpieza del destino (`clean-target`)**: borra `public/hot` obsoleto si existe (un `hot` fósil hace que `@vite` sirva URLs del dev server `:5173` aunque el build esté correcto).
6. **Vendor**: si el destino tiene composer, `composer install --no-dev --optimize-autoloader` allá con reintento ×3 (los bloqueos de archivos de Apache/antivirus en Windows hacen fallar el primer intento); si no, se sincroniza `vendor/` desde origen. `composer audit --no-dev` se ejecuta después y **solo avisa** (no bloquea el deploy: hay avisos known en dependencias transitivas).
7. **Mantenimiento** (salvo `--no-maintenance`): `php artisan down` **después** del sync.
8. **Respaldo BD** (tras el sync, antes de migrar) + **Migraciones**: `php artisan migrate --force`. **Prohibido `migrate:fresh`, `migrate:reset` y cualquier `db:seed` completo en producción** (crearían 500 comunicaciones demo y 25 usuarios con clave `password`). Solo seeders puntuales si se piden explícito (`RolesAndPermissionsSeeder`, `AdminUserSeeder` con `ADMIN_PASSWORD` seguro). Luego `migrate:status` debe quedar sin pendientes.
9. **Cachés**: `php artisan optimize` (config + events + route + view). `php artisan queue:restart` para los workers.
10. **Storage**: `php artisan storage:link`. Si `public/storage` existe como **directorio real** (no como enlace), su contenido se mueve a `storage/app/public` y el directorio se elimina antes de crear el enlace; después se **verifica** que `public/storage` resuelve al mismo destino. Sin esto, los adjuntos y avatares se escriben en un sitio y se sirven de otro.
11. **Levantar mantenimiento**: `php artisan up`.
12. **Reinicio de Apache**: `Restart-Service wampapache64` (fallback `httpd.exe -k restart`). **Obligatorio** si `opcache.validate_timestamps=0`: sin reinicio, Apache sigue ejecutando el código anterior y el despliegue parece no haber hecho nada.
13. **Verificar commit**: `git rev-parse HEAD` en origen y destino deben coincidir.
14. **Smoke test** (después de `up` y del reinicio): `GET /up` → 200 y `GET /login` → 200. Base por defecto `http://localhost:8080` (vhost `splaadm23p-10:8080` → `www/carto/public`); override con `--target`/URL según el servidor.

## Higiene de producción (qué nunca debe haber en destino)

- `node_modules/`, `tests/`, `.opencode/`, `public/hot`, `*.log`, docs `*.md`, `phpunit.xml`, configs de build (`vite.config.js`, `tsconfig.json`).
- **Ojo**: `node_modules/`, `tests/`, `.opencode/`, `*.md`, `phpunit.xml`, `vite.config.js` y `tsconfig.json` están **excluidos del mirror**, y robocopy **no borra lo que está excluido**: siguen en destino indefinidamente. Excluir evita que se pierdan, no que se limpien. Para eliminarlos hace falta una limpieza manual con lista confirmada, o un paso explícito de borrado en el script.
- El mirror sí borra lo que ya no existe en el origen: por ejemplo, los `public/build/assets/*` con hash viejo (se regeneran en el paso 2) y `bootstrap/cache/routes-v7.php` (se regenera en el paso 9).
- Rotación de crecimiento sin cota: los backups rotan con `--keep=7`; `storage/logs/laravel.log` no rota solo — programar `php artisan log:clear` periódico o rotación externa.

## OPcache y PHP en producción

Con `opcache.validate_timestamps=0` (recomendado en producción) Apache **no vuelve a leer los ficheros PHP cambiados** hasta que se reinicia el servicio. Por eso el reinicio de Apache es un paso obligatorio del despliegue, no opcional.

Ajustes recomendados en el `php.ini` que usa Apache (`C:\wamp64\bin\apache\apache2.4.65\bin\php.ini`): xdebug comentado, `display_errors=Off`, `expose_php=Off`, `upload_max_filesize` por encima de 32M (la app valida adjuntos de hasta 10 MB), `memory_limit` holgado para los reportes PDF, `opcache.memory_consumption` >= 128 y `opcache.max_accelerated_files` >= 20000. El mismo `php.ini` de CLI (`C:\wamp64\bin\php\php8.3.28\php.ini`) conviene sin xdebug para que `artisan` tampoco lo cargue.

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
- Tarea programada Windows cada minuto: `schedule:run` (respaldo diario + colas). **Verificar que exista**: sin ella, `Schedule::command('app:backup-database')->daily()` nunca se ejecuta y los respaldos solo salen a mano desde la UI.
- Worker supervisado para `queue:work` (QUEUE_CONNECTION=database). Sin jobs vivos hoy, pero `queue:restart` en cada deploy mantiene la coherencia si se activan.
- Copias de `storage/app/backups` fuera del servidor.
- `php.ini` de Apache endurecido (ver "OPcache y PHP en producción") y servicio `wampapache64` reiniciable.
