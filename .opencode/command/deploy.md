---
description: Despliega Carto a WAMP (c:/wamp64/www) con preflight, respaldo, migraciones y reporte por paso.
agent: build
---

Ejecuta el despliegue a producción siguiendo la skill `deploy` (runbook completo
en `.opencode/skills/deploy/SKILL.md`, mecánica en `scripts/sync.ps1`).

Argumentos del usuario: $ARGUMENTS

Flags soportados: `--dry-run` (solo preflight, no ejecuta), `--skip-build`
(reutiliza public/build), `--no-maintenance` (sin down/up), `--target <ruta>`
(destino alternativo a C:\wamp64\www\carto).

Procedimiento:

1. Lee la skill `deploy` y aplica preflight, pasos y reporte tal como indica.
2. Si algún paso falla, detente de inmediato e incluye la sección "Reversión".
3. Reporte final en chat (por paso: estado, duración, salida relevante) + log en
   `storage/logs/deploy-*.log` con secretos redactados.
