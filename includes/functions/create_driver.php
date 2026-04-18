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

$nombre = trim($_POST['nombre'] ?? '');
$apellidos = trim($_POST['apellidos'] ?? '');
$email = strtolower(trim($_POST['email'] ?? ''));
$password = (string)($_POST['password'] ?? '');
$isAdmin = isset($_POST['is_admin']) && strval($_POST['is_admin']) === '1';
$codigoInterno = strtoupper(trim($_POST['codigo_interno'] ?? ''));

$rutaIds = $_POST['ruta_ids'] ?? [];
if (!is_array($rutaIds)) {
    $rutaIds = [];
}
$rutaIds = array_values(array_unique(array_filter(array_map('intval', $rutaIds), function ($value) {
    return $value > 0;
})));

if ($nombre === '' || $apellidos === '' || $email === '' || $password === '') {
    echo json_encode(['success' => false, 'message' => 'Completa todos los datos del chofer']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Correo invalido']);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'La contrasena debe tener minimo 6 caracteres']);
    exit;
}

if ($isAdmin) {
    $codigoInterno = '';
    $rutaIds = [];
}

$vehiculoId = null;
if (!$isAdmin) {
    if ($codigoInterno === '') {
        echo json_encode(['success' => false, 'message' => 'Debes indicar el codigo interno del vehiculo']);
        exit;
    }
    if (count($rutaIds) < 1 || count($rutaIds) > 2) {
        echo json_encode(['success' => false, 'message' => 'Debes seleccionar 1 o 2 rutas para el bus']);
        exit;
    }

    $vehiculoStmt = $conn->prepare("SELECT id FROM vehiculos WHERE codigo_interno = ? LIMIT 1");
    if (!$vehiculoStmt) {
        echo json_encode(['success' => false, 'message' => 'No se pudo validar vehiculo']);
        exit;
    }
    $vehiculoStmt->bind_param('s', $codigoInterno);
    $vehiculoStmt->execute();
    $vehiculoResult = $vehiculoStmt->get_result();
    $vehiculo = $vehiculoResult ? $vehiculoResult->fetch_assoc() : null;
    if (!$vehiculo) {
        echo json_encode(['success' => false, 'message' => 'El codigo interno no pertenece a un vehiculo']);
        exit;
    }

    $vehiculoId = intval($vehiculo['id']);

    $ownerStmt = $conn->prepare("SELECT id FROM chofer WHERE vehiculo_id = ? LIMIT 1");
    if ($ownerStmt) {
        $ownerStmt->bind_param('i', $vehiculoId);
        $ownerStmt->execute();
        $ownerResult = $ownerStmt->get_result();
        if ($ownerResult && $ownerResult->fetch_assoc()) {
            echo json_encode(['success' => false, 'message' => 'Ese vehiculo ya esta asignado a otra cuenta']);
            exit;
        }
    }

    $routeValidation = $conn->prepare("SELECT id FROM rutas WHERE id = ? LIMIT 1");
    if (!$routeValidation) {
        echo json_encode(['success' => false, 'message' => 'No se pudo validar rutas']);
        exit;
    }
    foreach ($rutaIds as $rutaId) {
        $routeValidation->bind_param('i', $rutaId);
        $routeValidation->execute();
        $routeResult = $routeValidation->get_result();
        if (!$routeResult || !$routeResult->fetch_assoc()) {
            echo json_encode(['success' => false, 'message' => 'Una de las rutas no existe']);
            exit;
        }
    }
}

$emailStmt = $conn->prepare("SELECT id FROM chofer WHERE email = ? LIMIT 1");
if ($emailStmt) {
    $emailStmt->bind_param('s', $email);
    $emailStmt->execute();
    $emailResult = $emailStmt->get_result();
    if ($emailResult && $emailResult->fetch_assoc()) {
        echo json_encode(['success' => false, 'message' => 'Ya existe una cuenta con ese correo']);
        exit;
    }
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$rol = $isAdmin ? 'admin' : 'chofer';

$conn->begin_transaction();

try {
    if ($isAdmin) {
        $insertStmt = $conn->prepare(
            "INSERT INTO chofer (nombre, apellidos, email, password, rol, vehiculo_id)
             VALUES (?, ?, ?, ?, ?, NULL)"
        );
    } else {
        $insertStmt = $conn->prepare(
            "INSERT INTO chofer (nombre, apellidos, email, password, rol, vehiculo_id)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
    }

    if (!$insertStmt) {
        throw new Exception('No se pudo preparar la creacion de cuenta');
    }

    if ($isAdmin) {
        $insertStmt->bind_param('sssss', $nombre, $apellidos, $email, $passwordHash, $rol);
    } else {
        $insertStmt->bind_param('sssssi', $nombre, $apellidos, $email, $passwordHash, $rol, $vehiculoId);
    }

    if (!$insertStmt->execute()) {
        throw new Exception('No se pudo guardar la cuenta');
    }

    if (!$isAdmin && $vehiculoId !== null) {
        $deleteRouteStmt = $conn->prepare("DELETE FROM vehiculo_rutas WHERE vehiculo_id = ?");
        if (!$deleteRouteStmt) {
            throw new Exception('No se pudo limpiar rutas previas');
        }
        $deleteRouteStmt->bind_param('i', $vehiculoId);
        $deleteRouteStmt->execute();

        $insertRouteStmt = $conn->prepare("INSERT INTO vehiculo_rutas (vehiculo_id, ruta_id) VALUES (?, ?)");
        if (!$insertRouteStmt) {
            throw new Exception('No se pudo asignar rutas');
        }

        foreach ($rutaIds as $rutaId) {
            $insertRouteStmt->bind_param('ii', $vehiculoId, $rutaId);
            if (!$insertRouteStmt->execute()) {
                throw new Exception('No se pudo asignar una ruta al vehiculo');
            }
        }
    }

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Throwable $error) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $error->getMessage()]);
}
