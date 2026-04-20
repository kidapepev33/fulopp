<?php
ini_set('display_errors', '0');
ini_set('html_errors', '0');
error_reporting(E_ALL);

$host = getenv('DB_HOST') ?: 'localhost';
$port = intval(getenv('DB_PORT') ?: 3306);
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$dbname = getenv('DB_NAME') ?: 'proyecto_fulopp';

$conn = @new mysqli($host, $user, $pass, $dbname, $port);

if ($conn->connect_error) {
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'Error de conexion a base de datos',
            'details' => $conn->connect_error
        ]);
        exit;
    }

    die('Error de conexion a base de datos');
}

$conn->set_charset('utf8mb4');
?>
