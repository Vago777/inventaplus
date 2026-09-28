<?php
/**
 * ============================================================================
 * SERVICIO WEB - REGISTRO Y AUTENTICACIÓN DE USUARIOS
 * ============================================================================
 * GA7-220501096-AA5-EV01
 * Componente formativo: "Construcción API"
 *
 * Servicio web que recibe un usuario y una contraseña y realiza dos acciones:
 *
 *   1) "registro" : crea una cuenta nueva con el par usuario/contraseña.
 *   2) "login"    : valida las credenciales recibidas.
 *
 * Si la autenticación es correcta el servicio responde con el mensaje:
 *        {"mensaje": "Autenticación satisfactoria"}
 * En caso contrario responde con:
 *        {"error": "Error en la autenticación"}
 *
 * Cómo probarlo con el servidor integrado de PHP:
 *        php -S 127.0.0.1:8090 -t servicio-web-auth
 * ============================================================================
 */

/* ----------------------- Configuración de la base de datos ----------------- */

// Credenciales del servidor de base de datos local (XAMPP / MySQL).
// En despliegues reales estos valores se definen con variables de entorno.
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NOMBRE', 'servicio_auth');
define('DB_USUARIO', 'root');
define('DB_CLAVE', '');

// Todas las respuestas del servicio se entregan en formato JSON.
header('Content-Type: application/json; charset=utf-8');

/* ------------------------------ Funciones del servicio --------------------- */

/**
 * Envía una respuesta JSON y detiene la ejecución del script.
 *
 * @param int   $codigo    Código de estado HTTP de la respuesta.
 * @param array $contenido Arreglo asociativo con el mensaje a devolver.
 */
function responder(int $codigo, array $contenido): void
{
    http_response_code($codigo);               // Establece el código HTTP (200, 400, 401...).
    echo json_encode($contenido, JSON_UNESCAPED_UNICODE);  // Emite el JSON legible.
    exit;                                      // Termina el script.
}

/**
 * Establece y devuelve la conexión con la base de datos del servicio.
 *
 * @return PDO Conexión activa a la base de datos MySQL.
 */
function conectar(): PDO
{
    try {
        // Construye la cadena de conexión (DSN) por partes para mejor lectura.
        $dsn  = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT;
        $dsn .= ';dbname=' . DB_NOMBRE . ';charset=utf8mb4';

        // Opciones de conexión: errores como excepciones y resultados asociativos.
        $opciones = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        // Abre la conexión con la base de datos del servicio.
        return new PDO($dsn, DB_USUARIO, DB_CLAVE, $opciones);
    } catch (PDOException $e) {
        responder(500, ['error' => 'No se pudo conectar con el servicio de datos.']);
    }
}

/**
 * Registra un usuario nuevo en la base de datos.
 *
 * @param PDO   $pdo      Conexión activa.
 * @param string $usuario  Nombre de usuario recibido en la solicitud.
 * @param string $clave    Contraseña en texto plano recibida en la solicitud.
 */
function registrarUsuario(PDO $pdo, string $usuario, string $clave): void
{
    // Valida que el usuario no esté vacío.
    if (trim($usuario) === '') {
        responder(400, ['error' => 'El usuario es obligatorio.']);
    }

    // Genera el hash (bcrypt) de la contraseña; nunca se guarda en texto plano.
    $hash = password_hash($clave, PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare('INSERT INTO usuarios (usuario, clave_hash) VALUES (:u, :h)');
        $stmt->execute([':u' => $usuario, ':h' => $hash]);
        responder(201, ['mensaje' => 'Registro realizado correctamente.']);
    } catch (PDOException $e) {
        // Error 23000 corresponde a violación de la restricción UNIQUE (usuario repetido).
        if ($e->getCode() === '23000') {
            responder(409, ['error' => 'El usuario ya se encuentra registrado.']);
        }
        responder(500, ['error' => 'No fue posible completar el registro.']);
    }
}

/**
 * Valida el usuario y la contraseña para el inicio de sesión.
 *
 * @param PDO    $pdo     Conexión activa.
 * @param string $usuario Nombre de usuario recibido en la solicitud.
 * @param string $clave   Contraseña recibida en la solicitud.
 */
function autenticar(PDO $pdo, string $usuario, string $clave): void
{
    // Busca el usuario en la tabla de la base de datos.
    $stmt = $pdo->prepare('SELECT clave_hash FROM usuarios WHERE usuario = :u LIMIT 1');
    $stmt->execute([':u' => $usuario]);
    $fila = $stmt->fetch();

    // Compara la contraseña recibida con el hash almacenado mediante bcrypt.
    $esValida = $fila && password_verify($clave, $fila['clave_hash']);

    if ($esValida) {
        // Credenciales correctas: se devuelve el mensaje solicitado en el enunciado.
        responder(200, ['mensaje' => 'Autenticación satisfactoria']);
    }

    // Credenciales incorrectas: se devuelve el mensaje de error solicitado.
    responder(401, ['error' => 'Error en la autenticación']);
}

/* ------------------------- Procesamiento de la solicitud -------------------- */

// Lee el cuerpo de la petición (formato JSON) enviado por el cliente.
$entrada = json_decode(file_get_contents('php://input'), true);

// Se asegura de que el cuerpo sea un arreglo (si no es JSON válido se ignora).
$entrada = is_array($entrada) ? $entrada : [];

// Recupera los campos: acción a realizar, usuario y contraseña.
$accion  = $entrada['accion']  ?? '';
$usuario = $entrada['usuario'] ?? '';
$clave   = $entrada['clave']   ?? '';

// Rutas del servicio según la acción solicitada por el cliente.
if ($accion === 'registro') {
    registrarUsuario(conectar(), $usuario, $clave);
} elseif ($accion === 'login') {
    autenticar(conectar(), $usuario, $clave);
} else {
    // Acción desconocida o ausente: se notifica al cliente.
    responder(400, ['error' => 'Acción no válida. Use "registro" o "login".']);
}