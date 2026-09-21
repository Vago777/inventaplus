<?php 
require_once __DIR__ . "/connection.php";
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

$method = $_SERVER['REQUEST_METHOD'];

switch($method){
    case "GET":
        if (isset($_SESSION['admin_id']) && isset($_SESSION['admin_username'])) {
            echo json_encode([
                'logged_in' => true,
                'admin' => [
                    'id' => $_SESSION['admin_id'],
                    'username' => $_SESSION['admin_username'],
                    'nombre' => $_SESSION['admin_nombre'] ?? 'Administrador'
                ]
            ]);
        } else {
            echo json_encode(['logged_in' => false]);
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