<?php
$host = getenv('DB_HOST') ?: 'localhost';
$port = intval(getenv('DB_PORT') ?: 3306);
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$dbname = getenv('DB_NAME') ?: 'proyecto_fulopp';

$conn = new mysqli($host, $user, $pass, $dbname, $port);

if ($conn->connect_error) {
    die('Conexion fallida: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');
?>
