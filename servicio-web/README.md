# Servicio Web - API del proyecto InventaPlus

Evidencia **GA7-220501096-AA5-EV03** - Diseño y desarrollo de servicios web.
Evidencia **GA7-220501096-AA5-EV04** - Pruebas de las API con Postman.

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

## Pruebas con Postman (EV04)

La colección `api-inventaplus.postman_collection.json` contiene **39 peticiones
distribuidas en 7 grupos**, cada una con sus scripts de verificación `pm.test`:
autenticación, resumen, áreas, usuarios, equipos, asignaciones e integridad.

| Grupo | Peticiones | Cobertura |
| --- | --- | --- |
| 1. Autenticación | 4 | Acceso válido, credenciales inválidas, token y acceso protegido |
| 2. Resumen | 1 | Conteos del panel general y sesión válida |
| 3. Áreas | 6 | Listar, crear, duplicar, actualizar, eliminar y método no permitido |
| 4. Usuarios | 7 | Listar, crear, duplicar, actualizar, credenciales, mover de área y eliminar |
| 5. Equipos | 6 | Listar, crear, duplicar, actualizar, detalles, eliminar y método no permitido |
| 6. Asignaciones | 7 | Listar, asignar, código duplicado, reasignar, devolver y estado final |
| 7. Integridad y cierre | 8 | Persistencia de datos, resumen final y limpieza de registros de prueba |

En total la colección ejecuta **139 aserciones**, todas automáticas. Para correrla:

1. Importe la colección en Postman y cree un entorno con las variables
   `url_base` (`http://127.0.0.1:8090/api.php`), `usuario_admin` y `clave_admin`.
2. Configure `clave_admin` con la clave de su instalación local; no se versiona.
3. Abra la colección con **Runner** y ejecute las 39 peticiones en el orden numerado.

Las variables `id_area`, `id_usuario`, `id_equipo`, `id_asignacion` e `id_admin`
las sobrescribe cada script, por lo que la colección puede repetirse sin ajustes.

### Limpieza de los datos de prueba

La pruebas dejan un usuario, un equipo y su historial de asignación. Para devolver
la base de datos a su estado inicial:

```bash
mysql -u root < servicio-web/limpiar-datos-prueba.sql
```

## Documentación de endpoints

El detalle de los 17 endpoints (recurso, método, parámetros, cuerpo, respuesta y
códigos HTTP) está en `endpoints/API-ENDPOINTS.txt`.

## Repositorio

El proyecto se administra con Git y se publica en GitHub:

<https://github.com/Vago777/inventaplus>
