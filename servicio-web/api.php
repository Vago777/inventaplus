<?php
/**
 * ============================================================
 * API InventaPlus - Servicios web del proyecto formativo
 * ============================================================
 * Evidencia : GA7-220501096-AA5-EV03
 * Componente: Construccion de API / Servicios web
 *
 * Punto de entrada unico de la API. Recibe una ruta (?ruta=...),
 * el metodo HTTP (GET, POST, PUT, DELETE) y un cuerpo JSON, y
 * conmuta al servicio correspondiente del proyecto.
 *
 * Servicios disponibles:
 *   POST  login                -> autenticacion de administradores
 *   GET   areas                -> listar areas
 *   POST  areas                -> crear area
 *   PUT   areas                -> actualizar area
 *   DELETE areas               -> eliminar area
 *   GET   usuarios             -> listar usuarios
 *   POST  usuarios             -> crear usuario
 *   PUT   usuarios             -> actualizar usuario
 *   DELETE usuarios            -> eliminar usuario
 *   GET   equipos              -> listar equipos
 *   POST  equipos              -> crear equipo
 *   PUT   equipos              -> actualizar equipo
 *   DELETE equipos             -> eliminar equipo
 *   GET   asignaciones         -> listar asignaciones
 *   POST  asignaciones         -> asignar equipo a usuario
 *   POST  asignaciones&op=devolver -> registrar devolucion
 *   GET   resumen              -> conteos del panel general
 *
 * Formato de respuesta (ejemplo):
 *   {"ok": true, "datos": [...]}        para listados
 *   {"ok": true, "mensaje": "..."}      para procesos exitosos
 *   {"ok": false, "error": "..."}       para errores
 *
 * Prueba con el servidor integrado de PHP:
 *   php -S 127.0.0.1:8090 -t servicio-web
 * ============================================================
 */

// Todas las respuestas se entregan en formato JSON con UTF-8.
// Los avisos internos no se muestran: la respuesta debe ser JSON válido.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Las peticiones de verificación CORS no procesan lógica de negocio.
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Conexión compartida con la base de datos del proyecto.
require __DIR__ . '/db.php';

/* ======================= Funciones auxiliares ============================ */

/**
 * Envía una respuesta JSON con su código HTTP y termina el script.
 *
 * @param int   $codigo    Código de estado HTTP (200, 201, 400, 404...).
 * @param array $contenido Arreglo asociativo con la respuesta.
 */
function responder(int $codigo, array $contenido): void
{
    http_response_code($codigo);
    echo json_encode($contenido, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Lee el cuerpo JSON de la petición (válido para POST, PUT y DELETE).
 *
 * @return array Arreglo con los campos recibidos.
 */
function leerEntrada(): array
{
    $cuerpo = json_decode(file_get_contents('php://input'), true);
    return is_array($cuerpo) ? $cuerpo : [];
}

/**
 * Devuelve un campo del arreglo de entrada sin espacios al inicio/fin.
 *
 * @param array  $datos  Arreglo de entrada.
 * @param string $campo  Nombre del campo.
 * @param string $vacio  Valor por defecto si no existe.
 */
function campo(array $datos, string $campo, string $vacio = ''): string
{
    return trim((string)($datos[$campo] ?? $vacio));
}

/**
 * Normaliza un valor SI/NO (1/0, true/false) a entero.
 *
 * @param mixed $valor Valor recibido.
 * @return int 1 o 0.
 */
function queSiNo($valor): int
{
    if (is_bool($valor)) {
        return $valor ? 1 : 0;
    }
    $txt = strtolower(trim((string)$valor));
    return in_array($txt, ['1', 'true', 'si', 's'], true) ? 1 : 0;
}

/**
 * Indica si un registro existe en la tabla indicada.
 *
 * @param PDO   $pdo    Conexión activa.
 * @param string $tabla Tabla a consultar.
 * @param int   $id     Identificador a verificar.
 * @return bool True si el registro existe.
 */
function existeRegistro(PDO $pdo, string $tabla, int $id): bool
{
    $stmt = $pdo->prepare("SELECT 1 FROM `$tabla` WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    return (bool)$stmt->fetchColumn();
}

/* =================== Servicio: autenticación (login) ===================== */

/**
 * Valida las credenciales de un administrador.
 *
 * POST ?ruta=login
 * Cuerpo: { "username": "admin", "password": "..." }
 */
function servicioLogin(PDO $pdo, array $datos): void
{
    $username = campo($datos, 'username');
    $password = campo($datos, 'password');

    if ($username === '' || $password === '') {
        responder(400, ['ok' => false, 'error' => 'El nombre de usuario y la clave son obligatorios.']);
    }

    // Busca el administrador y trae también el nombre desde el usuario relacionado.
    $sql = 'SELECT a.id, a.username, a.password, a.estado, u.nombre, u.area_id
            FROM administradores a
            INNER JOIN usuarios u ON u.id = a.usuario_id
            WHERE a.username = :u LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':u' => $username]);
    $admin = $stmt->fetch();

    // Compara la clave recibida con el hash almacenado (password_verify).
    $esValida = $admin
        && $admin['estado'] === 'activo'
        && password_verify($password, $admin['password']);

    if (!$esValida) {
        responder(401, ['ok' => false, 'error' => 'Credenciales incorrectas.']);
    }

    responder(200, [
        'ok'      => true,
        'mensaje' => 'Autenticación satisfactoria',
        'usuario' => [
            'admin_id' => (int)$admin['id'],
            'username' => $admin['username'],
            'nombre'   => $admin['nombre'],
            'area_id'  => (int)$admin['area_id'],
        ],
    ]);
}

/* ====================== Servicio: áreas (catálogo) ======================= */

/**
 * CRUD de las áreas/dependencias del proyecto.
 *
 * GET    ?ruta=areas                -> listar (con cantidad de usuarios)
 * POST   ?ruta=areas                -> crear   { "nombre": "..." }
 * PUT    ?ruta=areas                -> actualizar { "id": 1, "nombre": "...", "estado": "activo" }
 * DELETE ?ruta=areas                -> eliminar (con cuerpo { "id": 1 })
 */
function servicioAreas(PDO $pdo, string $metodo, array $datos): void
{
    switch ($metodo) {
        case 'GET':
            $filas = $pdo->query(
                'SELECT a.id, a.nombre, a.estado, COUNT(u.id) AS usuarios
                 FROM areas a LEFT JOIN usuarios u ON u.area_id = a.id
                 GROUP BY a.id ORDER BY a.nombre'
            )->fetchAll();
            responder(200, ['ok' => true, 'datos' => $filas]);

        case 'POST':
            $nombre = campo($datos, 'nombre');
            if ($nombre === '') {
                responder(400, ['ok' => false, 'error' => 'El nombre del área es obligatorio.']);
            }
            $stmt = $pdo->prepare('INSERT INTO areas (nombre) VALUES (:n)');
            $stmt->execute([':n' => $nombre]);
            responder(201, ['ok' => true, 'mensaje' => 'Área creada correctamente.', 'id' => (int)$pdo->lastInsertId()]);

        case 'PUT':
            $id     = (int)campo($datos, 'id');
            $nombre = campo($datos, 'nombre');
            $estado = campo($datos, 'estado', 'activo');
            if ($id <= 0 || $nombre === '') {
                responder(400, ['ok' => false, 'error' => 'id y nombre son obligatorios.']);
            }
            if (!in_array($estado, ['activo', 'inactivo'], true)) {
                responder(400, ['ok' => false, 'error' => 'El estado debe ser activo o inactivo.']);
            }
            $stmt = $pdo->prepare('UPDATE areas SET nombre = :n, estado = :e WHERE id = :id');
            $stmt->execute([':n' => $nombre, ':e' => $estado, ':id' => $id]);
            if ($stmt->rowCount() === 0) {
                responder(404, ['ok' => false, 'error' => 'El área no existe o no cambió.']);
            }
            responder(200, ['ok' => true, 'mensaje' => 'Área actualizada correctamente.']);

        case 'DELETE':
            $id = (int)campo($datos, 'id');
            if ($id <= 0) {
                responder(400, ['ok' => false, 'error' => 'El id es obligatorio.']);
            }
            $uso = $pdo->prepare('SELECT COUNT(*) AS n FROM usuarios WHERE area_id = :id');
            $uso->execute([':id' => $id]);
            if ((int)$uso->fetch()['n'] > 0) {
                responder(409, ['ok' => false, 'error' => 'No se puede eliminar: el área tiene usuarios asociados.']);
            }
            $stmt = $pdo->prepare('DELETE FROM areas WHERE id = :id');
            $stmt->execute([':id' => $id]);
            if ($stmt->rowCount() === 0) {
                responder(404, ['ok' => false, 'error' => 'El área no existe.']);
            }
            responder(200, ['ok' => true, 'mensaje' => 'Área eliminada correctamente.']);
    }
}

/* ======================= Servicio: usuarios ============================== */

/**
 * CRUD de los usuarios (aprendices / funcionarios) del proyecto.
 *
 * GET    ?ruta=usuarios        -> listar
 * POST   ?ruta=usuarios        -> crear   { identificacion, nombre, correo_sena, telefono, area_id }
 * PUT    ?ruta=usuarios        -> actualizar (mismos campos + id)
 * DELETE ?ruta=usuarios        -> eliminar { id }
 */
function servicioUsuarios(PDO $pdo, string $metodo, array $datos): void
{
    switch ($metodo) {
        case 'GET':
            $filas = $pdo->query(
                'SELECT u.id, u.identificacion, u.nombre, u.correo_sena, u.telefono,
                        u.area_id, a.nombre AS area
                 FROM usuarios u INNER JOIN areas a ON a.id = u.area_id
                 ORDER BY u.nombre'
            )->fetchAll();
            responder(200, ['ok' => true, 'datos' => $filas]);

        case 'POST':
            $identificacion = campo($datos, 'identificacion');
            $nombre         = campo($datos, 'nombre');
            $correo         = campo($datos, 'correo_sena');
            $telefono       = campo($datos, 'telefono');
            $areaId         = (int)campo($datos, 'area_id');
            if ($identificacion === '' || $nombre === '' || $correo === '' || $telefono === '' || $areaId <= 0) {
                responder(400, ['ok' => false, 'error' => 'identificacion, nombre, correo_sena, telefono y area_id son obligatorios.']);
            }
            // El área debe existir en el catálogo (clave foránea area_id).
            if (!existeRegistro($pdo, 'areas', $areaId)) {
                responder(404, ['ok' => false, 'error' => 'El área indicada no existe.']);
            }
            $stmt = $pdo->prepare(
                'INSERT INTO usuarios (identificacion, nombre, correo_sena, telefono, area_id)
                 VALUES (:i, :n, :c, :t, :a)'
            );
            $stmt->execute([':i' => $identificacion, ':n' => $nombre, ':c' => $correo, ':t' => $telefono, ':a' => $areaId]);
            responder(201, ['ok' => true, 'mensaje' => 'Usuario creado correctamente.', 'id' => (int)$pdo->lastInsertId()]);

        case 'PUT':
            $id = (int)campo($datos, 'id');
            $identificacion = campo($datos, 'identificacion');
            $nombre         = campo($datos, 'nombre');
            $correo         = campo($datos, 'correo_sena');
            $telefono       = campo($datos, 'telefono');
            $areaId         = (int)campo($datos, 'area_id');
            if ($id <= 0 || $identificacion === '' || $nombre === '' || $correo === '' || $telefono === '' || $areaId <= 0) {
                responder(400, ['ok' => false, 'error' => 'Todos los campos son obligatorios (incluido id).']);
            }
            if (!existeRegistro($pdo, 'usuarios', $id)) {
                responder(404, ['ok' => false, 'error' => 'El usuario no existe.']);
            }
            if (!existeRegistro($pdo, 'areas', $areaId)) {
                responder(404, ['ok' => false, 'error' => 'El área indicada no existe.']);
            }
            $stmt = $pdo->prepare(
                'UPDATE usuarios SET identificacion = :i, nombre = :n, correo_sena = :c,
                        telefono = :t, area_id = :a
                 WHERE id = :id'
            );
            $stmt->execute([':i' => $identificacion, ':n' => $nombre, ':c' => $correo, ':t' => $telefono, ':a' => $areaId, ':id' => $id]);
            responder(200, ['ok' => true, 'mensaje' => 'Usuario actualizado correctamente.']);

        case 'DELETE':
            $id = (int)campo($datos, 'id');
            if ($id <= 0) {
                responder(400, ['ok' => false, 'error' => 'El id es obligatorio.']);
            }
            $uso = $pdo->prepare('SELECT COUNT(*) AS n FROM asignaciones WHERE usuario_id = :id');
            $uso->execute([':id' => $id]);
            if ((int)$uso->fetch()['n'] > 0) {
                responder(409, ['ok' => false, 'error' => 'No se puede eliminar: el usuario tiene asignaciones registradas.']);
            }
            $stmt = $pdo->prepare('DELETE FROM usuarios WHERE id = :id');
            $stmt->execute([':id' => $id]);
            if ($stmt->rowCount() === 0) {
                responder(404, ['ok' => false, 'error' => 'El usuario no existe.']);
            }
            responder(200, ['ok' => true, 'mensaje' => 'Usuario eliminado correctamente.']);
    }
}

/* ======================= Servicio: equipos =============================== */

/**
 * CRUD del inventario de equipos de cómputo.
 *
 * GET    ?ruta=equipos        -> listar
 * POST   ?ruta=equipos        -> crear   { tipo_equipo, serial, placa, tiene_mouse, tiene_teclado,
 *                                          tiene_cargador, tiene_rj45_tipo_c, estado }
 * PUT    ?ruta=equipos        -> actualizar (mismos campos + id)
 * DELETE ?ruta=equipos        -> eliminar { id }
 */
function servicioEquipos(PDO $pdo, string $metodo, array $datos): void
{
    switch ($metodo) {
        case 'GET':
            $filas = $pdo->query(
                'SELECT e.*, COUNT(a.id) AS asignado
                 FROM equipos e
                 LEFT JOIN asignaciones a ON a.equipo_id = e.id AND a.estado_asignacion = "activa"
                 GROUP BY e.id ORDER BY e.placa'
            )->fetchAll();
            responder(200, ['ok' => true, 'datos' => $filas]);

        case 'POST':
            $tipo   = campo($datos, 'tipo_equipo');
            $serial = campo($datos, 'serial');
            $placa  = campo($datos, 'placa');
            if ($tipo === '' || $serial === '' || $placa === '') {
                responder(400, ['ok' => false, 'error' => 'tipo_equipo, serial y placa son obligatorios.']);
            }
            if (!in_array($tipo, ['equipo_mesa', 'portatil'], true)) {
                responder(400, ['ok' => false, 'error' => 'tipo_equipo debe ser equipo_mesa o portatil.']);
            }
            $estado = campo($datos, 'estado', 'disponible');
            if (!in_array($estado, ['disponible', 'asignado', 'mantenimiento'], true)) {
                responder(400, ['ok' => false, 'error' => 'estado debe ser disponible, asignado o mantenimiento.']);
            }
            $stmt = $pdo->prepare(
                'INSERT INTO equipos (tipo_equipo, serial, placa, tiene_mouse, tiene_teclado,
                        tiene_cargador, tiene_rj45_tipo_c, estado)
                 VALUES (:t, :s, :p, :m, :k, :c, :r, :e)'
            );
            $stmt->execute([
                ':t' => $tipo,
                ':s' => $serial,
                ':p' => $placa,
                ':m' => queSiNo($datos['tiene_mouse'] ?? 0),
                ':k' => queSiNo($datos['tiene_teclado'] ?? 0),
                ':c' => queSiNo($datos['tiene_cargador'] ?? 0),
                ':r' => queSiNo($datos['tiene_rj45_tipo_c'] ?? 0),
                ':e' => $estado,
            ]);
            responder(201, ['ok' => true, 'mensaje' => 'Equipo creado correctamente.', 'id' => (int)$pdo->lastInsertId()]);

        case 'PUT':
            $id = (int)campo($datos, 'id');
            $tipo   = campo($datos, 'tipo_equipo');
            $serial = campo($datos, 'serial');
            $placa  = campo($datos, 'placa');
            if ($id <= 0 || $tipo === '' || $serial === '' || $placa === '') {
                responder(400, ['ok' => false, 'error' => 'Todos los campos son obligatorios (incluido id).']);
            }
            if (!in_array($tipo, ['equipo_mesa', 'portatil'], true)) {
                responder(400, ['ok' => false, 'error' => 'tipo_equipo debe ser equipo_mesa o portatil.']);
            }
            $estado = campo($datos, 'estado', 'disponible');
            if (!in_array($estado, ['disponible', 'asignado', 'mantenimiento'], true)) {
                responder(400, ['ok' => false, 'error' => 'estado debe ser disponible, asignado o mantenimiento.']);
            }
            if (!existeRegistro($pdo, 'equipos', $id)) {
                responder(404, ['ok' => false, 'error' => 'El equipo no existe.']);
            }
            $stmt = $pdo->prepare(
                'UPDATE equipos SET tipo_equipo = :t, serial = :s, placa = :p,
                        tiene_mouse = :m, tiene_teclado = :k, tiene_cargador = :c,
                        tiene_rj45_tipo_c = :r, estado = :e
                 WHERE id = :id'
            );
            $stmt->execute([
                ':t' => $tipo,
                ':s' => $serial,
                ':p' => $placa,
                ':m' => queSiNo($datos['tiene_mouse'] ?? 0),
                ':k' => queSiNo($datos['tiene_teclado'] ?? 0),
                ':c' => queSiNo($datos['tiene_cargador'] ?? 0),
                ':r' => queSiNo($datos['tiene_rj45_tipo_c'] ?? 0),
                ':e' => $estado,
                ':id' => $id,
            ]);
            responder(200, ['ok' => true, 'mensaje' => 'Equipo actualizado correctamente.']);

        case 'DELETE':
            $id = (int)campo($datos, 'id');
            if ($id <= 0) {
                responder(400, ['ok' => false, 'error' => 'El id es obligatorio.']);
            }
            $uso = $pdo->prepare('SELECT COUNT(*) AS n FROM asignaciones WHERE equipo_id = :id');
            $uso->execute([':id' => $id]);
            if ((int)$uso->fetch()['n'] > 0) {
                responder(409, ['ok' => false, 'error' => 'No se puede eliminar: el equipo tiene asignaciones registradas.']);
            }
            $stmt = $pdo->prepare('DELETE FROM equipos WHERE id = :id');
            $stmt->execute([':id' => $id]);
            if ($stmt->rowCount() === 0) {
                responder(404, ['ok' => false, 'error' => 'El equipo no existe.']);
            }
            responder(200, ['ok' => true, 'mensaje' => 'Equipo eliminado correctamente.']);
    }
}

/* ====================== Servicio: asignaciones =========================== */

/**
 * Asignaciones de equipos: entrega y devolución.
 *
 * GET  ?ruta=asignaciones            -> listar asignaciones con nombres
 * POST ?ruta=asignaciones            -> asignar { usuario_id, equipo_id, admin_id }
 * POST ?ruta=asignaciones&op=devolver-> devolver { id }
 */
function servicioAsignaciones(PDO $pdo, array $datos): void
{
    $metodo = $_SERVER['REQUEST_METHOD'];
    switch ($metodo) {
        case 'GET':
            $filas = $pdo->query(
                'SELECT a.id, a.estado_asignacion, a.fecha_asignacion, a.fecha_devolucion,
                        u.nombre AS usuario, u.identificacion,
                        e.placa, e.serial,
                        adm.nombre AS administrador
                 FROM asignaciones a
                 INNER JOIN usuarios u ON u.id = a.usuario_id
                 INNER JOIN equipos  e ON e.id = a.equipo_id
                 INNER JOIN usuarios adm ON adm.id = a.admin_id
                 ORDER BY a.fecha_asignacion DESC'
            )->fetchAll();
            responder(200, ['ok' => true, 'datos' => $filas]);

        case 'POST':
            // Si se pide la devolución se remite al flujo de devolución.
            if (campo($_GET, 'op') === 'devolver') {
                $id = (int)campo($datos, 'id');
                if ($id <= 0) {
                    responder(400, ['ok' => false, 'error' => 'El id de la asignación es obligatorio.']);
                }
                // Se recupera el equipo asociado antes de marcar la devolución.
                $sel = $pdo->prepare('SELECT equipo_id FROM asignaciones WHERE id = :id AND estado_asignacion = "activa"');
                $sel->execute([':id' => $id]);
                $equipoId = $sel->fetch()['equipo_id'] ?? null;
                if ($equipoId === null) {
                    responder(409, ['ok' => false, 'error' => 'La asignación no existe o ya fue devuelta.']);
                }
                $pdo->prepare(
                    'UPDATE asignaciones SET estado_asignacion = "devuelta", fecha_devolucion = NOW() WHERE id = :id'
                )->execute([':id' => $id]);
                // El equipo vuelve a estar disponible.
                $pdo->prepare('UPDATE equipos SET estado = "disponible" WHERE id = :id')
                    ->execute([':id' => $equipoId]);
                responder(200, ['ok' => true, 'mensaje' => 'Equipo devuelto correctamente.']);
            }

            // Asignación de un equipo a un usuario.
            $usuarioId = (int)campo($datos, 'usuario_id');
            $equipoId  = (int)campo($datos, 'equipo_id');
            $adminId   = (int)campo($datos, 'admin_id');
            if ($usuarioId <= 0 || $equipoId <= 0 || $adminId <= 0) {
                responder(400, ['ok' => false, 'error' => 'usuario_id, equipo_id y admin_id son obligatorios.']);
            }

            // El usuario que recibe y el administrador deben existir.
            if (!existeRegistro($pdo, 'usuarios', $usuarioId)) {
                responder(404, ['ok' => false, 'error' => 'El usuario no existe.']);
            }
            if (!existeRegistro($pdo, 'usuarios', $adminId)) {
                responder(404, ['ok' => false, 'error' => 'El administrador no existe.']);
            }

            // El equipo debe estar disponible para poder asignarlo.
            $eq = $pdo->prepare('SELECT estado FROM equipos WHERE id = :id');
            $eq->execute([':id' => $equipoId]);
            $estadoEquipo = $eq->fetch()['estado'] ?? null;
            if ($estadoEquipo === null) {
                responder(404, ['ok' => false, 'error' => 'El equipo no existe.']);
            }
            if ($estadoEquipo !== 'disponible') {
                responder(409, ['ok' => false, 'error' => 'El equipo no está disponible para asignación.']);
            }

            $stmt = $pdo->prepare(
                'INSERT INTO asignaciones (usuario_id, equipo_id, admin_id)
                 VALUES (:u, :e, :a)'
            );
            $stmt->execute([':u' => $usuarioId, ':e' => $equipoId, ':a' => $adminId]);

            // Se guarda el identificador de la asignación antes de las demás consultas.
            $idAsignacion = (int)$pdo->lastInsertId();

            // El equipo pasa a estado "asignado" porque ya fue entregado.
            $pdo->prepare('UPDATE equipos SET estado = "asignado" WHERE id = :id')
                ->execute([':id' => $equipoId]);

            responder(201, ['ok' => true, 'mensaje' => 'Equipo asignado correctamente.', 'id' => $idAsignacion]);
    }
}

/* ======================== Servicio: resumen ============================== */

/**
 * Conteos del panel general del software.
 *
 * GET ?ruta=resumen
 */
function servicioResumen(PDO $pdo): void
{
    $contar = static function (string $sql) use ($pdo): int {
        return (int)$pdo->query($sql)->fetchColumn();
    };

    $resumen = [
        'usuarios'        => $contar('SELECT COUNT(*) FROM usuarios'),
        'administradores' => $contar('SELECT COUNT(*) FROM administradores'),
        'areas'           => $contar('SELECT COUNT(*) FROM areas'),
        'equipos'         => $contar('SELECT COUNT(*) FROM equipos'),
        'equipos_disponibles'  => $contar("SELECT COUNT(*) FROM equipos WHERE estado = 'disponible'"),
        'equipos_asignados'    => $contar("SELECT COUNT(*) FROM equipos WHERE estado = 'asignado'"),
        'equipos_mantenimiento'=> $contar("SELECT COUNT(*) FROM equipos WHERE estado = 'mantenimiento'"),
        'asignaciones_activas' => $contar("SELECT COUNT(*) FROM asignaciones WHERE estado_asignacion = 'activa'"),
    ];
    responder(200, ['ok' => true, 'resumen' => $resumen]);
}

/* ======================== Router principal =============================== */

// Lectura del método HTTP y la ruta solicitada.
$metodo = $_SERVER['REQUEST_METHOD'];
$ruta   = trim($_GET['ruta'] ?? '');

// Conexión a la base de datos del proyecto (definida en db.php).
/** @var PDO $pdo */
$pdo = conectar();

// Envoltura del despacho: cualquier error de base de datos o del
// servidor se traduce a una respuesta JSON con su código HTTP.
try {
    switch ($ruta) {
        case 'login':
            if ($metodo !== 'POST') {
                responder(405, ['ok' => false, 'error' => 'Método no permitido para el servicio login.']);
            }
            servicioLogin($pdo, leerEntrada());
            break;

        case 'areas':
            servicioAreas($pdo, $metodo, leerEntrada());
            break;

        case 'usuarios':
            servicioUsuarios($pdo, $metodo, leerEntrada());
            break;

        case 'equipos':
            servicioEquipos($pdo, $metodo, leerEntrada());
            break;

        case 'asignaciones':
            servicioAsignaciones($pdo, leerEntrada());
            break;

        case 'resumen':
            if ($metodo !== 'GET') {
                responder(405, ['ok' => false, 'error' => 'Método no permitido para el servicio resumen.']);
            }
            servicioResumen($pdo);
            break;

        default:
            responder(404, ['ok' => false, 'error' => 'Servicio no encontrado. Use ruta=login, areas, usuarios, equipos, asignaciones o resumen.']);
    }
} catch (PDOException $e) {
    // 23000: violación de restricción (clave duplicada o clave foránea).
    $codigo = ($e->getCode() === '23000') ? 409 : 500;
    $detalle = ($e->getCode() === '23000')
        ? 'La operación viola una restricción de la base de datos (dato duplicado o referencia inexistente).'
        : 'Error interno en el servicio de base de datos.';
    responder($codigo, ['ok' => false, 'error' => $detalle]);
} catch (Throwable $e) {
    responder(500, ['ok' => false, 'error' => 'Error interno del servicio.']);
}