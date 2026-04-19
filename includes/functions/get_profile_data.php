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
$vehiclesResult = $conn->query(
    "SELECT v.id, v.codigo_interno, v.placa, v.capacidad, v.estado,
            c.id AS assigned_driver_id,
            TRIM(CONCAT(COALESCE(c.nombre, ''), ' ', COALESCE(c.apellidos, ''))) AS assigned_driver_name
     FROM vehiculos v
     LEFT JOIN chofer c ON c.vehiculo_id = v.id
     ORDER BY v.codigo_interno ASC"
);
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
if (($profile['rol'] ?? 'chofer') !== 'admin') {
    $routeStmt = $conn->prepare(
        "SELECT r.id, r.nombre
         FROM chofer_rutas cr
         INNER JOIN rutas r ON r.id = cr.ruta_id
         WHERE cr.chofer_id = ?
         ORDER BY r.nombre ASC"
    );
    if ($routeStmt) {
        $choferId = intval($profile['id'] ?? 0);
        $routeStmt->bind_param('i', $choferId);
        $routeStmt->execute();
        $routeResult = $routeStmt->get_result();
        while ($route = $routeResult->fetch_assoc()) {
            $assignedRoutes[] = $route;
        }
    }
}

$drivers = [];
if (($profile['rol'] ?? 'chofer') === 'admin') {
    $driversSql = "
        SELECT c.id, c.nombre, c.apellidos, c.email, c.rol, c.vehiculo_id,
               v.codigo_interno, v.placa, v.capacidad, v.estado,
               GROUP_CONCAT(DISTINCT r.id ORDER BY r.nombre ASC SEPARATOR ',') AS route_ids,
               GROUP_CONCAT(DISTINCT r.nombre ORDER BY r.nombre ASC SEPARATOR '||') AS route_names
        FROM chofer c
        LEFT JOIN vehiculos v ON v.id = c.vehiculo_id
        LEFT JOIN chofer_rutas cr ON cr.chofer_id = c.id
        LEFT JOIN rutas r ON r.id = cr.ruta_id
        WHERE (c.rol = 'chofer' OR c.rol IS NULL OR c.rol = '')
        GROUP BY c.id, c.nombre, c.apellidos, c.email, c.rol, c.vehiculo_id, v.codigo_interno, v.placa, v.capacidad, v.estado
        ORDER BY c.nombre ASC, c.apellidos ASC
    ";

    $driversResult = $conn->query($driversSql);
    if ($driversResult) {
        while ($driver = $driversResult->fetch_assoc()) {
            $routeIds = trim((string)($driver['route_ids'] ?? ''));
            $routeNames = trim((string)($driver['route_names'] ?? ''));
            $driverRoutes = [];

            if ($routeIds !== '' && $routeNames !== '') {
                $ids = explode(',', $routeIds);
                $names = explode('||', $routeNames);
                $max = min(count($ids), count($names));
                for ($i = 0; $i < $max; $i++) {
                    $driverRoutes[] = [
                        'id' => intval($ids[$i]),
                        'nombre' => $names[$i]
                    ];
                }
            }

            unset($driver['route_ids'], $driver['route_names']);
            $driver['routes'] = $driverRoutes;
            $drivers[] = $driver;
        }
    }
}

echo json_encode([
    'success' => true,
    'profile' => $profile,
    'assigned_routes' => $assignedRoutes,
    'routes' => $routes,
    'vehicles' => $vehicles,
    'drivers' => $drivers
]);
