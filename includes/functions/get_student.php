<?php
require_once '../../config/server.php';
require_once 'auth_scope.php';
header('Content-Type: application/json; charset=utf-8');

$authUser = require_auth_user();
$scope = get_user_scope($conn, intval($authUser['id'] ?? 0));

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

if ($student && !user_can_access_route($scope, intval($student['ruta_id'] ?? 0))) {
    http_response_code(403);
    echo json_encode(['student' => null, 'columns' => [], 'routes' => [], 'message' => 'No tienes acceso a este estudiante']);
    exit;
}

$columns = [];
$columnsResult = $conn->query("SHOW COLUMNS FROM estudiantes");
if ($columnsResult) {
    while ($column = $columnsResult->fetch_assoc()) {
        $columns[] = $column;
    }
}

$routes = [];
$routesSql = "SELECT id, nombre FROM rutas";
if ($scope['role'] !== 'admin') {
    $allowed = $scope['allowed_route_ids'];
    if (count($allowed) === 0) {
        $routesSql .= " WHERE 1 = 0";
    } else {
        $routesSql .= " WHERE id IN (" . build_in_clause_int($allowed) . ")";
    }
}
$routesSql .= " ORDER BY nombre ASC";
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
