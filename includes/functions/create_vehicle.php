<?php
session_start();
require_once '../../config/server.php';
require_once 'auth_scope.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metodo no permitido']);
    exit;
}

$authUser = require_auth_user();
$scope = get_user_scope($conn, intval($authUser['id'] ?? 0));

if ($scope['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Solo administradores pueden crear vehiculos']);
    exit;
}

$placa = strtoupper(trim($_POST['placa'] ?? ''));
$capacidad = intval($_POST['capacidad'] ?? 0);
$estadoRaw = strtolower(trim($_POST['estado'] ?? ''));
$estado = $estadoRaw === 'mantenimiento' ? 'mantenimiento' : 'activo';
$codigoInterno = strtoupper(trim($_POST['codigo_interno'] ?? ''));

if ($placa === '' || $capacidad <= 0 || $codigoInterno === '') {
    echo json_encode(['success' => false, 'message' => 'Completa todos los campos obligatorios']);
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
