<?php
session_start();
require_once '../../config/server.php';
require_once 'profile_schema.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metodo no permitido']);
    exit;
}

if (!isset($_SESSION['auth_user']) || !is_array($_SESSION['auth_user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesion no valida']);
    exit;
}

ensure_profile_schema($conn);

$placa = strtoupper(trim($_POST['placa'] ?? ''));
$capacidad = intval($_POST['capacidad'] ?? 0);
$estado = trim($_POST['estado'] ?? '');
$codigoInterno = strtoupper(trim($_POST['codigo_interno'] ?? ''));

$rutaIds = $_POST['ruta_ids'] ?? [];
if (!is_array($rutaIds)) {
    $rutaIds = [];
}
$rutaIds = array_values(array_unique(array_filter(array_map('intval', $rutaIds), function ($value) {
    return $value > 0;
})));

if ($placa === '' || $capacidad <= 0 || $estado === '' || $codigoInterno === '') {
    echo json_encode(['success' => false, 'message' => 'Completa todos los campos obligatorios']);
    exit;
}

if (count($rutaIds) < 1 || count($rutaIds) > 2) {
    echo json_encode(['success' => false, 'message' => 'Debes asignar 1 o 2 rutas al vehiculo']);
    exit;
}

$checkStmt = $conn->prepare("SELECT id FROM vehiculos WHERE placa = ? OR codigo_interno = ? LIMIT 1");
if ($checkStmt) {
    $checkStmt->bind_param('ss', $placa, $codigoInterno);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    if ($checkResult && $checkResult->fetch_assoc()) {
        echo json_encode(['success' => false, 'message' => 'La placa o el codigo interno ya existen']);
        exit;
    }
}

$routeStmt = $conn->prepare("SELECT id FROM rutas WHERE id = ? LIMIT 1");
if (!$routeStmt) {
    echo json_encode(['success' => false, 'message' => 'No se pudo validar rutas']);
    exit;
}

foreach ($rutaIds as $rutaId) {
    $routeStmt->bind_param('i', $rutaId);
    $routeStmt->execute();
    $routeResult = $routeStmt->get_result();
    if (!$routeResult || !$routeResult->fetch_assoc()) {
        echo json_encode(['success' => false, 'message' => 'Una de las rutas no existe']);
        exit;
    }
}

$conn->begin_transaction();

try {
    $insertStmt = $conn->prepare(
        "INSERT INTO vehiculos (placa, capacidad, estado, codigo_interno) VALUES (?, ?, ?, ?)"
    );
    if (!$insertStmt) {
        throw new Exception('No se pudo preparar la insercion del vehiculo');
    }

    $insertStmt->bind_param('siss', $placa, $capacidad, $estado, $codigoInterno);
    if (!$insertStmt->execute()) {
        throw new Exception('No se pudo guardar el vehiculo');
    }

    $vehiculoId = intval($conn->insert_id);
    if ($vehiculoId <= 0) {
        throw new Exception('No se pudo obtener el ID del vehiculo');
    }

    $insertRouteStmt = $conn->prepare("INSERT INTO vehiculo_rutas (vehiculo_id, ruta_id) VALUES (?, ?)");
    if (!$insertRouteStmt) {
        throw new Exception('No se pudo preparar rutas de vehiculo');
    }

    foreach ($rutaIds as $rutaId) {
        $insertRouteStmt->bind_param('ii', $vehiculoId, $rutaId);
        if (!$insertRouteStmt->execute()) {
            throw new Exception('No se pudo asignar una ruta al vehiculo');
        }
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'vehicle' => [
            'id' => $vehiculoId,
            'placa' => $placa,
            'capacidad' => $capacidad,
            'estado' => $estado,
            'codigo_interno' => $codigoInterno
        ]
    ]);
} catch (Throwable $error) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $error->getMessage()]);
}
