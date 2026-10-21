# Offline Maps Guide

MeshBeacon supports fully offline map rendering using the standard `.mbtiles` format. Your offline map must be a **Vector** MBTiles file using the **OpenMapTiles** schema (the de-facto standard layer naming used by most vector tile generators). Raster MBTiles (pre-rendered PNG/JPEG images) are not supported — uploads are rejected unless they declare `format = pbf` in their metadata.

You can upload your `.mbtiles` file directly through the **Settings > Offline Map** dashboard. Files up to 500MB are supported.

---

## How to Generate an Offline Vector Map using Planetiler

The simplest, free, open-source way to generate a compatible Vector `.mbtiles` file for any region in the world is **[Planetiler](https://github.com/onthegomap/planetiler)** — it builds an OpenMapTiles-schema vector tileset directly from an OpenStreetMap extract with a single command, no database or server setup required.

### Prerequisites
1. Install a Java Runtime (JRE) 17 or newer.
2. Download the latest `planetiler.jar` from the [Planetiler releases page](https://github.com/onthegomap/planetiler/releases).
3. Download an OpenStreetMap `.osm.pbf` extract for your region from [Geofabrik](https://download.geofabrik.de/) (pick the smallest extract that covers your area of interest — country, state, or city level — to keep the build fast and the output file small).

### Step 1: Run Planetiler
Open a terminal in the folder containing `planetiler.jar` and your downloaded `.osm.pbf` extract, then run:

```bash
java -jar planetiler.jar --osm-path=my-region.osm.pbf --output=map.mbtiles --minzoom=0 --maxzoom=14
```

- Replace `my-region.osm.pbf` with your actual downloaded extract filename.
- `--maxzoom=14` is a good balance between detail and file size (MapLibre continues rendering the vector geometry sharply if a user zooms in further than this).

Planetiler will print progress as it processes the extract, and produce `map.mbtiles` when finished.

### Step 2: Upload to MeshBeacon
1. Open your MeshBeacon dashboard and navigate to **Settings > Offline Map**.
2. Click the upload area, select your newly generated `map.mbtiles` file, and upload it.
3. Ensure the **Prefer Offline Map** toggle is enabled.
4. The dashboard, GPS, and Kiosk maps will now automatically serve your offline vector map tiles at lightning speed!

---

## Alternative tools

Any tool that outputs an OpenMapTiles-schema Vector MBTiles file will work, including:

- **[OpenMapTiles](https://github.com/openmaptiles/openmaptiles)** — the reference Docker-based toolchain the schema is named after.
- **[Tilemaker](https://github.com/systemed/tilemaker)** — a lightweight C++ alternative with a configurable schema (use its default OpenMapTiles-compatible config).
- **[MapTiler Desktop](https://www.maptiler.com/desktop/)** — a commercial GUI tool that can export OpenMapTiles-schema vector MBTiles.

A tileset generated with a different (non-OpenMapTiles) schema will still upload and serve tiles correctly, but MeshBeacon's built-in map style only recognizes OpenMapTiles layer names (`water`, `landcover`, `landuse`, `park`, `building`, `transportation`, `boundary`, `place`, etc.), so unrecognized layers simply won't be drawn.
