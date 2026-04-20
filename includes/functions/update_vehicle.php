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
    echo json_encode(['success' => false, 'message' => 'Solo administradores pueden editar vehiculos']);
    exit;
}

$vehicleId = intval($_POST['vehicle_id'] ?? 0);
$placa = strtoupper(trim($_POST['placa'] ?? ''));
$capacidad = intval($_POST['capacidad'] ?? 0);
$estadoRaw = strtolower(trim($_POST['estado'] ?? ''));
$estado = $estadoRaw === 'mantenimiento' ? 'mantenimiento' : 'activo';

if ($vehicleId <= 0 || $placa === '' || $capacidad <= 0) {
    echo json_encode(['success' => false, 'message' => 'Datos de vehiculo incompletos']);
    exit;
}

$vehicleStmt = $conn->prepare(
    "SELECT v.id, v.placa, v.capacidad, v.estado, c.id AS assigned_driver_id,
            TRIM(CONCAT(COALESCE(c.nombre, ''), ' ', COALESCE(c.apellidos, ''))) AS assigned_driver_name
     FROM vehiculos v
     LEFT JOIN chofer c ON c.vehiculo_id = v.id
     WHERE v.id = ?
     LIMIT 1"
);
if (!$vehicleStmt) {
    echo json_encode(['success' => false, 'message' => 'No se pudo validar vehiculo']);
    exit;
}
$vehicleStmt->bind_param('i', $vehicleId);
$vehicleStmt->execute();
$vehicleResult = $vehicleStmt->get_result();
$vehicle = $vehicleResult ? $vehicleResult->fetch_assoc() : null;
if (!$vehicle) {
    echo json_encode(['success' => false, 'message' => 'Vehiculo no encontrado']);
    exit;
}

$duplicateStmt = $conn->prepare("SELECT id FROM vehiculos WHERE placa = ? AND id <> ? LIMIT 1");
if ($duplicateStmt) {
    $duplicateStmt->bind_param('si', $placa, $vehicleId);
    $duplicateStmt->execute();
    $duplicateResult = $duplicateStmt->get_result();
    if ($duplicateResult && $duplicateResult->fetch_assoc()) {
        echo json_encode(['success' => false, 'message' => 'Ya existe otro vehiculo con esa placa']);
        exit;
    }
}

$assignedDriverId = intval($vehicle['assigned_driver_id'] ?? 0);
$assignedDriverName = trim((string)($vehicle['assigned_driver_name'] ?? ''));
$willUnassign = $estado === 'mantenimiento' && $assignedDriverId > 0;

$conn->begin_transaction();
try {
    $updateStmt = $conn->prepare("UPDATE vehiculos SET placa = ?, capacidad = ?, estado = ? WHERE id = ?");
    if (!$updateStmt) {
        throw new Exception('No se pudo actualizar vehiculo');
    }
    $updateStmt->bind_param('sisi', $placa, $capacidad, $estado, $vehicleId);
    if (!$updateStmt->execute()) {
        throw new Exception('No se pudo actualizar vehiculo');
    }

    if ($willUnassign) {
        $unassignStmt = $conn->prepare("UPDATE chofer SET vehiculo_id = NULL WHERE vehiculo_id = ?");
        if (!$unassignStmt) {
            throw new Exception('No se pudo desasignar chofer');
        }
        $unassignStmt->bind_param('i', $vehicleId);
        if (!$unassignStmt->execute()) {
            throw new Exception('No se pudo desasignar chofer');
        }
    }

    $conn->commit();
    echo json_encode([
        'success' => true,
        'unassigned_driver' => $willUnassign,
        'unassigned_driver_name' => $assignedDriverName !== '' ? $assignedDriverName : null
    ]);
} catch (Throwable $error) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $error->getMessage()]);
}


