<?php
require_once '../../config/server.php';
require_once 'auth_scope.php';
header('Content-Type: application/json; charset=utf-8');

$authUser = require_auth_user();
$scope = get_user_scope($conn, intval($authUser['id'] ?? 0));

if (($scope['role'] ?? 'chofer') !== 'admin') {
    http_response_code(403);
    echo json_encode(['student' => null, 'columns' => [], 'routes' => [], 'message' => 'Solo administrador puede editar estudiantes']);
    exit;
}

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
$routesSql = "SELECT id, nombre FROM rutas ORDER BY nombre ASC";
$routesResult = $conn->query($routesSql);
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
