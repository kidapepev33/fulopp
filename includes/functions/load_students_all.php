<?php
require_once '../../config/server.php';
header('Content-Type: application/json; charset=utf-8');

$sql = "SELECT id, foto, cedula, nombre, seccion, becado, codigo_barras FROM estudiantes ORDER BY nombre ASC";
$result = $conn->query($sql);

$students = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}

echo json_encode($students);
