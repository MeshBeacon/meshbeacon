# Offline Maps Guide

MeshBeacon supports fully offline map rendering using the standard `.mbtiles` format. To ensure compatibility with our highly optimized Leaflet map integration, your offline map must be a **Raster** MBTiles file (containing pre-rendered PNG or JPEG images). Vector maps (PBF) are not currently supported.

You can upload your `.mbtiles` file directly through the **Settings > Offline Map** dashboard. Files up to 500MB are supported.

---

## How to Download/Generate an Offline Map using QGIS

The most modern and reliable way to generate a compatible Raster `.mbtiles` file for any region in the world is using **QGIS**, a free, open-source professional GIS software.

### Prerequisites
1. Download and install [QGIS](https://qgis.org/) for your operating system.

### Step 1: Add OpenStreetMap to QGIS
1. Open QGIS.
2. Look at the **Browser** panel on the left side.
3. Expand the **XYZ Tiles** folder. 
   - *If "OpenStreetMap" is missing:* Right-click **XYZ Tiles** -> **New Connection...**. Enter `OpenStreetMap` as the name and `https://tile.openstreetmap.org/{z}/{x}/{y}.png` as the URL, then click OK.
4. Double-click on **OpenStreetMap**. The world map will now appear on your screen.
5. Use your mouse to zoom and pan the map to the exact region you want to save (e.g., your city or country).

### Step 2: Generate the MBTiles File
1. Open the Processing Toolbox by clicking **Processing > Toolbox** in the top menu bar (or press `Ctrl+Alt+T`).
2. In the Toolbox search bar on the right side, type `MBTiles`.
3. Double-click the tool called **Generate XYZ tiles (MBTiles)**.
4. Configure the tool settings:
   - **Extent**: Click the `...` button on the far right of the Extent box and select **Calculate from Map Canvas**. (This locks the export to the exact area you are currently viewing).
   - **Minimum zoom**: We recommend `5` or `6` for a good zoomed-out perspective.
   - **Maximum zoom**: We recommend `14` or `15`. (Higher zoom levels offer incredible detail but significantly increase the file size. MeshBeacon will automatically stretch/upscale your highest zoom tiles if users zoom in further, so `14` is a great balance).
   - **Output file**: Click the `...` button, select **Save to File**, and choose a location on your computer to save the `map.mbtiles` file.
5. Click **Run**. QGIS will automatically download all the required map tiles from the internet and package them into your new `.mbtiles` file.

### Step 3: Upload to MeshBeacon
1. Open your MeshBeacon dashboard and navigate to **Settings > Offline Map**.
2. Click the upload area, select your newly generated `.mbtiles` file, and upload it.
3. Ensure the **Prefer Offline Map** toggle is enabled.
4. The dashboard, GPS, and Kiosk maps will now automatically serve your offline map tiles at lightning speed!
