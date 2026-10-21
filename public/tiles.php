<?php
$mbtilesPath = __DIR__ . '/../storage/app/map.mbtiles';
if (!file_exists($mbtilesPath)) {
    header("HTTP/1.0 404 Not Found");
    exit;
}
// The .mbtiles file is a SQLite container by format specification, always,
// regardless of the app's main DB_CONNECTION driver (sqlite/mysql/pgsql).
// Guard explicitly so a deployment that trimmed PHP extensions to match its
// main DB engine gets a clear log line instead of a bare fatal error.
if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
    error_log('tiles.php: the pdo_sqlite PHP extension is not installed; offline map tiles cannot be served.');
    header("HTTP/1.0 500 Internal Server Error");
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
        // MBTiles vector (PBF) tiles are conventionally gzip-compressed in
        // storage; the browser only auto-decompresses on fetch if told via
        // Content-Encoding, otherwise MapLibre's MVT parser gets raw gzip
        // bytes instead of protobuf and silently fails to render.
        $isGzipped = substr($tileData, 0, 2) === "\x1f\x8b";
        header('Content-Type: application/x-protobuf');
        header('Cache-Control: public, max-age=604800');
        if ($isGzipped) {
            header('Content-Encoding: gzip');
        }
        echo $tileData;
    } else {
        header("HTTP/1.0 404 Not Found");
    }
} catch (\Exception $e) {
    error_log('tiles.php: failed to read tile from mbtiles file: ' . $e->getMessage());
    header("HTTP/1.0 500 Internal Server Error");
}
