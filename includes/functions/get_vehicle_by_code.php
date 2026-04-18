<?php
session_start();
require_once '../../config/server.php';
require_once 'profile_schema.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['auth_user']) || !is_array($_SESSION['auth_user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesion no valida']);
    exit;
}

ensure_profile_schema($conn);

$code = trim($_GET['codigo_interno'] ?? '');
if ($code === '') {
    echo json_encode(['success' => false, 'message' => 'Codigo interno requerido']);
    exit;
}

$stmt = $conn->prepare(
    "SELECT id, codigo_interno, placa, capacidad, estado
     FROM vehiculos
     WHERE codigo_interno = ?
     LIMIT 1"
);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'No se pudo consultar vehiculo']);
    exit;
}

$stmt->bind_param('s', $code);
$stmt->execute();
$result = $stmt->get_result();
$vehicle = $result ? $result->fetch_assoc() : null;

if (!$vehicle) {
    echo json_encode(['success' => false, 'message' => 'Vehiculo no encontrado']);
    exit;
}

$routes = [];
$routesStmt = $conn->prepare(
    "SELECT r.id, r.nombre
     FROM vehiculo_rutas vr
     INNER JOIN rutas r ON r.id = vr.ruta_id
     WHERE vr.vehiculo_id = ?
     ORDER BY r.nombre ASC"
);
if ($routesStmt) {
    $vehiculoId = intval($vehicle['id']);
    $routesStmt->bind_param('i', $vehiculoId);
    $routesStmt->execute();
    $routesResult = $routesStmt->get_result();
    while ($route = $routesResult->fetch_assoc()) {
        $routes[] = $route;
    }
}

echo json_encode([
    'success' => true,
    'vehicle' => $vehicle,
    'routes' => $routes
]);
