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
            $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE id = ?');
            $stmt->execute([$id]);
            $data = $stmt->fetch();
            
            if ($data) {
                echo json_encode($data);
            } else {
                http_response_code(404);
                echo json_encode(['message' => 'Usuario no encontrado']);
            }
        } else {
            $stmt = $pdo->prepare('SELECT * FROM usuarios ORDER BY nombre');
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

        $identificacion = trim($data['identificacion'] ?? '');
        $nombre = trim($data['nombre'] ?? '');
        $correo_sena = trim($data['correo_sena'] ?? '');
        $telefono = trim($data['telefono'] ?? '');
        $area = trim($data['area'] ?? '');

        if(empty($identificacion) || empty($nombre) || empty($correo_sena) || empty($telefono) || empty($area)){
            echo json_encode(['message' => 'Todos los campos son obligatorios']);
            exit;
        }

        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE identificacion = ?');
        $stmt->execute([$identificacion]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['message' => 'La identificación ya existe']);
            exit;
        }

        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE correo_sena = ?');
        $stmt->execute([$correo_sena]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['message' => 'El correo ya existe']);
            exit;
        }

        $stmt = $pdo->prepare('INSERT INTO usuarios (identificacion, nombre, correo_sena, telefono, area) VALUES (?,?,?,?,?)');
        $stmt->execute([$identificacion, $nombre, $correo_sena, $telefono, $area]);

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
        $identificacion = trim($data['identificacion'] ?? '');
        $nombre = trim($data['nombre'] ?? '');
        $correo_sena = trim($data['correo_sena'] ?? '');
        $telefono = trim($data['telefono'] ?? '');
        $area = trim($data['area'] ?? '');

        if($id === '' || !is_numeric($id)){
            echo json_encode(['message'=>'ID vacio o Invalido']);
            exit;
        }

        if(empty($identificacion) || empty($nombre) || empty($correo_sena) || empty($telefono) || empty($area)){
            echo json_encode(['message' => 'Todos los campos son obligatorios']);
            exit;
        }

        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE identificacion = ? AND id != ?');
        $stmt->execute([$identificacion, $id]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['message' => 'La identificación ya existe']);
            exit;
        }

        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE correo_sena = ? AND id != ?');
        $stmt->execute([$correo_sena, $id]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['message' => 'El correo ya existe']);
            exit;
        }

        $stmt = $pdo->prepare('UPDATE usuarios SET identificacion = ?, nombre = ?, correo_sena = ?, telefono = ?, area = ? WHERE id = ?');
        $stmt->execute([$identificacion, $nombre, $correo_sena, $telefono, $area, $id]);
        
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

        $stmt = $pdo->prepare('SELECT id FROM asignaciones WHERE usuario_id = ? AND estado_asignacion = "activa"');
        $stmt->execute([$id]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['message' => 'No se puede eliminar el usuario porque tiene asignaciones activas']);
            exit;
        }

        $stmt = $pdo->prepare('DELETE FROM usuarios WHERE id = ?');
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