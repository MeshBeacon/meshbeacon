# MeshBeacon Helm chart

Supersedes the previous plain `k8s/` manifest set (same resources, now
templated). See inline comments in `values.yaml` and each template for the
reasoning behind specific choices (sidecar nginx, fsGroup instead of a
permissions Job, single-node ReadWriteOnce storage assumption, etc.).

## Install

```sh
helm install meshbeacon ./charts/meshbeacon \
  --namespace meshbeacon --create-namespace \
  --set secrets.appKey="$(php artisan key:generate --show)" \
  --set ingress.host=meshbeacon.example.com
```

For anything beyond a quick test, put your overrides in a values file kept
out of git instead of a long `--set` chain:

```sh
cp charts/meshbeacon/values.yaml my-values.yaml
# edit my-values.yaml: secrets.*, ingress.host, storage sizes, etc.
helm install meshbeacon ./charts/meshbeacon -n meshbeacon --create-namespace -f my-values.yaml
```

The migrate Job (`optimize:clear` + `migrate` + `db:seed`) runs
automatically as a `post-install,pre-upgrade` Helm hook — no manual step
needed on `helm upgrade`.

## Upgrading / uninstalling

```sh
helm upgrade meshbeacon ./charts/meshbeacon -n meshbeacon -f my-values.yaml
helm uninstall meshbeacon -n meshbeacon   # PVCs are not deleted automatically
```

## Multi-node HA

Default values assume a single node (`highAvailability: false`, SQLite,
`ReadWriteOnce` storage + `podAffinity` pinning). For real multi-node HA,
set `database.connection: mysql` (or `pgsql`) with real `database.host`/
credentials, switch `storage.accessMode` to `ReadWriteMany` on a storage
class that supports it (NFS/EFS/Longhorn/etc.), and set
`highAvailability: true` to drop the pod-affinity pinning.
