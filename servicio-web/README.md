# Servicio Web - API del proyecto InventaPlus

Evidencia **GA7-220501096-AA5-EV03** - Diseño y desarrollo de servicios web.

API en PHP que expone los servicios del sistema de gestión de equipos
**InventaPlus**. Todas las respuestas se entregan en formato JSON con su
código HTTP correspondiente.

## Arquitectura

| Capa | Tecnología | Función |
| --- | --- | --- |
| Cliente | HTTP (navegador, PowerShell, Postman) | Envía solicitudes JSON |
| Servidor | PHP 8 (servidor integrado) | Procesa y responde los servicios |
| Persistencia | MySQL / MariaDB (`inventario`) | Almacena áreas, usuarios, equipos y asignaciones |
| Versionamiento | Git + GitHub | Control de versiones del proyecto |

## Requisitos

- PHP 8 o superior.
- MySQL / MariaDB en ejecución.
- Base de datos `inventario` creada con `database/inventario.sql`.

## Ejecución

```bash
php -S 127.0.0.1:8090 -t servicio-web
```

## Servicios de la API

| Servicio | Método | Ruta | Descripción |
| --- | --- | --- | --- |
| Autenticación | POST | `/api.php?ruta=login` | Valida las credenciales del administrador |
| Áreas - listar | GET | `/api.php?ruta=areas` | Lista las áreas con su número de usuarios |
| Áreas - crear | POST | `/api.php?ruta=areas` | Crea un área |
| Áreas - actualizar | PUT | `/api.php?ruta=areas` | Actualiza nombre y estado del área |
| Áreas - eliminar | DELETE | `/api.php?ruta=areas` | Elimina un área sin usuarios |
| Usuarios - listar | GET | `/api.php?ruta=usuarios` | Lista los usuarios con su área |
| Usuarios - crear | POST | `/api.php?ruta=usuarios` | Crea un usuario |
| Usuarios - actualizar | PUT | `/api.php?ruta=usuarios` | Actualiza los datos del usuario |
| Usuarios - eliminar | DELETE | `/api.php?ruta=usuarios` | Elimina un usuario sin asignaciones |
| Equipos - listar | GET | `/api.php?ruta=equipos` | Lista el inventario de equipos |
| Equipos - crear | POST | `/api.php?ruta=equipos` | Crea un equipo |
| Equipos - actualizar | PUT | `/api.php?ruta=equipos` | Actualiza un equipo |
| Equipos - eliminar | DELETE | `/api.php?ruta=equipos` | Elimina un equipo sin asignaciones |
| Asignaciones - listar | GET | `/api.php?ruta=asignaciones` | Lista las asignaciones de equipos |
| Asignaciones - asignar | POST | `/api.php?ruta=asignaciones` | Asigna un equipo a un usuario |
| Asignaciones - devolver | POST | `/api.php?ruta=asignaciones&op=devolver` | Registra la devolución del equipo |
| Resumen | GET | `/api.php?ruta=resumen` | Conteos del panel general |

### Ejemplo

Las credenciales se toman del administrador registrado en la base de datos del
proyecto; no se almacenan en el repositorio. Reemplace los valores de ejemplo por
los de su instalación local:

```bash
curl "http://127.0.0.1:8090/api.php?ruta=login" -X POST ^
  -H "Content-Type: application/json" ^
  -d "{\"username\":\"<USUARIO_ADMIN>\",\"password\":\"<CLAVE_ADMIN>\"}"
```

## Repositorio

El proyecto se administra con Git y se publica en GitHub:

<https://github.com/Vago777/inventaplus>
