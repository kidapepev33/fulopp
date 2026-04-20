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
    echo json_encode(['success' => false, 'message' => 'Solo administradores pueden editar choferes']);
    exit;
}

$driverId = intval($_POST['driver_id'] ?? 0);
$vehiculoId = intval($_POST['vehiculo_id'] ?? 0);
$rutaIds = $_POST['ruta_ids'] ?? [];
if (!is_array($rutaIds)) {
    $rutaIds = [];
}
$rutaIds = array_values(array_unique(array_filter(array_map('intval', $rutaIds), function ($value) {
    return $value > 0;
})));

if ($driverId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Chofer invalido']);
    exit;
}

$driverStmt = $conn->prepare("SELECT id FROM chofer WHERE id = ? AND (rol = 'chofer' OR rol IS NULL OR rol = '') LIMIT 1");
if (!$driverStmt) {
    echo json_encode(['success' => false, 'message' => 'No se pudo validar chofer']);
    exit;
}
$driverStmt->bind_param('i', $driverId);
$driverStmt->execute();
$driverResult = $driverStmt->get_result();
if (!$driverResult || !$driverResult->fetch_assoc()) {
    echo json_encode(['success' => false, 'message' => 'Chofer no encontrado']);
    exit;
}

if (count($rutaIds) > 2) {
    echo json_encode(['success' => false, 'message' => 'Solo se permiten maximo 2 rutas']);
    exit;
}

if ($vehiculoId > 0) {
    $vehicleStmt = $conn->prepare("SELECT id, estado FROM vehiculos WHERE id = ? LIMIT 1");
    if (!$vehicleStmt) {
        echo json_encode(['success' => false, 'message' => 'No se pudo validar vehiculo']);
        exit;
    }
    $vehicleStmt->bind_param('i', $vehiculoId);
    $vehicleStmt->execute();
    $vehicleResult = $vehicleStmt->get_result();
    $vehicleRow = $vehicleResult ? $vehicleResult->fetch_assoc() : null;
    if (!$vehicleRow) {
        echo json_encode(['success' => false, 'message' => 'Vehiculo no encontrado']);
        exit;
    }
    $vehicleState = strtolower(trim((string)($vehicleRow['estado'] ?? 'activo')));
    if ($vehicleState === 'mantenimiento') {
        echo json_encode(['success' => false, 'message' => 'No puedes asignar un vehiculo en mantenimiento']);
        exit;
    }

    $ownerStmt = $conn->prepare("SELECT id FROM chofer WHERE vehiculo_id = ? AND id <> ? LIMIT 1");
    if ($ownerStmt) {
        $ownerStmt->bind_param('ii', $vehiculoId, $driverId);
        $ownerStmt->execute();
        $ownerResult = $ownerStmt->get_result();
        if ($ownerResult && $ownerResult->fetch_assoc()) {
            echo json_encode(['success' => false, 'message' => 'Ese vehiculo ya esta asignado a otra cuenta']);
            exit;
        }
    }
}

foreach ($rutaIds as $rutaId) {
    $routeStmt = $conn->prepare("SELECT id FROM rutas WHERE id = ? LIMIT 1");
    if (!$routeStmt) {
        echo json_encode(['success' => false, 'message' => 'No se pudo validar rutas']);
        exit;
    }
    $routeStmt->bind_param('i', $rutaId);
    $routeStmt->execute();
    $routeResult = $routeStmt->get_result();
    if (!$routeResult || !$routeResult->fetch_assoc()) {
        echo json_encode(['success' => false, 'message' => 'Una ruta no existe']);
        exit;
    }
}

$conn->begin_transaction();
try {
    if ($vehiculoId > 0) {
        $updateStmt = $conn->prepare("UPDATE chofer SET vehiculo_id = ? WHERE id = ?");
        $updateStmt->bind_param('ii', $vehiculoId, $driverId);
    } else {
        $updateStmt = $conn->prepare("UPDATE chofer SET vehiculo_id = NULL WHERE id = ?");
        $updateStmt->bind_param('i', $driverId);
    }

    if (!$updateStmt || !$updateStmt->execute()) {
        throw new Exception('No se pudo actualizar vehiculo del chofer');
    }

    $deleteRoutesStmt = $conn->prepare("DELETE FROM chofer_rutas WHERE chofer_id = ?");
    if (!$deleteRoutesStmt) {
        throw new Exception('No se pudo limpiar rutas del chofer');
    }
    $deleteRoutesStmt->bind_param('i', $driverId);
    $deleteRoutesStmt->execute();

    if (count($rutaIds) > 0) {
        $insertRouteStmt = $conn->prepare("INSERT INTO chofer_rutas (chofer_id, ruta_id) VALUES (?, ?)");
        if (!$insertRouteStmt) {
            throw new Exception('No se pudieron insertar rutas');
        }
        foreach ($rutaIds as $rutaId) {
            $insertRouteStmt->bind_param('ii', $driverId, $rutaId);
            if (!$insertRouteStmt->execute()) {
                throw new Exception('No se pudo asignar una ruta');
            }
        }
    }

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Throwable $error) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $error->getMessage()]);
}

