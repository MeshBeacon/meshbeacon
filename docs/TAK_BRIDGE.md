# MeshBeacon to TAK CoT Bridge

The MeshBeacon to TAK CoT (Cursor on Target) Bridge is a standalone service that automatically parses GPS tracking events from the MeshBeacon MQTT broker and translates them into CoT XML format. It then broadcasts these XML events to an TAK Multicast network or a specific TAK Server.

This enables field operators using TAK (Android Team Awareness Kit) or WinTAK to seamlessly see the real-time locations of MeshBeacon devices (Ducks) on their maps without any manual intervention.

> **If your target is OpenTAKServer specifically:** don't run this bridge *and* the [OpenTAKServer Encrypted Bridge](OPENTAK_BRIDGE.md) plugin pointed at the same OTS instance. This bridge and the `ots-meshbeacon-bridge` plugin use different `uid` conventions for the same Duck (raw `DeviceID` here vs. `meshbeacon-<duck_id>` there), so OTS would show two separate, unsynchronized markers per device — one plaintext with no SOS/GeoChat/command support, one encrypted with full feature support. Pick one per target: this bridge for generic ATAK/iTAK/WinTAK/plain-OTS reach, or the OpenTAKServer plugin once it's installed there.

## How it works

1. The bridge connects to the local MeshBeacon MQTT broker (e.g., `meshbeacon-mqtt-server-1:1883`) and subscribes to the `hub/event` topic.
2. It filters for JSON messages containing `lat` (latitude) and `lon` (longitude) coordinates.
3. It converts the coordinates and `DeviceID` into a standard CoT `event` XML.
4. It forwards the CoT XML payload via UDP to the configured `TAK_IP` (default: `239.2.3.1`) and `TAK_PORT` (default: `4242`).
5. A JSON summary of the successfully forwarded event is pushed back to the MQTT topic `hub/tak/log`, where the MeshBeacon Laravel application records it.

## Viewing TAK Logs

In the MeshBeacon dashboard, you can monitor the TAK forwarding events in real time by navigating to **TAK Logs** in the sidebar. This dashboard view displays the time, device ID, target IP/Port, and a preview of the generated CoT XML.

## Configuration

The bridge configuration is primarily managed through environment variables in its own `docker-compose.yml` (or `.env`) file. **These are not set in this MeshBeacon app's `.env`** — the bridge is a separate service with its own deployment:

- `MQTT_BROKER`: The hostname or IP of the MQTT broker (default: `localhost`).
- `MQTT_PORT`: The MQTT broker port (default: `1883`).
- `MQTT_TOPIC`: The topic to subscribe to for MeshBeacon events (default: `hub/event`).
- `TAK_IP`: The multicast IP address or TAK Server IP to send the UDP packets to (default: `239.2.3.1`).
- `TAK_PORT`: The UDP port for TAK multicast (default: `4242`).

## Connecting the bridge to this MeshBeacon instance

`MQTT_BROKER`/`MQTT_PORT` and `TAK_IP`/`TAK_PORT` are two independent pairs — don't confuse them:

- **`MQTT_BROKER`/`MQTT_PORT` — where the bridge reads MeshBeacon events from.** Point these at this app's mosquitto broker (the `mqtt-server` service in [docker-compose.yml](../docker-compose.yml)):
  - *Bridge on the same Docker host*, as a sibling container: set `MQTT_BROKER` to the mosquitto container's name (e.g. `meshbeacon-mqtt-server-1`) and attach the bridge's `docker-compose.yml` to the same Docker network as this stack (e.g. an `external` network shared via `networks:`).
  - *Bridge on a different host/VM*: this app's broker is published on the host at `MQTT_BIND_ADDRESS:MQTT_BIND_PORT` (see this app's `.env`, default `0.0.0.0:1883`) — set `MQTT_BROKER` to that host's reachable IP/hostname and `MQTT_PORT` to the same value as `MQTT_BIND_PORT`.
- **`TAK_IP`/`TAK_PORT` — where the bridge sends CoT XML to.** These have nothing to do with MeshBeacon; they're your TAK Server's IP/port, or the multicast group/port your ATAK/iTAK/WinTAK clients are already listening on. Leave the defaults (`239.2.3.1:4242`, the standard TAK multicast group) unless your deployment uses a unicast TAK Server or a different multicast group.

## Deployment

The bridge runs as a containerized service, separate from this repository:

1. Obtain the bridge's own repository (e.g. `meshbeacon-tak-bridge/`) and its `docker-compose.yml`.
2. Set `MQTT_BROKER`/`MQTT_PORT` per "Connecting the bridge to this MeshBeacon instance" above, and `TAK_IP`/`TAK_PORT` for your TAK deployment.
3. Start it: `docker compose up -d` (run from the bridge's own directory, not this repo).
4. In **this** MeshBeacon app's `.env`, set `TAK_BRIDGE_ENABLED=true` (see `.env.example`) and restart/redeploy the app. This reveals the **TAK Logs** nav link once the bridge is running and publishing to `hub/tak/log`.

To change `TAK_IP` or `TAK_PORT` later, edit the bridge's own `docker-compose.yml` and re-run `docker compose up -d` there — not in this repo.
