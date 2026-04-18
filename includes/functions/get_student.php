<?php
require_once '../../config/server.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_GET['id'])) {
    echo json_encode(['student' => null, 'columns' => [], 'routes' => []]);
    exit;
}

$id = intval($_GET['id']);

$studentSql = "SELECT * FROM estudiantes WHERE id = ? LIMIT 1";
$studentStmt = $conn->prepare($studentSql);
$studentStmt->bind_param("i", $id);
$studentStmt->execute();
$studentResult = $studentStmt->get_result();
$student = $studentResult->fetch_assoc();

$columns = [];
$columnsResult = $conn->query("SHOW COLUMNS FROM estudiantes");
if ($columnsResult) {
    while ($column = $columnsResult->fetch_assoc()) {
        $columns[] = $column;
    }
}

$routes = [];
$routesResult = $conn->query("SELECT id, nombre FROM rutas ORDER BY nombre ASC");
if ($routesResult) {
    while ($route = $routesResult->fetch_assoc()) {
        $routes[] = $route;
    }
}

echo json_encode([
    'student' => $student ?: null,
    'columns' => $columns,
    'routes' => $routes
]);
