<?php
require_once __DIR__ . '/../../config/server.php';
require_once __DIR__ . '/auth_scope.php';
header('Content-Type: application/json; charset=utf-8');

$authUser = require_auth_user();
$scope = get_user_scope($conn, intval($authUser['id'] ?? 0));

$codigo = strtoupper(trim($_GET['codigo'] ?? ''));
if ($codigo === '') {
    echo json_encode(['success' => false, 'message' => 'Codigo requerido']);
    exit;
}

$studentStmt = $conn->prepare(
    "SELECT e.id, e.foto, e.cedula, e.nombre, e.seccion, e.becado, e.codigo_barras, e.ruta_id, r.nombre AS ruta_nombre
     FROM estudiantes e
     LEFT JOIN rutas r ON r.id = e.ruta_id
     WHERE UPPER(e.codigo_barras) = ?
     LIMIT 1"
);

if (!$studentStmt) {
    echo json_encode(['success' => false, 'message' => 'No se pudo consultar estudiante']);
    exit;
}

$studentStmt->bind_param('s', $codigo);
$studentStmt->execute();
$studentResult = $studentStmt->get_result();
$student = $studentResult ? $studentResult->fetch_assoc() : null;

if (!$student) {
    echo json_encode(['success' => false, 'message' => 'Codigo no encontrado']);
    exit;
}

$routeId = intval($student['ruta_id'] ?? 0);
$allowed = user_can_access_route($scope, $routeId);

if ($allowed) {
    echo json_encode([
        'success' => true,
        'allowed' => true,
        'student' => $student
    ]);
    exit;
}

$driverName = 'Sin chofer asignado';
$driverStmt = $conn->prepare(
    "SELECT c.nombre, c.apellidos
     FROM chofer c
     INNER JOIN chofer_rutas cr ON cr.chofer_id = c.id
     WHERE cr.ruta_id = ? AND (c.rol = 'chofer' OR c.rol IS NULL OR c.rol = '')
     ORDER BY c.id ASC
     LIMIT 1"
);
if ($driverStmt) {
    $driverStmt->bind_param('i', $routeId);
    $driverStmt->execute();
    $driverResult = $driverStmt->get_result();
    $driver = $driverResult ? $driverResult->fetch_assoc() : null;
    if ($driver) {
        $full = trim(($driver['nombre'] ?? '') . ' ' . ($driver['apellidos'] ?? ''));
        if ($full !== '') {
            $driverName = $full;
        }
    }
}

echo json_encode([
    'success' => true,
    'allowed' => false,
    'student' => $student,
    'route' => [
        'id' => $routeId,
        'nombre' => $student['ruta_nombre'] ?? 'Ruta no definida'
    ],
    'driver' => [
        'nombre' => $driverName
    ]
]);

