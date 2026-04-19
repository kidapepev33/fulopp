<?php
require_once '../../config/server.php';
require_once 'auth_scope.php';
header('Content-Type: application/json; charset=utf-8');

$authUser = require_auth_user();
$scope = get_user_scope($conn, intval($authUser['id'] ?? 0));

$sql = "
    SELECT rutas.id, rutas.nombre, COUNT(estudiantes.id) as total
    FROM rutas
    LEFT JOIN estudiantes ON estudiantes.ruta_id = rutas.id
";

if ($scope['role'] !== 'admin') {
    $allowed = $scope['allowed_route_ids'];
    if (count($allowed) === 0) {
        echo json_encode([]);
        exit;
    }
    $sql .= " WHERE rutas.id IN (" . build_in_clause_int($allowed) . ")";
}

$sql .= "
    GROUP BY rutas.id
    ORDER BY rutas.nombre ASC
";

$result = $conn->query($sql);
$rutas = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $rutas[] = $row;
    }
}

echo json_encode($rutas);

