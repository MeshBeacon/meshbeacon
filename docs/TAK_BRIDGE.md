# MeshBeacon to TAK CoT Bridge

The MeshBeacon to TAK CoT (Cursor on Target) Bridge is a standalone service that automatically parses GPS tracking events from the MeshBeacon MQTT broker and translates them into CoT XML format. It then broadcasts these XML events to an TAK Multicast network or a specific TAK Server.

This enables field operators using TAK (Android Team Awareness Kit) or WinTAK to seamlessly see the real-time locations of MeshBeacon devices (Ducks) on their maps without any manual intervention.

## How it works

1. The bridge connects to the local MeshBeacon MQTT broker (e.g., `meshbeacon-mqtt-server-1:1883`) and subscribes to the `hub/event` topic.
2. It filters for JSON messages containing `lat` (latitude) and `lon` (longitude) coordinates.
3. It converts the coordinates and `DeviceID` into a standard CoT `event` XML.
4. It forwards the CoT XML payload via UDP to the configured `TAK_IP` (default: `239.2.3.1`) and `TAK_PORT` (default: `4242`).
5. A JSON summary of the successfully forwarded event is pushed back to the MQTT topic `hub/tak/log`, where the MeshBeacon Laravel application records it.

## Viewing TAK Logs

In the MeshBeacon dashboard, you can monitor the TAK forwarding events in real time by navigating to **TAK Logs** in the sidebar. This dashboard view displays the time, device ID, target IP/Port, and a preview of the generated CoT XML.

## Configuration

The bridge configuration is primarily managed through environment variables in its `docker-compose.yml` file:

- `MQTT_BROKER`: The hostname or IP of the MQTT broker (default: `localhost`).
- `MQTT_PORT`: The MQTT broker port (default: `1883`).
- `MQTT_TOPIC`: The topic to subscribe to for MeshBeacon events (default: `hub/event`).
- `TAK_IP`: The multicast IP address or TAK Server IP to send the UDP packets to (default: `239.2.3.1`).
- `TAK_PORT`: The UDP port for TAK multicast (default: `4242`).

## Deployment

The bridge runs as a containerized service. If you need to make changes to `TAK_IP` or `TAK_PORT`:

1. Navigate to the bridge directory (e.g. `meshbeacon-tak-bridge/`).
2. Edit the `docker-compose.yml` file and adjust the `TAK_IP` and `TAK_PORT` environment variables.
3. Apply the changes by running: `docker compose up -d`.
