#!/bin/sh

set -eu

if [ "$#" -lt 2 ]; then
    echo "usage: worker-entrypoint.sh <worker> <command> [args...]" >&2
    exit 64
fi

worker="$1"
shift
heartbeat_interval="${OBSERVABILITY_HEARTBEAT_INTERVAL:-15}"

heartbeat() {
    php artisan observability:heartbeat "$worker" >/dev/null 2>&1 || true
}

heartbeat
"$@" &
child_pid=$!

trap 'kill "$child_pid" 2>/dev/null || true' INT TERM HUP

while kill -0 "$child_pid" 2>/dev/null; do
    sleep "$heartbeat_interval"

    if kill -0 "$child_pid" 2>/dev/null; then
        heartbeat
    fi
done

set +e
wait "$child_pid"
exit_code=$?
set -e

exit "$exit_code"
