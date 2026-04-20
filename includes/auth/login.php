<?php
session_start();
require_once __DIR__ . '/../../config/server.php';
require_once __DIR__ . '/../functions/profile_schema.php';
header('Content-Type: application/json; charset=utf-8');

ensure_profile_schema($conn);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metodo no permitido']);
    exit;
}

$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? (string)$_POST['password'] : '';

if ($email === '' || $password === '') {
    echo json_encode(['success' => false, 'message' => 'Correo y contrasena requeridos']);
    exit;
}

$sql = "SELECT id, nombre, apellidos, email, password, rol, vehiculo_id FROM chofer WHERE email = ? LIMIT 1";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'No se pudo preparar la consulta']);
    exit;
}

$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result ? $result->fetch_assoc() : null;

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Credenciales invalidas']);
    exit;
}

$storedPassword = (string)$user['password'];
$validPassword = password_verify($password, $storedPassword) || hash_equals($storedPassword, $password);

if (!$validPassword) {
    echo json_encode(['success' => false, 'message' => 'Credenciales invalidas']);
    exit;
}

session_regenerate_id(true);
$_SESSION['auth_user'] = [
    'id' => (int)$user['id'],
    'nombre' => $user['nombre'],
    'apellidos' => $user['apellidos'] ?? '',
    'email' => $user['email'],
    'rol' => $user['rol'] ?? 'chofer',
    'vehiculo_id' => isset($user['vehiculo_id']) ? (int)$user['vehiculo_id'] : null
];

echo json_encode(['success' => true, 'redirect' => '/pages/rutas.html']);

