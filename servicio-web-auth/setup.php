<?php
/**
 * ============================================================================
 * SETUP - CREA LA BASE DE DATOS Y LA TABLA DEL SERVICIO WEB
 * ============================================================================
 * GA7-220501096-AA5-EV01 - Servicio web de registro y autenticación
 *
 * Este script se ejecuta una sola vez desde la terminal:
 *     php setup.php
 *
 * Crea la base de datos "servicio_auth" y la tabla "usuarios" que almacena
 * el usuario y la contraseña cifrada de quienes se registran en el servicio.
 * ============================================================================
 */

// Credenciales de conexión al servidor de base de datos MySQL/MariaDB.
$host   = '127.0.0.1';  // Dirección del servidor de base de datos.
$port   = '3306';       // Puerto por defecto de MySQL.
$usuario = 'root';      // Usuario administrador de la base de datos local.
$pass   = '';           // Contraseña (vacía en este entorno local).

/**
 * 1. Conectarse al servidor sin seleccionar una base de datos todavía,
 *    para poder crear la base de datos del servicio si no existe.
 */
try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;charset=utf8mb4",
        $usuario,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Error al conectar con la base de datos: " . $e->getMessage() . PHP_EOL);
}

/**
 * 2. Crear la base de datos del servicio si aún no existe.
 *    Se usa ILIKE para que la consulta funcione igual en MySQL y MariaDB.
 */
$pdo->exec(
    "CREATE DATABASE IF NOT EXISTS servicio_auth
     CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
);
echo "Base de datos 'servicio_auth' verificada/creada." . PHP_EOL;

// 3. Conectarse a la base de datos creada en el paso anterior.
$pdo->exec("USE servicio_auth");

/**
 * 4. Crear la tabla "usuarios" que guarda los datos de cada usuario.
 *
 *    - id          : identificador único (autoincrementable).
 *    - usuario     : nombre de usuario, único en el sistema.
 *    - clave_hash  : contraseña cifrada con password_hash() (bcrypt).
 *    - creado_en   : fecha y hora del registro.
 */
$pdo->exec(
    "CREATE TABLE IF NOT EXISTS usuarios (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        usuario     VARCHAR(60) NOT NULL UNIQUE,
        clave_hash  VARCHAR(255) NOT NULL,
        creado_en   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB"
);
echo "Tabla 'usuarios' verificada/creada." . PHP_EOL;

echo "Configuración inicial completada. El servicio ya está listo." . PHP_EOL;