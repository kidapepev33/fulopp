<?php
require_once __DIR__ . '/../../config/server.php';
require_once __DIR__ . '/auth_scope.php';
header('Content-Type: application/json; charset=utf-8');

$authUser = require_auth_user();
$scope = get_user_scope($conn, intval($authUser['id'] ?? 0));

$sql = "
    SELECT e.id, e.foto, e.cedula, e.nombre, e.seccion, e.becado, e.codigo_barras, e.ruta_id, r.nombre AS ruta_nombre
    FROM estudiantes e
    LEFT JOIN rutas r ON r.id = e.ruta_id
";

if ($scope['role'] !== 'admin') {
    $allowed = $scope['allowed_route_ids'];
    if (count($allowed) === 0) {
        echo json_encode([]);
        exit;
    }
    $sql .= " WHERE e.ruta_id IN (" . build_in_clause_int($allowed) . ")";
}

$sql .= " ORDER BY e.nombre ASC";
$result = $conn->query($sql);

$students = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}

echo json_encode($students);


