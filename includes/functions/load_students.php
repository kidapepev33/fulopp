<?php
require_once '../../config/server.php';
require_once 'auth_scope.php';
header('Content-Type: application/json; charset=utf-8');

$authUser = require_auth_user();
$scope = get_user_scope($conn, intval($authUser['id'] ?? 0));

if (!isset($_GET['ruta_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ruta requerida', 'students' => []]);
    exit;
}

$rutaId = intval($_GET['ruta_id']);
if ($rutaId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Ruta invalida', 'students' => []]);
    exit;
}

if (!user_can_access_route($scope, $rutaId)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tienes acceso a esta ruta', 'students' => []]);
    exit;
}

$sql = "SELECT id, foto, cedula, nombre, seccion, becado, codigo_barras FROM estudiantes WHERE ruta_id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'No se pudo cargar estudiantes', 'students' => []]);
    exit;
}

$stmt->bind_param("i", $rutaId);
$stmt->execute();
$result = $stmt->get_result();

$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}

echo json_encode(['success' => true, 'students' => $students]);

