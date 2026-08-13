# Exportación de la lógica de negocio — Carto

Este paquete contiene la **lógica de negocio** del sistema Carto, desacoplada del stack (Laravel + Inertia + React). Sirve para reimplementar el sistema en otro stack conservando las reglas de negocio.

## Contenido

| Ruta | Descripción |
| --- | --- |
| `business-logic.md` | **Documento principal**: modelo de dominio, reglas del correlativo, flujos (creación de comunicación, asignación de puesto), reinicio de numeración, estados/permisos, invariantes y diagramas Mermaid, más los contratos de persistencia. |
| `src/services/` | Implementación de la lógica de negocio orquestada (PHP, sin Eloquent directo): `NumberSequenceService`, `CommunicationService`, `UserService`, `AreaService`, `PositionService`, `BrandSettingsService`. |
| `src/repositories/` | Contratos (interfaces) de la capa de persistencia, independientes del ORM. |

## Cómo usarlo

1. Lee `business-logic.md` para entender las reglas de negocio.
2. Reimplementa la capa de persistencia a partir de los contratos en `src/repositories/`.
3. Adapta los servicios en `src/services/` a tu stack (los métodos ya encapsulan la lógica pura).

## Qué NO incluye (depende del stack)

- Vistas/componentes React e Inertia.
- Controladores HTTP y rutas.
- Migraciones / esquema de base de datos específico de Laravel.

La fuente canónica de la documentación es `domain.md` en la raíz del proyecto; este paquete es una foto autocontenida para trasladarse.
