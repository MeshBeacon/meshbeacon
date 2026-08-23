<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use PDO;
use PDOException;

/**
 * Reads metadata from the offline map's .mbtiles file.
 *
 * MBTiles is a fixed file format (https://github.com/mapbox/mbtiles-spec)
 * that is always backed by SQLite, regardless of which database engine
 * (`DB_CONNECTION`: sqlite, mysql, pgsql, ...) the rest of the application
 * uses for its own data. The `pdo_sqlite` PHP extension is therefore a
 * hard requirement for offline map support no matter which primary
 * database driver is configured, so failures here are logged rather than
 * silently swallowed.
 */
class MbtilesService
{
    public function sqliteDriverAvailable(): bool
    {
        return in_array('sqlite', PDO::getAvailableDrivers(), true);
    }

    /**
     * Returns the mbtiles file's declared max zoom level, or $default if
     * the file is missing, unreadable, or the pdo_sqlite driver isn't
     * installed.
     */
    public function getMaxNativeZoom(string $mbtilesPath, int $default = 19): int
    {
        if (!file_exists($mbtilesPath)) {
            return $default;
        }

        if (!$this->sqliteDriverAvailable()) {
            Log::warning('Offline map metadata unavailable: the pdo_sqlite PHP extension is not installed.', [
                'mbtiles_path' => $mbtilesPath,
            ]);

            return $default;
        }

        try {
            $pdo = new PDO('sqlite:' . $mbtilesPath);
            $value = $pdo->query("SELECT value FROM metadata WHERE name = 'maxzoom'")->fetchColumn();

            return is_numeric($value) ? (int) $value : $default;
        } catch (PDOException $e) {
            Log::warning('Failed to read maxzoom metadata from mbtiles file.', [
                'mbtiles_path' => $mbtilesPath,
                'error' => $e->getMessage(),
            ]);

            return $default;
        }
    }
}
