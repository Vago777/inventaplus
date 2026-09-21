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
            $stmt = $pdo->prepare('SELECT * FROM equipos WHERE id = ?');
            $stmt->execute([$id]);
            $data = $stmt->fetch();
            
            if ($data) {
                echo json_encode($data);
            } else {
                http_response_code(404);
                echo json_encode(['message' => 'Equipo no encontrado']);
            }
        } else {
            $stmt = $pdo->prepare('SELECT * FROM equipos ORDER BY tipo_equipo, placa');
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

        $tipo_equipo = trim($data['tipo_equipo'] ?? '');
        $serial = trim($data['serial'] ?? '');
        $placa = trim($data['placa'] ?? '');
        $tiene_mouse = isset($data['tiene_mouse']) ? (bool)$data['tiene_mouse'] : false;
        $tiene_teclado = isset($data['tiene_teclado']) ? (bool)$data['tiene_teclado'] : false;
        $tiene_cargador = isset($data['tiene_cargador']) ? (bool)$data['tiene_cargador'] : false;
        $tiene_rj45_tipo_c = isset($data['tiene_rj45_tipo_c']) ? (bool)$data['tiene_rj45_tipo_c'] : false;
        $estado = trim($data['estado'] ?? 'disponible');

        if(empty($tipo_equipo) || empty($serial) || empty($placa)){
            echo json_encode(['message' => 'Tipo equipo, serial y placa son obligatorios']);
            exit;
        }

        if(!in_array($tipo_equipo, ['equipo_mesa', 'portatil'])){
            echo json_encode(['message' => 'Tipo de equipo no válido']);
            exit;
        }

        $stmt = $pdo->prepare('SELECT id FROM equipos WHERE serial = ? OR placa = ?');
        $stmt->execute([$serial, $placa]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['message' => 'El serial o placa ya existe']);
            exit;
        }

        $stmt = $pdo->prepare('INSERT INTO equipos (tipo_equipo, serial, placa, tiene_mouse, tiene_teclado, tiene_cargador, tiene_rj45_tipo_c, estado) VALUES (?,?,?,?,?,?,?,?)');
        $stmt->execute([$tipo_equipo, $serial, $placa, $tiene_mouse, $tiene_teclado, $tiene_cargador, $tiene_rj45_tipo_c, $estado]);

        echo json_encode(['message' => $stmt->rowCount() > 0 ? 'Registrado' : 'Error al registrar']);
    break;

    case "PUT":
        checkSession();
        
        $data = json_decode(file_get_contents('php://input'), true);
        if(!$data){
            echo json_encode(['message'=>'JSON Invalido']);
            exit;
        }
        
        $id = trim($data['id'] ?? '');
        $tipo_equipo = trim($data['tipo_equipo'] ?? '');
        $serial = trim($data['serial'] ?? '');
        $placa = trim($data['placa'] ?? '');
        $tiene_mouse = isset($data['tiene_mouse']) ? (bool)$data['tiene_mouse'] : false;
        $tiene_teclado = isset($data['tiene_teclado']) ? (bool)$data['tiene_teclado'] : false;
        $tiene_cargador = isset($data['tiene_cargador']) ? (bool)$data['tiene_cargador'] : false;
        $tiene_rj45_tipo_c = isset($data['tiene_rj45_tipo_c']) ? (bool)$data['tiene_rj45_tipo_c'] : false;
        $estado = trim($data['estado'] ?? 'disponible');

        if($id === '' || !is_numeric($id)){
            echo json_encode(['message'=>'ID vacio o Invalido']);
            exit;
        }

        if(empty($tipo_equipo) || empty($serial) || empty($placa)){
            echo json_encode(['message' => 'Tipo equipo, serial y placa son obligatorios']);
            exit;
        }

        if(!in_array($tipo_equipo, ['equipo_mesa', 'portatil'])){
            echo json_encode(['message' => 'Tipo de equipo no válido']);
            exit;
        }

        $stmt = $pdo->prepare('SELECT id FROM equipos WHERE (serial = ? OR placa = ?) AND id != ?');
        $stmt->execute([$serial, $placa, $id]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['message' => 'El serial o placa ya existe']);
            exit;
        }

        $stmt = $pdo->prepare('UPDATE equipos SET tipo_equipo = ?, serial = ?, placa = ?, tiene_mouse = ?, tiene_teclado = ?, tiene_cargador = ?, tiene_rj45_tipo_c = ?, estado = ? WHERE id = ?');
        $stmt->execute([$tipo_equipo, $serial, $placa, $tiene_mouse, $tiene_teclado, $tiene_cargador, $tiene_rj45_tipo_c, $estado, $id]);
        
        echo json_encode(['message' => $stmt->rowCount() > 0 ? 'Actualizado' : 'Error al actualizar']);
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

        $stmt = $pdo->prepare('SELECT id FROM asignaciones WHERE equipo_id = ? AND estado_asignacion = "activa"');
        $stmt->execute([$id]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['message' => 'No se puede eliminar el equipo porque tiene asignaciones activas']);
            exit;
        }

        $stmt = $pdo->prepare('DELETE FROM equipos WHERE id = ?');
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