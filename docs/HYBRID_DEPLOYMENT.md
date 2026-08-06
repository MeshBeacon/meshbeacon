# Hybrid Deployment: Store-and-Forward with Laravel Outbox

## Overview

OpenDMS supports three deployment modes, controlled entirely via the `.env` file:

| Mode | `DB_CONNECTION` | `CENTRAL_DMS_URL` | Description |
|---|---|---|---|
| **Production** | `mysql` / `pgsql` | *(empty)* | This instance IS the central server |
| **Offline standalone** | `sqlite` | *(empty)* | No internet; field team only |
| **Hybrid** | `sqlite` | `https://...` | Field node syncs to central when online |

A central aggregator should also set `DASHBOARD_READONLY=true` so it can
receive synced telemetry without being usable as a second, conflicting
dispatch console — see [Read-Only Dashboard Mode](#central-server-read-only-dashboard-mode)
below.


In hybrid mode, the system uses a **store-and-forward outbox pattern**: every incoming
LoRa message is written to the local SQLite database first, then a background queue
worker attempts to push the record upstream to the central server. The worker retries
indefinitely with exponential backoff, so data accumulated during outages is
automatically delivered when connectivity returns — no operator intervention needed.

---

## How It Works

```
MamaDuck (LoRa)
      │
PapaDuck Gateway (SX1302)
      │ MQTT
      ▼
ProcessMqttMessage (Laravel Job)
      │
      ├─── ClusterData::create()          ← written locally first, always succeeds
      │         synced = false            ← marks record as pending sync
      │
      └─── SyncRecordToCloud::dispatch()  ← queued in same SQLite jobs table
                    │
             queue:work (background process)
                    │
             CENTRAL_DMS_URL set?
             ├─ YES + internet up  ──► POST /api/ingest  ──► synced = true
             └─ YES + offline      ──► retry (30s → 60s → 2m → 5m → 10m)
```

The `synced` column on `cluster_data` has three states:

| Value | Meaning |
|---|---|
| `null` | Sync not applicable (production or fully offline mode) |
| `false` | Pending sync (hybrid mode, not yet delivered to central) |
| `true` | Successfully delivered to central server |

This means the same schema and codebase works in all three modes with no data model changes.

---

## Setup

### 1. Run the migration

```bash
php artisan migrate
```

This adds `synced` (nullable boolean) and `synced_at` (nullable timestamp) to `cluster_data`.

### 2. Configure `.env` on the field node

```env
# Field node running SQLite
DB_CONNECTION=sqlite
DB_DATABASE=/home/pi/opendms/database/field.sqlite
QUEUE_CONNECTION=database

# Central server to sync to (leave empty for offline standalone)
CENTRAL_DMS_URL=https://dms.example.gov.my
CENTRAL_DMS_TOKEN=your-pre-shared-api-token
```

Leave `CENTRAL_DMS_URL` empty (or remove it) for:
- The central production server itself
- A fully offline standalone deployment with no central server

### 3. Install the systemd service

Edit `deploy/systemd/opendms-queue.service` and update `User` and `WorkingDirectory`
to match your deployment path, then:

```bash
sudo cp deploy/systemd/opendms-queue.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable opendms-queue.service
sudo systemctl start opendms-queue.service
```

Check the worker is running:

```bash
sudo systemctl status opendms-queue.service
journalctl -u opendms-queue -f
```

---

## Monitoring Sync State

Count records still pending upload:

```bash
php artisan tinker --execute="echo App\Models\ClusterData::where('synced', false)->count();"
```

Count records successfully synced:

```bash
php artisan tinker --execute="echo App\Models\ClusterData::where('synced', true)->count();"
```

Or add a simple dashboard widget by querying:

```php
ClusterData::selectRaw("
    COUNT(*) FILTER (WHERE synced IS NULL)   AS not_applicable,
    COUNT(*) FILTER (WHERE synced = 0)       AS pending,
    COUNT(*) FILTER (WHERE synced = 1)       AS synced
")->first();
```

---

## Central Server: Ingestion Endpoint

The central DMS exposes `POST /api/ingest`, authenticated by Bearer token, which
accepts the raw `ClusterData` column values as JSON and upserts them
(deduplicated on `duck_id` + `message_id`, so a field node's retried requests
never create duplicate rows). The field node's `SyncRecordToCloud` job
considers any `2xx` response as success.

The central server should be another OpenDMS instance running in production
mode (`DB_CONNECTION=mysql` or `pgsql`, `CENTRAL_DMS_URL` empty).

### Configure `.env` on the central server

Only `CENTRAL_DMS_TOKEN` needs to be set on the central server — it must be the
**same pre-shared token** configured as `CENTRAL_DMS_TOKEN` on every field node
that syncs to it. `CENTRAL_DMS_URL` is left empty here, since the central
server doesn't sync *to* anything else.

```env
# Central server (production mode) — accepts inbound sync from field nodes
DB_CONNECTION=mysql
CENTRAL_DMS_URL=
CENTRAL_DMS_TOKEN=your-pre-shared-api-token
```

If `CENTRAL_DMS_TOKEN` is left empty, `/api/ingest` responds `503 Service
Unavailable` for every request — the endpoint refuses to run unconfigured
rather than silently accepting unauthenticated data.

### Configure `.env` on each field/remote node

Already covered above in [Configure `.env` on the field node](#2-configure-env-on-the-field-node):
set `CENTRAL_DMS_URL` to the central server's base URL (no trailing
`/api/ingest`, the job appends that) and `CENTRAL_DMS_TOKEN` to the same
value configured on the central server.

### Testing the connection end-to-end

1. On the central server, confirm the route is registered:
   ```bash
   php artisan route:list --path=api/ingest
   ```
2. From the field node (or any machine that can reach the central server),
   send a manual test record:
   ```bash
   curl -i -X POST https://dms.example.gov.my/api/ingest \
     -H "Authorization: Bearer your-pre-shared-api-token" \
     -H "Content-Type: application/json" \
     -d '{"duck_id":"TESTDUCK","topic":"test/topic","message_id":"manual-test-1"}'
   ```
   A `200` response with `{"message":"Record ingested","id":...}` confirms
   the endpoint is reachable and the token is valid. A `401` means the token
   is wrong; a `503` means `CENTRAL_DMS_TOKEN` isn't set on the central
   server; a connection failure/timeout means firewall or DNS, not
   application config.
3. On the field node, verify the outbox is actually draining by checking the
   `synced` counts (see [Monitoring Sync State](#monitoring-sync-state)
   above) before and after triggering a real MamaDuck message, or by
   tailing the queue worker log:
   ```bash
   journalctl -u opendms-queue -f
   ```
4. Automated coverage for both sides of this flow (the outbox job and the
   ingest endpoint) lives in `tests/Feature/HybridSyncTest.php`:
   ```bash
   php artisan test --filter=HybridSyncTest
   ```

---

## Central Server: Read-Only Dashboard Mode

Live incident dispatch (acknowledge, assign, add notes, mark resolved) should
only ever happen at the field site where the responder actually is — a
central aggregator only ever *receives* raw telemetry (`ClusterData`), it
never receives the derived `IncidentLog` dispatch state. If a central
instance were also used as an active dispatch console, it and the field site
could independently and inconsistently manage the same real-world incident.

To make this structurally impossible rather than just a documented rule, set
`DASHBOARD_READONLY=true` in the central server's `.env`:

```env
# Central server: monitoring-only, dispatch happens at the field site
DASHBOARD_READONLY=true
```

With this set:
- The 5 dispatch-mutating routes (`sos-ack`, `bulk-acknowledge`,
  incident `status`/`notes`/`assign`) are rejected with `403 Forbidden` by
  `PreventDashboardWritesWhenReadonly` middleware — enforced server-side,
  independent of the UI.
- The dashboard UI hides/disables the corresponding buttons and shows a
  banner ("Read-only monitoring instance — incident dispatch happens at the
  field site, not here.") so operators aren't confused by controls that
  would otherwise 403.

Leave `DASHBOARD_READONLY=false` (or unset, the default) on every field
node, where dispatch actions must continue to work normally.

Automated coverage: `tests/Feature/DashboardReadonlyTest.php`
(`php artisan test --filter=DashboardReadonlyTest`).

---

## Why Not MQTT Bridge?

Mosquitto's bridge feature can forward messages between brokers and is a natural
alternative. However:

- Mosquitto's persistence queue is a **separate file** from your application data.
  A misconfigured or crashed broker during an outage can silently drop messages.
- There is no per-record audit trail (no `synced`/`synced_at` in the database).
- The central server would need a publicly exposed MQTT broker port (1883/8883),
  which is commonly blocked by firewalls and cloud providers.
- The Laravel queue approach uses only **HTTPS (port 443)** for delivery —
  universally allowed through any NAT, firewall, or satellite link.

The Laravel database queue stores durability in the same SQLite file as your
application data: one file, one backup, one source of truth.

---

## Rollback

To remove the sync columns (e.g., if reverting to offline-only):

```bash
php artisan migrate:rollback --path=database/migrations/2026_02_27_000000_add_sync_columns_to_cluster_data_table.php
```
