<?php
require_once '../../config/server.php';
require_once 'auth_scope.php';
header('Content-Type: application/json; charset=utf-8');

$authUser = require_auth_user();
$scope = get_user_scope($conn, intval($authUser['id'] ?? 0));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metodo no permitido']);
    exit;
}

if (!isset($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID requerido']);
    exit;
}

$id = intval($_POST['id']);

$currentRouteStmt = $conn->prepare("SELECT ruta_id FROM estudiantes WHERE id = ? LIMIT 1");
if (!$currentRouteStmt) {
    echo json_encode(['success' => false, 'message' => 'No se pudo validar estudiante']);
    exit;
}
$currentRouteStmt->bind_param('i', $id);
$currentRouteStmt->execute();
$currentRouteResult = $currentRouteStmt->get_result();
$currentStudent = $currentRouteResult ? $currentRouteResult->fetch_assoc() : null;
if (!$currentStudent) {
    echo json_encode(['success' => false, 'message' => 'Estudiante no encontrado']);
    exit;
}

if (!user_can_access_route($scope, intval($currentStudent['ruta_id'] ?? 0))) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tienes permiso para editar este estudiante']);
    exit;
}
$columnsResult = $conn->query("SHOW COLUMNS FROM estudiantes");

if (!$columnsResult) {
    echo json_encode(['success' => false, 'message' => 'No se pudo leer la estructura de estudiantes']);
    exit;
}

$editableColumns = [];
while ($column = $columnsResult->fetch_assoc()) {
    $field = $column['Field'];
    if ($field === 'id' || $field === 'foto' || $field === 'codigo_barras') {
        continue;
    }
    $editableColumns[$field] = $column;
}

if (isset($_POST['cedula'])) {
    $cedulaValue = trim(strval($_POST['cedula']));
    if ($cedulaValue !== '') {
        $duplicateStmt = $conn->prepare("SELECT id FROM estudiantes WHERE cedula = ? AND id <> ? LIMIT 1");
        if ($duplicateStmt) {
            $duplicateStmt->bind_param("si", $cedulaValue, $id);
            $duplicateStmt->execute();
            $duplicateResult = $duplicateStmt->get_result();
            if ($duplicateResult && $duplicateResult->fetch_assoc()) {
                echo json_encode(['success' => false, 'message' => 'La cedula ya existe en otro estudiante']);
                exit;
            }
        }
    }
}

$setClauses = [];
$params = [];
$types = '';

foreach ($editableColumns as $field => $meta) {
    if (!array_key_exists($field, $_POST)) {
        continue;
    }

    $value = $_POST[$field];

    if ($value === '' && $meta['Null'] === 'YES') {
        $value = null;
    }

    $typeText = strtolower($meta['Type']);
    if (strpos($typeText, 'int') !== false) {
        $types .= 'i';
        $params[] = ($value === null || $value === '') ? null : intval($value);
    } elseif (strpos($typeText, 'decimal') !== false || strpos($typeText, 'float') !== false || strpos($typeText, 'double') !== false) {
        $types .= 'd';
        $params[] = ($value === null || $value === '') ? null : floatval($value);
    } else {
        $types .= 's';
        $params[] = $value;
    }

    $setClauses[] = "`$field` = ?";
}

if (array_key_exists('ruta_id', $_POST)) {
    $targetRoute = intval($_POST['ruta_id']);
    if ($targetRoute > 0 && !user_can_access_route($scope, $targetRoute)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No puedes mover el estudiante a esa ruta']);
        exit;
    }
}

if (isset($_FILES['foto']) && is_array($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    $tmpPath = $_FILES['foto']['tmp_name'];
    $imageInfo = @getimagesize($tmpPath);

    if (!$imageInfo) {
        echo json_encode(['success' => false, 'message' => 'El archivo de foto no es una imagen valida']);
        exit;
    }

    $allowedMimeToExt = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif'
    ];

    $mime = $imageInfo['mime'] ?? '';
    if (!isset($allowedMimeToExt[$mime])) {
        echo json_encode(['success' => false, 'message' => 'Formato de imagen no permitido']);
        exit;
    }

    $extension = $allowedMimeToExt[$mime];
    $imagesDir = realpath(__DIR__ . '/../../assets/images');
    if ($imagesDir === false) {
        echo json_encode(['success' => false, 'message' => 'No se encontro el directorio de imagenes']);
        exit;
    }

    $bannersDir = $imagesDir . DIRECTORY_SEPARATOR . 'banners';
    if (!is_dir($bannersDir) && !mkdir($bannersDir, 0777, true)) {
        echo json_encode(['success' => false, 'message' => 'No se pudo crear el directorio de banners']);
        exit;
    }

    foreach (glob($bannersDir . DIRECTORY_SEPARATOR . 'photo' . $id . '.*') ?: [] as $existingFile) {
        @unlink($existingFile);
    }

    $fileName = 'photo' . $id . '.' . $extension;
    $targetPath = $bannersDir . DIRECTORY_SEPARATOR . $fileName;

    if (!move_uploaded_file($tmpPath, $targetPath)) {
        echo json_encode(['success' => false, 'message' => 'No se pudo guardar la foto']);
        exit;
    }

    $types .= 's';
    $params[] = '../assets/images/banners/' . $fileName;
    $setClauses[] = "`foto` = ?";
}

if (!$setClauses) {
    echo json_encode(['success' => false, 'message' => 'No hay campos para actualizar']);
    exit;
}

$types .= 'i';
$params[] = $id;

$sql = "UPDATE estudiantes SET " . implode(', ', $setClauses) . " WHERE id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'No se pudo preparar la consulta']);
    exit;
}

$stmt->bind_param($types, ...$params);
$ok = $stmt->execute();

if (!$ok) {
    echo json_encode(['success' => false, 'message' => 'No se pudo actualizar']);
    exit;
}

echo json_encode(['success' => true]);
