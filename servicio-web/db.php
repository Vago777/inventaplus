<?php
/**
 * ============================================================
 * db.php - Conexión con la base de datos del proyecto
 * ============================================================
 * Evidencia: GA7-220501096-AA5-EV03
 *
 * Define la función conectar(), que abre la conexión PDO con la
 * base de datos "inventario" del sistema InventaPlus.
 *
 * En desarrollo local se puede crear el archivo config_local.php
 * (no versionado) para sobrescribir las credenciales.
 * ============================================================
 */

// Credenciales por defecto del entorno local (XAMPP: root sin clave).
$db = [
    'host' => '127.0.0.1',
    'port' => '3306',
    'nombre' => 'inventario',
    'usuario' => 'root',
    'clave' => '',
];

// Si existe una configuración local, se carga y sobrescribe la anterior.
if (file_exists(__DIR__ . '/config_local.php')) {
    include __DIR__ . '/config_local.php';
}

/**
 * Abre y devuelve la conexión con la base de datos del proyecto.
 *
 * @return PDO Conexión activa (errores como excepciones, arreglos asociativos).
 */
function conectar(): PDO
{
    global $db;

    $dsn = 'mysql:host=' . $db['host'] . ';port=' . $db['port']
         . ';dbname=' . $db['nombre'] . ';charset=utf8mb4';

    try {
        return new PDO($dsn, $db['usuario'], $db['clave'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $e) {
        // Si no hay conexión se responde en JSON y se detiene la ejecución.
        http_response_code(500);
        echo json_encode(
            ['ok' => false, 'error' => 'No se pudo conectar con la base de datos del proyecto.'],
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }
}