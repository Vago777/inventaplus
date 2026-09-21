<?php 
require_once __DIR__ . "/connection.php";
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$method = $_SERVER['REQUEST_METHOD'];

switch($method){
    case "POST":
        $data = json_decode(file_get_contents('php://input'), true);
        if(!$data){
            echo json_encode(['message'=>'JSON Invalido']);
            exit;
        }

        $username = trim($data['username'] ?? '');
        $password = trim($data['password'] ?? '');

        if(empty($username) || empty($password)){
            echo json_encode(['message' => 'Usuario y contraseña son obligatorios']);
            exit;
        }

        $stmt = $pdo->prepare('
            SELECT a.*, u.nombre, u.correo_sena 
            FROM administradores a 
            JOIN usuarios u ON a.usuario_id = u.id 
            WHERE a.username = ? AND a.estado = "activo"
        ');
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password'])) {
            session_start();
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_nombre'] = $admin['nombre'];
            $_SESSION['login_time'] = time();
            
            echo json_encode([
                'message' => 'Login exitoso',
                'success' => true,
                'admin' => [
                    'id' => $admin['id'],
                    'username' => $admin['username'],
                    'nombre' => $admin['nombre']
                ]
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['message' => 'Usuario o contraseña incorrectos']);
        }
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