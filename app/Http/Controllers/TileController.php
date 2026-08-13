<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PDO;

class TileController extends Controller
{
    public function serveTile($z, $x, $y)
    {
        $mbtilesPath = config('services.map.mbtiles_path');

        if (!$mbtilesPath || !file_exists($mbtilesPath)) {
            return response('', 404);
        }

        try {
            // MBTiles uses TMS tiling scheme for the Y coordinate, where Y=0 is at the bottom.
            // Leaflet by default uses XYZ where Y=0 is at the top.
            // Therefore, we must flip the Y coordinate.
            $tmsY = (pow(2, $z) - 1) - $y;

            $pdo = new PDO('sqlite:' . $mbtilesPath);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->prepare('SELECT tile_data FROM tiles WHERE zoom_level = ? AND tile_column = ? AND tile_row = ?');
            $stmt->execute([$z, $x, $tmsY]);
            $tileData = $stmt->fetchColumn();

            if ($tileData) {
                return response($tileData, 200)
                    ->header('Content-Type', 'image/png')
                    ->header('Cache-Control', 'public, max-age=604800'); // 7 days
            }

        } catch (\Exception $e) {
            Log::error("Failed to serve MBTile: " . $e->getMessage());
        }

        return response('', 404);
    }
}
