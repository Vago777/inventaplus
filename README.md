# InventaPlus

Sistema de Gestión y Asignación de Equipos de Cómputo.

Aplicación web desarrollada en PHP que permite administrar el inventario de equipos de cómputo, gestionar usuarios y realizar asignaciones y devoluciones de equipos, con un panel de control que presenta indicadores clave.

## Tecnologías

| Capa | Tecnología |
|---|---|
| Frontend | HTML5, CSS3, JavaScript (ES6+), Bootstrap 5.1.3, jQuery 3.7.1, DataTables 1.13.6 |
| Backend | PHP 7+ (API REST con PDO) |
| Base de datos | MySQL / MariaDB |
| Servidor | XAMPP (Apache) / Ubuntu Server + LAMP |

## Estructura del proyecto

```
├── index.html           # Página de autenticación
├── index.css            # Estilos del login
├── js/
│   ├── login.js         # Lógica del frontend del login
│   └── admin.js         # Lógica del panel de administración
├── admin.html           # Panel de administración (SPA-lite)
├── style.css            # Estilos del panel
├── login.php            # Endpoint de autenticación
├── check_session.php    # Validación de sesión activa
├── logout.php           # Cierre de sesión
├── usuarios.php         # API REST de usuarios (CRUD)
├── equipos.php          # API REST de equipos (CRUD)
├── asignaciones.php     # API REST de asignaciones
├── connection.php       # Conexión a la base de datos (PDO)
└── database/
    └── inventario.sql   # Esquema y datos iniciales
```

## Funcionalidades

- **Autenticación:** Inicio y cierre de sesión con manejo de sesiones PHP.
- **Gestión de usuarios:** CRUD de aprendices/funcionarios (identificación, nombre, correo SENA, teléfono, área).
- **Gestión de equipos:** CRUD de equipos de mesa y portátiles, con control de accesorios (mouse, teclado, cargador, RJ45) y estados (disponible, asignado, mantenimiento).
- **Asignaciones:** Asignación y devolución de equipos con registro del administrador y fechas.
- **Dashboard:** Indicadores de total de usuarios, equipos, asignaciones activas y equipos disponibles, con devolución rápida.
- **Reglas de negocio:** No se eliminan usuarios/equipos con asignaciones activas; no se asignan equipos no disponibles.

## Requisitos

- XAMPP (Apache + MySQL) o servidor LAMP.
- PHP 7.4 o superior.
- MySQL 5.7 / MariaDB 10.3 o superior.
- Navegador web moderno.

## Instalación

1. Iniciar Apache y MySQL (XAMPP) o el servicio web de su servidor.
2. Colocar el proyecto en el directorio `htdocs` (o `www`).
3. Importar la base de datos:

   ```sql
   mysql -u root -p < database/inventario.sql
   ```

4. Crear el archivo `config_local.php` (opcional) con las credenciales de su base de datos:

   ```php
   <?php
   $config['host']     = 'localhost';
   $config['db_name']  = 'inventario';
   $config['username'] = 'root';
   $config['password'] = 'su_contraseña';
   ```

   > **Nota de seguridad:** `config_local.php` no se versiona en el repositorio (está en `.gitignore`). Si no existe, la conexión usa los valores por defecto del entorno de desarrollo.

5. Importar el esquema: abra `http://localhost/pry/index.html` (ajuste la ruta según su instalación) e inicie sesión con las credenciales del administrador definidas al instalar, o créelas directamente en la tabla `administradores` con un hash bcrypt:

   ```php
   <?php echo password_hash('SuClaveSegura', PASSWORD_BCRYPT); ?>
   ```

   > Es obligatorio cambiar la contraseña del administrador inmediatamente después de la primera instalación.

## Control de cambios

| Fecha | Autor | Commit | Descripción |
|---|---|---|---|
| 2026-09-21 | Vago777 | `0a0c639` | Commit inicial del sistema InventaPlus: autenticación, CRUD de usuarios/equipos, asignaciones, dashboard y esquema de base de datos |
| 2026-09-21 | — | `77fdf79` | Initial commit (README del repositorio) |

**Convenciones de commits:** `feat:` (nueva funcionalidad), `fix:` (corrección), `docs:` (documentación), `refactor:` (reestructuración), `style:` (formato), `test:` (pruebas).

## Seguridad

- Las contraseñas se almacenan con hash `bcrypt` (función `password_hash` de PHP).
- Las consultas SQL usan prepared statements (PDO) para prevenir inyección SQL.
- Las credenciales reales de la base de datos se mantienen fuera del repositorio (`config_local.php`).
- No se exponen credenciales de acceso en el código versionado.

## Licencia

Uso académico / educativo para el programa Tecnólogo en Análisis y Desarrollo de Software — SENA.