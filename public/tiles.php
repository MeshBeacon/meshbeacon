<?php
$mbtilesPath = __DIR__ . '/../storage/app/map.mbtiles';
if (!file_exists($mbtilesPath)) {
    header("HTTP/1.0 404 Not Found");
    exit;
}
$z = $_SERVER['TILE_Z'] ?? 0;
$x = $_SERVER['TILE_X'] ?? 0;
$y = $_SERVER['TILE_Y'] ?? 0;
try {
    $tmsY = (pow(2, $z) - 1) - $y;
    $pdo = new PDO('sqlite:' . $mbtilesPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->prepare('SELECT tile_data FROM tiles WHERE zoom_level = ? AND tile_column = ? AND tile_row = ?');
    $stmt->execute([$z, $x, $tmsY]);
    $tileData = $stmt->fetchColumn();
    if ($tileData) {
        header('Content-Type: image/png');
        header('Cache-Control: public, max-age=604800');
        echo $tileData;
    } else {
        header("HTTP/1.0 404 Not Found");
    }
} catch (\Exception $e) {
    header("HTTP/1.0 500 Internal Server Error");
}
