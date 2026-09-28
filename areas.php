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
            $stmt = $pdo->prepare('SELECT * FROM areas WHERE id = ?');
            $stmt->execute([$id]);
            $data = $stmt->fetch();
            
            if ($data) {
                echo json_encode($data);
            } else {
                http_response_code(404);
                echo json_encode(['message' => 'Área no encontrada']);
            }
        } else {
            $stmt = $pdo->prepare('SELECT * FROM areas ORDER BY nombre');
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

        $nombre = trim($data['nombre'] ?? '');
        $estado = trim($data['estado'] ?? 'activo');

        if(empty($nombre)){
            echo json_encode(['message' => 'Todos los campos son obligatorios']);
            exit;
        }

        if(!in_array($estado, ['activo', 'inactivo'])){
            echo json_encode(['message' => 'Estado no válido']);
            exit;
        }

        $stmt = $pdo->prepare('SELECT id FROM areas WHERE nombre = ?');
        $stmt->execute([$nombre]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['message' => 'El área ya existe']);
            exit;
        }

        $stmt = $pdo->prepare('INSERT INTO areas (nombre, estado) VALUES (?,?)');
        $stmt->execute([$nombre, $estado]);

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
        $nombre = trim($data['nombre'] ?? '');
        $estado = trim($data['estado'] ?? '');

        if($id === '' || !is_numeric($id)){
            echo json_encode(['message'=>'ID vacio o Invalido']);
            exit;
        }

        if(empty($nombre) || empty($estado)){
            echo json_encode(['message' => 'Todos los campos son obligatorios']);
            exit;
        }

        if(!in_array($estado, ['activo', 'inactivo'])){
            echo json_encode(['message' => 'Estado no válido']);
            exit;
        }

        $stmt = $pdo->prepare('SELECT id FROM areas WHERE nombre = ? AND id != ?');
        $stmt->execute([$nombre, $id]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['message' => 'El área ya existe']);
            exit;
        }

        $stmt = $pdo->prepare('UPDATE areas SET nombre = ?, estado = ? WHERE id = ?');
        $stmt->execute([$nombre, $estado, $id]);
        
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

        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE area_id = ?');
        $stmt->execute([$id]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['message' => 'No se puede eliminar el área porque tiene usuarios asociados']);
            exit;
        }

        $stmt = $pdo->prepare('DELETE FROM areas WHERE id = ?');
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