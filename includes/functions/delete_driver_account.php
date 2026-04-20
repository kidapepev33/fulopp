<?php
require_once __DIR__ . '/../../config/server.php';
require_once __DIR__ . '/auth_scope.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metodo no permitido']);
    exit;
}

$authUser = require_auth_user();
$scope = get_user_scope($conn, intval($authUser['id'] ?? 0));
if (($scope['role'] ?? 'chofer') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Solo administradores pueden borrar choferes']);
    exit;
}

$driverId = intval($_POST['driver_id'] ?? 0);
if ($driverId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Chofer invalido']);
    exit;
}

$stmt = $conn->prepare("SELECT id FROM chofer WHERE id = ? AND (rol = 'chofer' OR rol IS NULL OR rol = '') LIMIT 1");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'No se pudo validar chofer']);
    exit;
}
$stmt->bind_param('i', $driverId);
$stmt->execute();
$result = $stmt->get_result();
if (!$result || !$result->fetch_assoc()) {
    echo json_encode(['success' => false, 'message' => 'Chofer no encontrado']);
    exit;
}

$deleteStmt = $conn->prepare("DELETE FROM chofer WHERE id = ?");
if (!$deleteStmt) {
    echo json_encode(['success' => false, 'message' => 'No se pudo borrar chofer']);
    exit;
}
$deleteStmt->bind_param('i', $driverId);
$ok = $deleteStmt->execute();

if (!$ok) {
    echo json_encode(['success' => false, 'message' => 'No se pudo borrar chofer']);
    exit;
}

echo json_encode(['success' => true]);


