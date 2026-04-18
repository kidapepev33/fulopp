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

$userId = intval($_SESSION['auth_user']['id'] ?? 0);
if ($userId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Usuario no valido']);
    exit;
}

$routes = [];
$routesResult = $conn->query("SELECT id, nombre FROM rutas ORDER BY nombre ASC");
if ($routesResult) {
    while ($route = $routesResult->fetch_assoc()) {
        $routes[] = $route;
    }
}

$vehicles = [];
$vehiclesResult = $conn->query("SELECT id, codigo_interno, placa, capacidad, estado FROM vehiculos ORDER BY codigo_interno ASC");
if ($vehiclesResult) {
    while ($vehicle = $vehiclesResult->fetch_assoc()) {
        $vehicles[] = $vehicle;
    }
}

$profileStmt = $conn->prepare(
    "SELECT c.id, c.nombre, c.apellidos, c.email, c.rol, c.vehiculo_id,
            v.codigo_interno, v.placa, v.capacidad, v.estado
     FROM chofer c
     LEFT JOIN vehiculos v ON v.id = c.vehiculo_id
     WHERE c.id = ?
     LIMIT 1"
);

if (!$profileStmt) {
    echo json_encode(['success' => false, 'message' => 'No se pudo preparar el perfil']);
    exit;
}

$profileStmt->bind_param('i', $userId);
$profileStmt->execute();
$profileResult = $profileStmt->get_result();
$profile = $profileResult ? $profileResult->fetch_assoc() : null;

if (!$profile) {
    echo json_encode(['success' => false, 'message' => 'No se encontro la cuenta']);
    exit;
}

$assignedRoutes = [];
if (!empty($profile['vehiculo_id'])) {
    $routeStmt = $conn->prepare(
        "SELECT r.id, r.nombre
         FROM vehiculo_rutas vr
         INNER JOIN rutas r ON r.id = vr.ruta_id
         WHERE vr.vehiculo_id = ?
         ORDER BY r.nombre ASC"
    );
    if ($routeStmt) {
        $vehiculoId = intval($profile['vehiculo_id']);
        $routeStmt->bind_param('i', $vehiculoId);
        $routeStmt->execute();
        $routeResult = $routeStmt->get_result();
        while ($route = $routeResult->fetch_assoc()) {
            $assignedRoutes[] = $route;
        }
    }
}

echo json_encode([
    'success' => true,
    'profile' => $profile,
    'assigned_routes' => $assignedRoutes,
    'routes' => $routes,
    'vehicles' => $vehicles
]);

