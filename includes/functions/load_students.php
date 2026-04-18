<?php
require_once '../../config/server.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_GET['ruta_id'])) {
    echo json_encode([]);
    exit;
}

$ruta_id = intval($_GET['ruta_id']);

$sql = "SELECT id, foto, cedula, nombre, seccion, becado, codigo_barras FROM estudiantes WHERE ruta_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $ruta_id);
$stmt->execute();
$result = $stmt->get_result();

$students = [];

while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}

echo json_encode($students);
