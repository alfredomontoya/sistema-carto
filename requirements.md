# Requisitos del Sistema

> La **fuente canónica** de la lógica de negocio es [`domain.md`](./domain.md). Este archivo describe los requisitos funcionales en prosa; ante contradicciones, manda `domain.md`.

Sistema de información para la gestión de comunicaciones internas (ci) y oficios externos (of) con numeración correlativa por área de trabajo.

## Roles y permisos

- **administrador**: crea y gestiona usuarios, asigna roles, resetea contraseñas (invalida sesiones), gestiona áreas, gestiona ajustes de marca. También puede crear comunicaciones y pertenece a un área.
- **usuario**: inicia sesión, consulta su perfil, cambia su contraseña, elige/actualiza su avatar, actualiza sus datos (nombre, correo, teléfono, dirección), crea y edita comunicaciones propias.

Permisos Spatie:
- `manage users`, `manage areas`, `manage settings` — solo rol `administrador`.

El registro público y la recuperación de contraseña no existen; solo el administrador crea cuentas.

## Usuarios, áreas y puestos

- **Áreas** = departamentos reales, organizados en un árbol recursivo (raíz CARTOGRAFIA como semilla).
- **Puestos** = cargos dentro de un área (JEFE, TECNICO, ABOGADO, SECRETARIA, ASISTENTE como semilla). Un puesto pertenece a una sola área (`positions.area_id`); un área tiene muchos puestos (`positions`).
- Un usuario se asigna a un **puesto**; su área actual se deriva del puesto (`currentAssignment` → `position.area`).
- La pertenencia se registra en un historial (`position_user` con `ended_at`): los usuarios pueden cambiar de puesto a lo largo del tiempo sin perder el historial. El puesto actual es la asignación con `ended_at = NULL`.
- No se puede eliminar un área que tenga hijos o puestos; no se puede eliminar un puesto con asignaciones (historial). Tampoco crear ciclos al mover subáreas.
- Usuarios desactivados (`is_active = false`) no pueden iniciar sesión y se les marcan las sesiones invalidadas al resetear contraseña.

## Comunicaciones / Oficios

- Un usuario crea una comunicación interna (ci) u oficio externo (of). Campos:
  - **Área**: la del área del puesto del usuario logueado (no editable; puede diferir del área de numeración).
  - **Puesto**: el del usuario logueado al crear (se guarda `position_id`; si cambia de puesto, la comunicación conserva el puesto original).
  - **Referencia**: texto libre.
  - **Remitente**: nombre y puesto del usuario logueado (no editable).
  - **Destinatario**: nombre y puesto, ambos obligatorios (buscador de usuarios internos + texto libre).
  - **Área destino**: nombre de área registrada (buscador) o texto libre; obligatoria en comunicaciones internas, opcional en oficios.
  - **Adjunto**: opcional, PDF, Word o imagen.
- Cada comunicación recibe un número correlativo por área, tipo (ci/of) y año con formato `prefijo.codigo_area.secuencia/año` (ejemplo `ci.carto.0001/2026`).
- El correlativo no se reutiliza aunque se anule una comunicación.
- **Solo el creador puede editar** una comunicación mientras esté activa.
- Anulación: la comunicación pasa a estado `anulado`; deja de mostrarse en la lista activa pero queda consultable (histórico) y su número no se reasigna.
- Búsqueda por remitente, destinatario, número de comunicación y rango de fechas.

## Ajustes

- Solo el nombre del sistema es editable desde la pantalla admin (los colores, logo y favicon guardados se siguen aplicando).
- El color primario se aplica como acento de la interfaz (botones, enlaces, selección activa) y al progreso de navegación Inertia.

## Portabilidad

- Toda la lógica de negocio está detrás de repositorios e interfaces para poder migrar la persistencia a MySQL, SQL Server, MongoDB o Firebase sin cambiar servicios/controladores.
- La base de datos de desarrollo es SQLite y la migración debe ser sencilla.

## Definido / Fuera de alcance

- No hay registro público ni verificación de correo.
- No hay recuperación de contraseña; la restablece el administrador.
- El administrador también crea comunicaciones y pertenece a un puesto.
- Destino admite tanto usuario interno (buscador) como texto libre.
- Avatar: galería precargada + subida propia.
