<?php
require_once '../../config/server.php';

$sql = "
SELECT rutas.id, rutas.nombre, COUNT(estudiantes.id) as total
FROM rutas
LEFT JOIN estudiantes ON estudiantes.ruta_id = rutas.id
GROUP BY rutas.id
ORDER BY rutas.nombre ASC
";

$result = $conn->query($sql);

$rutas = [];

while ($row = $result->fetch_assoc()) {
    $rutas[] = $row;
}

echo json_encode($rutas);