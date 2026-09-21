<?php
// ============================================================
// connection.php
// Configuracion de la conexion a la base de datos.
// - En desarrollo local, crea el archivo config_local.php
//   (NO versionado) con tus credenciales reales.
// - Si config_local.php no existe, se usa la configuracion
//   por defecto del entorno de desarrollo (XAMPP).
// ============================================================

$config = array(
    'host'     => 'localhost',
    'db_name'  => 'inventario',
    'username' => 'root',
    'password' => ''
);

// Si existe la configuracion local, la carga (sobrescribe la por defecto)
if (file_exists(__DIR__ . '/config_local.php')) {
    include __DIR__ . '/config_local.php';
}

$host     = $config['host'];
$db_name  = $config['db_name'];
$username = $config['username'];
$password = $config['password'];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo json_encode(['message' => 'Error de conexión: ' . $e->getMessage()]);
    exit;
}
?>