# MeshBeacon

MeshBeacon is a mesh-network disaster response and incident management platform. It receives telemetry from ClusterDuck or MamaDuck LoRa deployments over MQTT, stores the records in Laravel, and gives authenticated operators a dashboard for incident response, GPS, status messages, reports, and responder management.

The application runs in three modes:

| Mode | Database | Central URL | Use case |
| --- | --- | --- | --- |
| Central | MySQL or PostgreSQL | Empty | A central monitoring and ingestion server |
| Offline | SQLite | Empty | A standalone field deployment with no upstream server |
| Hybrid field node | SQLite | Set | A field deployment that stores locally and forwards records when online |

## Features

- MQTT ingestion from the `hub/event` topic.
- MQTT commands on the `hub/command` topic for acknowledgements, status messages, broadcasts, and GPS requests.
- Durable Laravel database queues for message processing, outbound commands, scheduled GPS polling, and hybrid synchronization.
- SOS incident tracking with acknowledgement, status, notes, assignment, resolution, and retransmission handling.
- GPS history, map links, device health, mesh topology, telemetry history, and CSV or print reports.
- Optional Telegram SOS alerts and responder account linking.
- Read-only central dashboards for hybrid deployments.
- A multi-stage Docker image build for Linux `amd64` and `arm64` hosts.
- A one-line installer for Linux and a native FreeBSD installation path.

## Architecture

```text
LoRa devices
    |
ClusterDuck or MamaDuck gateway
    |
    | MQTT: hub/event
    v
Mosquitto broker -> mqtt-worker -> ProcessMqttMessage
                                      |
                                      +-> cluster_data database record
                                      +-> default queue jobs
                                      +-> SOS acknowledgements
                                      +-> Telegram alerts
                                      +-> hybrid sync outbox

Operator browser -> Nginx -> PHP-FPM -> Laravel dashboard
                                      |
                                      +-> MQTT: hub/command
```

`MqttSubscribe` subscribes to `hub/event` and dispatches `ProcessMqttMessage`. The job validates the message shape, normalizes the path, writes `cluster_data`, and dispatches follow-up work. Dashboard requests derive SOS and GPS views from those records. `MqttService` publishes commands to `hub/command`.

An incoming event normally contains these fields:

```json
{
  "MessageID": "unique-message-id",
  "eventType": "status",
  "payload": {
    "DeviceID": "duck-01",
    "Message": "MSG,TEXT:Ready",
    "path": ["duck-01", "gateway-01"],
    "origin": "duck-01",
    "destination": "gateway-01",
    "hops": 1,
    "duckType": 1
  }
}
```

## Quick Install

Review `install.sh` before piping it to a shell. On Linux, the installer expects Docker Engine and the Docker Compose v2 plugin. It clones this repository, creates `.env`, generates `APP_KEY` and first-admin credentials, builds the application image locally, and starts the stack.

```sh
curl -fsSL https://raw.githubusercontent.com/9M2PJU/meshbeacon/main/install.sh | sh
```

The default directory is `$HOME/meshbeacon`. Set values before invoking the shell when you need a different location or image:

```sh
curl -fsSL https://raw.githubusercontent.com/9M2PJU/meshbeacon/main/install.sh | \
  MESHBEACON_INSTALL_DIR=/opt/meshbeacon MESHBEACON_PORT=8080 sh
```

The installer prints the generated administrator password once. Store it in a password manager and change it after the first login. The installer does not update an existing checkout unless `MESHBEACON_UPDATE=1` is set.

On FreeBSD, run the same installer with root privileges:

```sh
curl -fsSL https://raw.githubusercontent.com/9M2PJU/meshbeacon/main/install.sh | sudo sh
```

The FreeBSD path installs PHP, Composer, Node, Mosquitto, and the required PHP extensions with `pkg`. It builds the application from source and starts the web server and workers with FreeBSD `daemon`. The first-run web service uses `php artisan serve`; place it behind a production web server and a process supervisor before exposing it to the Internet. Package names default to `php84` and `node22`; override them with `MESHBEACON_PHP_PACKAGE` or `MESHBEACON_NODE_PACKAGE` when the local FreeBSD repository uses different names.

## Docker Deployment

### Requirements

- Docker Engine with Compose v2.
- Composer authentication if the locked Livewire Flux package requires it.

Compose builds `Dockerfile.compose` locally. The Dockerfile uses multi-architecture Alpine, PHP, Node, and Composer base images, so it can build for `linux/amd64` or `linux/arm64` with Docker Buildx. FreeBSD does not appear as a Docker platform because the image contains a Linux userland and requires a Linux kernel; use the native FreeBSD installer instead.

### Manual setup

```sh
git clone https://github.com/9M2PJU/meshbeacon.git
cd meshbeacon
cp .env.example .env
```

Set a strong value for `MESHBEACON_ADMIN_PASSWORD` in `.env`, then start the stack:

```sh
docker buildx build --load --provenance=false --file Dockerfile.compose --tag meshbeacon:local .
docker compose up -d --no-build
docker compose ps
docker compose logs -f app mqtt-worker queue-worker
```

Open `http://localhost:8080` unless `MESHBEACON_PORT` or `APP_URL` has been changed. The `migrate` service applies migrations and seeds the first administrator before the PHP application starts. The `permissions` service prepares the persistent volume permissions and then exits; that exited state is expected.

### Services

| Service | Responsibility |
| --- | --- |
| `webserver` | Nginx on the configured host port |
| `app` | PHP-FPM Laravel application |
| `migrate` | Database migrations and initial administrator seeding |
| `permissions` | Ownership and permissions for persistent volumes |
| `mqtt-worker` | MQTT subscription and message dispatch |
| `queue-worker` | `sync` and `default` Laravel queues |
| `scheduler` | Runs scheduled commands every 60 seconds |
| `mqtt-server` | Mosquitto broker on the configured MQTT port |

Compose uses named volumes for the SQLite database, Laravel storage, built public assets, and Mosquitto data/logs. The application image contains the source and dependencies, so Compose does not bind-mount the project source over the image.

### Updating

```sh
git pull --ff-only
docker buildx build --load --provenance=false --file Dockerfile.compose --tag meshbeacon:local .
docker compose up -d --no-build
docker compose logs -f app
```

Keep `.env` outside version control. Back up the `app-database` and `app-storage` volumes before upgrades that contain important incident data.

## Configuration

The complete template is [.env.example](.env.example). The settings below control the main deployment behaviors.

| Variable | Description |
| --- | --- |
| `APP_URL` | Public URL used for links and the Telegram webhook |
| `APP_KEY` | Laravel encryption key; keep it stable and secret |
| `APP_DEBUG` | Use `false` in any shared or production deployment |
| `MESHBEACON_IMAGE` | Container image used by Compose |
| `MESHBEACON_PORT` | Host port for the web interface, default `8080` |
| `MESHBEACON_ADMIN_EMAIL` | Email for the first seeded administrator |
| `MESHBEACON_ADMIN_PASSWORD` | Password for the first seeded administrator |
| `DB_CONNECTION` | `sqlite`, `mysql`, or `pgsql` |
| `DB_DATABASE` | SQLite file path when using SQLite |
| `QUEUE_CONNECTION` | Use `database` for the supplied worker stack |
| `MQTT_HOST` | Broker hostname, normally `mqtt-server` in Compose |
| `MQTT_PORT` | Broker port, normally `1883` |
| `MQTT_AUTH_USERNAME` / `MQTT_AUTH_PASSWORD` | Optional broker credentials |
| `MQTT_TLS_ENABLED` | Enable MQTT TLS when the broker is configured for TLS |
| `CENTRAL_DMS_URL` | Base URL of the central MeshBeacon server in hybrid mode |
| `CENTRAL_DMS_TOKEN` | Shared bearer token for hybrid ingestion |
| `DASHBOARD_READONLY` | Reject incident-dispatch writes on a central aggregator |
| `TELEGRAM_BOT_TOKEN` | Enables Telegram alert delivery |
| `TELEGRAM_BOT_USERNAME` | Bot username used for responder linking |
| `TELEGRAM_WEBHOOK_SECRET` | Secret used to protect the Telegram webhook |

For MySQL or PostgreSQL, set the usual `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` values in addition to `DB_CONNECTION`. The Docker Compose file is optimized for SQLite field deployments; run a central production database as a separately managed service.

## Hybrid Synchronization

Hybrid mode uses a store-and-forward outbox:

1. The field node writes every MQTT record to local SQLite first.
2. The record receives `synced=false` and a `SyncRecordToCloud` job enters the `sync` queue.
3. The job posts the record to the central server's `POST /api/ingest` endpoint.
4. A successful response changes `synced` to `true` and records `synced_at`.
5. Network failures retry with increasing delays, preserving the local record during an outage.

On the central server, set:

```env
DB_CONNECTION=mysql
CENTRAL_DMS_URL=
CENTRAL_DMS_TOKEN=use-the-same-long-random-token-on-field-nodes
DASHBOARD_READONLY=true
```

On each field node, set:

```env
DB_CONNECTION=sqlite
CENTRAL_DMS_URL=https://central.example.org
CENTRAL_DMS_TOKEN=use-the-same-long-random-token-on-the-central-server
```

`CENTRAL_DMS_URL` must be the base URL without `/api/ingest`. The `queue-worker` must process the `sync` queue; the supplied Compose file already uses `--queue=sync,default`. Full operational notes and rollback details are in [docs/HYBRID_DEPLOYMENT.md](docs/HYBRID_DEPLOYMENT.md).

Check the central endpoint and token with:

```sh
docker compose exec app php artisan route:list --path=api/ingest
docker compose logs -f queue-worker
```

The ingestion endpoint is idempotent for the `(duck_id, message_id)` pair, so a retry cannot create a duplicate telemetry row.

## Telegram Alerts

Telegram is optional. To enable it:

1. Create a bot with `@BotFather` and copy its token and username.
2. Set `TELEGRAM_BOT_TOKEN`, `TELEGRAM_BOT_USERNAME`, and a random `TELEGRAM_WEBHOOK_SECRET`.
3. Set `APP_URL` to a public HTTPS URL.
4. Register the webhook:

   ```sh
   docker compose exec app php artisan telegram:set-webhook
   ```

5. Each responder generates a Telegram link token from their profile settings and sends it to the bot. Linked responders receive SOS alerts with the device and map information.

Telegram requires an HTTPS webhook reachable from the public Internet. Leave `TELEGRAM_BOT_TOKEN` empty to disable Telegram processing.

## Operator Guide

After signing in, the authenticated dashboard provides:

- Live device and message views.
- SOS incident acknowledgement, assignment, notes, status changes, and resolution.
- Status messages and broadcasts sent through MQTT.
- GPS requests and per-device polling intervals.
- Device health, topology, timeline, hourly statistics, and reports.
- CSV and print exports for incident and period reporting.
- Profile, password, two-factor authentication, locale, user, and Telegram settings.

The kiosk view is intended for a display that needs a simplified incident overview. The central read-only mode keeps monitoring views available while the server-side middleware rejects dispatch mutations with HTTP 403.

## Security Notes

- Keep `APP_DEBUG=false` outside local development.
- Protect `APP_KEY`, database credentials, MQTT credentials, and hybrid tokens.
- Change the generated administrator password after first login. Do not use the development fallback from older deployments.
- The bundled Mosquitto configuration allows anonymous MQTT connections and binds port `1883` on all interfaces. Restrict the port to the gateway network or replace the configuration with authenticated and TLS-protected MQTT before exposing it beyond a trusted LAN.
- Put the web interface behind HTTPS and a reverse proxy when it is reachable outside a private network.
- Do not commit `.env`, `auth.json`, Composer credentials, database files, or Mosquitto data.
- Review the Livewire Flux license and keep its Composer credentials in a local secret manager or `COMPOSER_AUTH` environment variable.

## Development

Local development requires PHP 8.2 or newer, Composer, Node.js, npm, SQLite, and access to an MQTT broker. The application currently targets Node 22 in the container build.

```sh
composer install
cp .env.example .env
touch database/database.sqlite
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
```

For an interactive development session, `composer run dev` starts Laravel, a queue listener, Pail logs, and Vite. Start the MQTT subscriber separately:

```sh
php artisan app:mqtt-subscribe
php artisan queue:work --queue=sync,default --tries=5 --timeout=0
php artisan schedule:work
npm run dev
```

Run the test suite with:

```sh
composer run test
```

Focused hybrid and read-only coverage is available with:

```sh
php artisan test --filter=HybridSyncTest
php artisan test --filter=DashboardReadonlyTest
```

The repository includes `package-lock.json`, so the Docker build uses `npm ci`. The native FreeBSD installer uses `npm install` to accommodate the local package environment.

## Project Layout

| Path | Purpose |
| --- | --- |
| `app/Console` | MQTT, GPS, Telegram, and Artisan commands |
| `app/Jobs` | Queue jobs for message processing, commands, alerts, and sync |
| `app/Http` | Controllers, API ingestion, and security middleware |
| `app/Models` | Telemetry, incidents, users, and GPS poll models |
| `database/migrations` | Application schema and indexes |
| `resources/views` and `resources/js` | Dashboard, settings, authentication, and frontend behavior |
| `services/mosquitto` | Broker configuration and native-install runtime directories |
| `services/nginx` | Nginx configuration for the PHP-FPM container |
| `Dockerfile.compose` | Multi-stage production image build |
| `docker-compose.yml` | Application, worker, scheduler, broker, and Nginx services |
| `install.sh` | Linux Docker and native FreeBSD installer |
| `docs/HYBRID_DEPLOYMENT.md` | Store-and-forward deployment guide |

## Container Build

Build the local image for the current host architecture with Compose:

```sh
docker buildx build --load --provenance=false --file Dockerfile.compose --tag meshbeacon:local .
```

For a multi-architecture build, use Docker Buildx and provide a registry destination of your choice:

```sh
docker buildx build \
  --platform linux/amd64,linux/arm64 \
  --file Dockerfile.compose \
  --tag meshbeacon:latest \
  --output type=oci,dest=meshbeacon.oci \
  .
```

The Dockerfile accepts an optional BuildKit secret named `composer_auth` for authenticated Composer installs. Keep credentials out of image layers and the repository.

## Licensing

The first-party MeshBeacon source and documentation are licensed under the Apache License 2.0. See [LICENSE](LICENSE).

The application also distributes and links third-party packages. Each dependency keeps its own license and attribution terms. In particular, the locked `livewire/flux` dependency declares a proprietary license; using or redistributing the resulting application or image requires the appropriate Flux license and Composer access. Review `composer.lock` and the relevant upstream license before distributing a build.

The Apache License applies to MeshBeacon's first-party work and does not relicense third-party dependencies, trademarks, or service names.
