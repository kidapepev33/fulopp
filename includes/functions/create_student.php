<?php
require_once '../../config/server.php';
header('Content-Type: application/json; charset=utf-8');

function barcode_pattern_code39($char)
{
    static $patterns = [
        '0' => 'nnnwwnwnn', '1' => 'wnnwnnnnw', '2' => 'nnwwnnnnw', '3' => 'wnwwnnnnn',
        '4' => 'nnnwwnnnw', '5' => 'wnnwwnnnn', '6' => 'nnwwwnnnn', '7' => 'nnnwnnwnw',
        '8' => 'wnnwnnwnn', '9' => 'nnwwnnwnn', 'A' => 'wnnnnwnnw', 'B' => 'nnwnnwnnw',
        'C' => 'wnwnnwnnn', 'D' => 'nnnnwwnnw', 'E' => 'wnnnwwnnn', 'F' => 'nnwnwwnnn',
        'G' => 'nnnnnwwnw', 'H' => 'wnnnnwwnn', 'I' => 'nnwnnwwnn', 'J' => 'nnnnwwwnn',
        'K' => 'wnnnnnnww', 'L' => 'nnwnnnnww', 'M' => 'wnwnnnnwn', 'N' => 'nnnnwnnww',
        'O' => 'wnnnwnnwn', 'P' => 'nnwnwnnwn', 'Q' => 'nnnnnnwww', 'R' => 'wnnnnnwwn',
        'S' => 'nnwnnnwwn', 'T' => 'nnnnwnwwn', 'U' => 'wwnnnnnnw', 'V' => 'nwwnnnnnw',
        'W' => 'wwwnnnnnn', 'X' => 'nwnnwnnnw', 'Y' => 'wwnnwnnnn', 'Z' => 'nwwnwnnnn',
        '-' => 'nwnnnnwnw', '.' => 'wwnnnnwnn', ' ' => 'nwwnnnwnn', '$' => 'nwnwnwnnn',
        '/' => 'nwnwnnnwn', '+' => 'nwnnnwnwn', '%' => 'nnnwnwnwn', '*' => 'nwnnwnwnn'
    ];

    return $patterns[$char] ?? null;
}

function generate_barcode_svg_code39($text, $fullPath)
{
    $payload = '*' . strtoupper($text) . '*';
    $narrow = 2;
    $wide = 5;
    $height = 90;
    $quietZone = 10;

    $x = $quietZone;
    $svgBars = [];

    $chars = str_split($payload);
    foreach ($chars as $index => $char) {
        $pattern = barcode_pattern_code39($char);
        if ($pattern === null) {
            return false;
        }

        for ($i = 0; $i < 9; $i++) {
            $isBar = ($i % 2 === 0);
            $width = ($pattern[$i] === 'w') ? $wide : $narrow;
            if ($isBar) {
                $svgBars[] = '<rect x="' . $x . '" y="0" width="' . $width . '" height="' . $height . '" fill="#000" />';
            }
            $x += $width;
        }

        if ($index < count($chars) - 1) {
            $x += $narrow;
        }
    }

    $totalWidth = $x + $quietZone;
    $textY = $height + 20;
    $escapedText = htmlspecialchars(strtoupper($text), ENT_QUOTES, 'UTF-8');

    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $totalWidth . '" height="' . ($height + 28) . '" viewBox="0 0 ' . $totalWidth . ' ' . ($height + 28) . '">';
    $svg .= '<rect width="100%" height="100%" fill="#fff" />';
    $svg .= implode('', $svgBars);
    $svg .= '<text x="' . ($totalWidth / 2) . '" y="' . $textY . '" text-anchor="middle" font-size="14" font-family="Arial, sans-serif" fill="#000">' . $escapedText . '</text>';
    $svg .= '</svg>';

    return file_put_contents($fullPath, $svg) !== false;
}

function generate_barcode_png_code39($text, $fullPath)
{
    if (!function_exists('imagecreatetruecolor')) {
        return false;
    }

    $payload = '*' . strtoupper($text) . '*';
    $narrow = 2;
    $wide = 5;
    $height = 90;
    $quietZone = 10;
    $labelHeight = 24;

    $width = $quietZone * 2;
    $chars = str_split($payload);
    foreach ($chars as $index => $char) {
        $pattern = barcode_pattern_code39($char);
        if ($pattern === null) {
            return false;
        }

        for ($i = 0; $i < 9; $i++) {
            $width += ($pattern[$i] === 'w') ? $wide : $narrow;
        }
        if ($index < count($chars) - 1) {
            $width += $narrow;
        }
    }

    $img = imagecreatetruecolor($width, $height + $labelHeight);
    if (!$img) {
        return false;
    }

    $white = imagecolorallocate($img, 255, 255, 255);
    $black = imagecolorallocate($img, 0, 0, 0);
    imagefill($img, 0, 0, $white);

    $x = $quietZone;
    foreach ($chars as $index => $char) {
        $pattern = barcode_pattern_code39($char);
        for ($i = 0; $i < 9; $i++) {
            $isBar = ($i % 2 === 0);
            $segmentWidth = ($pattern[$i] === 'w') ? $wide : $narrow;
            if ($isBar) {
                imagefilledrectangle($img, $x, 0, $x + $segmentWidth - 1, $height - 1, $black);
            }
            $x += $segmentWidth;
        }
        if ($index < count($chars) - 1) {
            $x += $narrow;
        }
    }

    imagestring($img, 3, max(2, intval(($width - (strlen($text) * 7)) / 2)), $height + 4, strtoupper($text), $black);
    $saved = imagepng($img, $fullPath);
    imagedestroy($img);

    return $saved;
}

function get_barcode_directory()
{
    $imagesDir = realpath(__DIR__ . '/../../assets/images');
    if ($imagesDir === false) {
        return false;
    }

    $barcodeDir = $imagesDir . DIRECTORY_SEPARATOR . 'Qr_student';
    if (!is_dir($barcodeDir) && !mkdir($barcodeDir, 0777, true)) {
        return false;
    }

    return $barcodeDir;
}

function barcode_relative_path($id, $ext)
{
    return '../assets/images/Qr_student/barcode_' . intval($id) . '.' . $ext;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metodo no permitido']);
    exit;
}

$cedula = trim($_POST['cedula'] ?? '');
$nombre = trim($_POST['nombre'] ?? '');
$seccion = trim($_POST['seccion'] ?? '');
$rutaId = intval($_POST['ruta_id'] ?? 0);
$becado = isset($_POST['becado']) && strval($_POST['becado']) === '1' ? 1 : 0;

if ($cedula === '' || $nombre === '' || $seccion === '' || $rutaId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Completa todos los campos requeridos']);
    exit;
}

$cedulaStmt = $conn->prepare("SELECT id FROM estudiantes WHERE cedula = ? LIMIT 1");
if (!$cedulaStmt) {
    echo json_encode(['success' => false, 'message' => 'No se pudo validar la cedula']);
    exit;
}
$cedulaStmt->bind_param("s", $cedula);
$cedulaStmt->execute();
$cedulaResult = $cedulaStmt->get_result();
if ($cedulaResult && $cedulaResult->fetch_assoc()) {
    echo json_encode(['success' => false, 'message' => 'La cedula ya existe en otro estudiante']);
    exit;
}

$routeStmt = $conn->prepare("SELECT id FROM rutas WHERE id = ? LIMIT 1");
if (!$routeStmt) {
    echo json_encode(['success' => false, 'message' => 'No se pudo validar la ruta']);
    exit;
}
$routeStmt->bind_param("i", $rutaId);
$routeStmt->execute();
$routeResult = $routeStmt->get_result();
if (!$routeResult || !$routeResult->fetch_assoc()) {
    echo json_encode(['success' => false, 'message' => 'La ruta seleccionada no existe']);
    exit;
}

$conn->begin_transaction();
$barcodePng = null;
$barcodeSvg = null;

try {
    $tempBarcode = 'TMP-' . uniqid('', true);
    $insertSql = "INSERT INTO estudiantes (cedula, nombre, seccion, ruta_id, becado, codigo_barras) VALUES (?, ?, ?, ?, ?, ?)";
    $insertStmt = $conn->prepare($insertSql);
    if (!$insertStmt) {
        throw new Exception('No se pudo preparar la insercion');
    }

    $insertStmt->bind_param("sssiis", $cedula, $nombre, $seccion, $rutaId, $becado, $tempBarcode);
    if (!$insertStmt->execute()) {
        throw new Exception('No se pudo guardar el estudiante');
    }

    $newId = intval($conn->insert_id);
    if ($newId <= 0) {
        throw new Exception('No se pudo obtener el ID del estudiante');
    }

    $barcode = 'EST-' . str_pad(strval($newId), 4, '0', STR_PAD_LEFT);

    $barcodeStmt = $conn->prepare("UPDATE estudiantes SET codigo_barras = ? WHERE id = ?");
    if (!$barcodeStmt) {
        throw new Exception('No se pudo preparar la actualizacion del codigo');
    }

    $barcodeStmt->bind_param("si", $barcode, $newId);
    if (!$barcodeStmt->execute()) {
        throw new Exception('No se pudo asignar el codigo de barras');
    }

    $barcodeDir = get_barcode_directory();
    if ($barcodeDir === false) {
        throw new Exception('No se pudo preparar el directorio de codigos de barras');
    }

    $barcodePng = $barcodeDir . DIRECTORY_SEPARATOR . 'barcode_' . $newId . '.png';
    $barcodeSvg = $barcodeDir . DIRECTORY_SEPARATOR . 'barcode_' . $newId . '.svg';

    if (file_exists($barcodePng)) {
        @unlink($barcodePng);
    }
    if (file_exists($barcodeSvg)) {
        @unlink($barcodeSvg);
    }

    $generated = generate_barcode_png_code39($barcode, $barcodePng);
    if (!$generated) {
        $generated = generate_barcode_svg_code39($barcode, $barcodeSvg);
    }
    if (!$generated) {
        throw new Exception('No se pudo generar la imagen del codigo de barras');
    }

    $conn->commit();

    $barcodeImage = file_exists($barcodePng)
        ? barcode_relative_path($newId, 'png')
        : barcode_relative_path($newId, 'svg');

    echo json_encode([
        'success' => true,
        'id' => $newId,
        'codigo_barras' => $barcode,
        'barcode_image' => $barcodeImage
    ]);
} catch (Throwable $e) {
    $conn->rollback();
    if ($barcodePng && file_exists($barcodePng)) {
        @unlink($barcodePng);
    }
    if ($barcodeSvg && file_exists($barcodeSvg)) {
        @unlink($barcodeSvg);
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
