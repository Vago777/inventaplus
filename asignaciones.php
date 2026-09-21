<?php 
require_once __DIR__ . "/connection.php";
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

function checkSession() {
    if (!isset($_SESSION['admin_id'])) {
        http_response_code(401);
        echo json_encode(['message' => 'No autorizado - Sesión no iniciada']);
        exit;
    }
}

$method = $_SERVER['REQUEST_METHOD'];

switch($method){
    case "GET":
        checkSession();
        
        if (isset($_GET['id'])) {
            $id = $_GET['id'];
            $stmt = $pdo->prepare('
                SELECT a.*, u.nombre as nombre_usuario, u.identificacion, 
                       e.tipo_equipo, e.serial, e.placa, e.estado as estado_equipo,
                       admin.nombre as nombre_admin
                FROM asignaciones a 
                JOIN usuarios u ON a.usuario_id = u.id 
                JOIN equipos e ON a.equipo_id = e.id 
                LEFT JOIN usuarios admin ON a.admin_id = admin.id 
                WHERE a.id = ?
            ');
            $stmt->execute([$id]);
            $data = $stmt->fetch();
            
            if ($data) {
                echo json_encode($data);
            } else {
                http_response_code(404);
                echo json_encode(['message' => 'Asignación no encontrada']);
            }
        } else {
            $stmt = $pdo->prepare('
                SELECT a.*, u.nombre as nombre_usuario, u.identificacion, 
                       e.tipo_equipo, e.serial, e.placa, e.estado as estado_equipo,
                       admin.nombre as nombre_admin
                FROM asignaciones a 
                JOIN usuarios u ON a.usuario_id = u.id 
                JOIN equipos e ON a.equipo_id = e.id 
                LEFT JOIN usuarios admin ON a.admin_id = admin.id 
                ORDER BY a.fecha_asignacion DESC
            ');
            $stmt->execute();
            $data = $stmt->fetchAll();
            echo json_encode($data);
        }
    break;

    case "POST":
        checkSession();
        
        $data = json_decode(file_get_contents('php://input'), true);
        if(!$data){
            echo json_encode(['message'=>'JSON Invalido']);
            exit;
        }

        $usuario_id = trim($data['usuario_id'] ?? '');
        $equipo_id = trim($data['equipo_id'] ?? '');

        if(empty($usuario_id) || empty($equipo_id)){
            echo json_encode(['message' => 'Usuario y equipo son obligatorios']);
            exit;
        }

        if(!is_numeric($usuario_id) || !is_numeric($equipo_id)){
            echo json_encode(['message' => 'ID de usuario y equipo deben ser numéricos']);
            exit;
        }

        $stmt = $pdo->prepare('SELECT id FROM asignaciones WHERE equipo_id = ? AND estado_asignacion = "activa"');
        $stmt->execute([$equipo_id]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['message' => 'El equipo ya está asignado y no ha sido devuelto']);
            exit;
        }

        $stmt = $pdo->prepare('SELECT estado FROM equipos WHERE id = ?');
        $stmt->execute([$equipo_id]);
        $equipo = $stmt->fetch();
        
        if (!$equipo || $equipo['estado'] !== 'disponible') {
            http_response_code(400);
            echo json_encode(['message' => 'El equipo no está disponible para asignación']);
            exit;
        }

        $admin_id = $_SESSION['admin_id'];
        
        $stmt = $pdo->prepare('INSERT INTO asignaciones (usuario_id, equipo_id, admin_id, estado_asignacion) VALUES (?,?,?,"activa")');
        $stmt->execute([$usuario_id, $equipo_id, $admin_id]);

        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->prepare('UPDATE equipos SET estado = "asignado" WHERE id = ?');
            $stmt->execute([$equipo_id]);
            echo json_encode(['message' => 'Registrado']);
        } else {
            echo json_encode(['message' => 'Error al registrar']);
        }
    break;

    case "PUT":
        checkSession();
        
        $data = json_decode(file_get_contents('php://input'), true);
        if(!$data){
            echo json_encode(['message'=>'JSON Invalido']);
            exit;
        }
        
        $id = trim($data['id'] ?? '');

        if($id === '' || !is_numeric($id)){
            echo json_encode(['message'=>'ID vacio o Invalido']);
            exit;
        }

        if (isset($data['devolver']) && $data['devolver'] === true) {
            $stmt = $pdo->prepare('UPDATE asignaciones SET estado_asignacion = "devuelta", fecha_devolucion = CURRENT_TIMESTAMP WHERE id = ? AND estado_asignacion = "activa"');
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() > 0) {
                $stmt = $pdo->prepare('SELECT equipo_id FROM asignaciones WHERE id = ?');
                $stmt->execute([$id]);
                $asignacion = $stmt->fetch();
                
                $stmt = $pdo->prepare('UPDATE equipos SET estado = "disponible" WHERE id = ?');
                $stmt->execute([$asignacion['equipo_id']]);
                
                echo json_encode(['message' => 'Actualizado']);
            } else {
                echo json_encode(['message' => 'Error al actualizar o la asignación no está activa']);
            }
        } else {
            echo json_encode(['message' => 'Operación no válida']);
        }
    break;

    case "DELETE":
        checkSession();
        
        $data = json_decode(file_get_contents('php://input'), true);
        if(!$data){
            echo json_encode(['message'=>'JSON Invalido']);
            exit;
        }
        
        $id = trim($data['id'] ?? '');

        if($id === '' || !is_numeric($id)){
            echo json_encode(['message'=>'ID vacio o Invalido']);
            exit;
        }

        $stmt = $pdo->prepare('SELECT equipo_id, estado_asignacion FROM asignaciones WHERE id = ?');
        $stmt->execute([$id]);
        $asignacion = $stmt->fetch();
        
        if ($asignacion && $asignacion['estado_asignacion'] === 'activa') {
            $stmt = $pdo->prepare('UPDATE equipos SET estado = "disponible" WHERE id = ?');
            $stmt->execute([$asignacion['equipo_id']]);
        }

        $stmt = $pdo->prepare('DELETE FROM asignaciones WHERE id = ?');
        $stmt->execute([$id]);
        
        echo json_encode(['message' => $stmt->rowCount() > 0 ? 'Eliminado' : 'Error al eliminar']);
    break;

    case "OPTIONS":
        http_response_code(200);
        break;

    default:
        http_response_code(405);
        echo json_encode(['message'=>'Metodo no valido']);
    break;
}
?>