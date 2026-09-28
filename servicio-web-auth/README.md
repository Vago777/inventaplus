# Servicio web de registro y autenticación (GA7-220501096-AA5-EV01)

Servicio web desarrollado con PHP para el registro y el inicio de sesión de usuarios.
Recibe un **usuario** y una **contraseña**; si la autenticación es correcta responde el
mensaje **"Autenticación satisfactoria"**, y en caso contrario devuelve el mensaje
**"Error en la autenticación"**.

## Requisitos

- PHP 8 o superior.
- MySQL / MariaDB.
- Git para el control de versiones.

## Estructura

| Archivo | Descripción |
| --- | --- |
| `servicio.php` | Código del servicio web (registro y login), con comentarios. |
| `setup.php` | Script que crea la base de datos `servicio_auth` y la tabla `usuarios`. |
| `README.md` | Esta documentación. |

## Instalación

1. Crear la base de datos y la tabla (una sola vez):

   ```bash
   php setup.php
   ```

2. Iniciar el servidor integrado de PHP dentro de la carpeta del proyecto:

   ```bash
   php -S 127.0.0.1:8090 -t servicio-web-auth
   ```

## Uso del servicio

Enviar una petición `POST` en formato JSON a `http://127.0.0.1:8090/servicio.php`.

### Registro de un usuario

```json
{
  "accion": "registro",
  "usuario": "victor",
  "clave": "clave123"
}
```

Respuesta exitosa:

```json
{ "mensaje": "Registro realizado correctamente." }
```

### Inicio de sesión correcto

```json
{
  "accion": "login",
  "usuario": "victor",
  "clave": "clave123"
}
```

Respuesta:

```json
{ "mensaje": "Autenticación satisfactoria" }
```

### Inicio de sesión con credenciales incorrectas

```json
{
  "accion": "login",
  "usuario": "victor",
  "clave": "incorrecta"
}
```

Respuesta:

```json
{ "error": "Error en la autenticación" }
```

### Uso con Invoke-RestMethod (PowerShell)

```powershell
Invoke-RestMethod -Uri "http://127.0.0.1:8090/servicio.php" `
  -Method Post -ContentType "application/json" `
  -Body '{"accion":"registro","usuario":"ana","clave":"ana2026"}'
```

## Seguridad

- La contraseña se almacena cifrada con `password_hash()` (bcrypt).
- No se almacenan credenciales en el repositorio.
- Las credenciales de conexión a la base de datos se definen en una constante
  del código y deben moverse a variables de entorno en un despliegue real.