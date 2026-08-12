#!/bin/sh

set -eu

project_repo_url=${MESHBEACON_REPO_URL:-https://github.com/MeshBeacon/meshbeacon.git}
project_ref=${MESHBEACON_REF:-main}
system_name=$(uname -s)

if [ "$system_name" = "FreeBSD" ]; then
    default_install_dir=/usr/local/www/meshbeacon
else
    : "${HOME:?HOME must be set}"
    default_install_dir=$HOME/meshbeacon
fi

install_dir=${MESHBEACON_INSTALL_DIR:-$default_install_dir}

log() {
    printf '%s\n' "[meshbeacon] $*"
}

die() {
    printf '%s\n' "[meshbeacon] ERROR: $*" >&2
    exit 1
}

if [ "$system_name" = "FreeBSD" ] && [ "$(id -u)" -ne 0 ]; then
    die "FreeBSD installation changes system packages and services. Run this installer with root privileges."
fi

command_exists() {
    command -v "$1" >/dev/null 2>&1
}

run_root() {
    if [ "$(id -u)" -eq 0 ]; then
        "$@"
    else
        command_exists sudo || die "This step needs root privileges. Install sudo or run the installer as root."
        sudo "$@"
    fi
}

get_env_value() {
    env_key=$1

    [ -f .env ] || return 0

    awk -v key="$env_key" '
        $0 ~ "^[[:space:]]*" key "=" {
            sub("^[[:space:]]*" key "=", "")
            gsub(/^"|"$/, "")
            print
            exit
        }
    ' .env
}

set_env_value() {
    env_key=$1
    env_value=$2
    escaped_value=$(printf '%s' "$env_value" | sed 's/[\\&|]/\\&/g')
    temp_env=$(mktemp .env.tmp.XXXXXX)

    if grep -q "^${env_key}=" .env 2>/dev/null; then
        sed "s|^${env_key}=.*|${env_key}=${escaped_value}|" .env > "$temp_env"
    else
        cp .env "$temp_env"
        printf '\n%s=%s\n' "$env_key" "$env_value" >> "$temp_env"
    fi

    mv "$temp_env" .env
}

generate_app_key() {
    if command_exists openssl; then
        printf 'base64:%s' "$(openssl rand -base64 32 | tr -d '\r\n')"
        return
    fi

    command_exists base64 || die "openssl or base64 is required to generate APP_KEY."
    printf 'base64:%s' "$(dd if=/dev/urandom bs=32 count=1 2>/dev/null | base64 | tr -d '\r\n')"
}

generate_secret() {
    if command_exists openssl; then
        openssl rand -hex 24
        return
    fi

    command_exists base64 || die "openssl or base64 is required to generate an administrator password."
    dd if=/dev/urandom bs=48 count=1 2>/dev/null | base64 | tr -dc 'A-Za-z0-9' | cut -c 1-32
}

shell_quote() {
    printf "'%s'" "$(printf '%s' "$1" | sed "s/'/'\\''/g")"
}

clone_project() {
    if [ -d "$install_dir/.git" ]; then
        log "Using existing checkout at $install_dir"

        if [ "${MESHBEACON_UPDATE:-0}" = "1" ]; then
            if [ "$system_name" = "FreeBSD" ]; then
                run_root git -C "$install_dir" pull --ff-only
            else
                git -C "$install_dir" pull --ff-only
            fi
        fi
        return
    fi

    if [ -e "$install_dir" ]; then
        if find "$install_dir" -mindepth 1 -maxdepth 1 -print -quit 2>/dev/null | grep . >/dev/null; then
            die "$install_dir exists and is not an empty Git checkout. Set MESHBEACON_INSTALL_DIR to another path."
        fi
    fi

    log "Cloning $project_repo_url ($project_ref) into $install_dir"

    if [ "$system_name" = "FreeBSD" ]; then
        run_root mkdir -p "$(dirname "$install_dir")"
        run_root git clone --depth 1 --branch "$project_ref" "$project_repo_url" "$install_dir"
    else
        mkdir -p "$(dirname "$install_dir")"
        git clone --depth 1 --branch "$project_ref" "$project_repo_url" "$install_dir"
    fi
}

prepare_environment() {
    cd "$install_dir"

    [ -f .env ] || cp .env.example .env

    port=${MESHBEACON_PORT:-$(get_env_value MESHBEACON_PORT)}
    [ -n "$port" ] || port=8080

    mqtt_bind_address=${MQTT_BIND_ADDRESS:-$(get_env_value MQTT_BIND_ADDRESS)}
    [ -n "$mqtt_bind_address" ] || mqtt_bind_address=0.0.0.0

    mqtt_bind_port=${MQTT_BIND_PORT:-$(get_env_value MQTT_BIND_PORT)}
    [ -n "$mqtt_bind_port" ] || mqtt_bind_port=1883

    image_source=${MESHBEACON_IMAGE_SOURCE:-$(get_env_value MESHBEACON_IMAGE_SOURCE)}
    [ -n "$image_source" ] || image_source=ghcr

    image=${MESHBEACON_IMAGE:-$(get_env_value MESHBEACON_IMAGE)}
    [ -n "$image" ] || image=meshbeacon:local

    ghcr_image=${MESHBEACON_GHCR_IMAGE:-$(get_env_value MESHBEACON_GHCR_IMAGE)}
    [ -n "$ghcr_image" ] || ghcr_image=ghcr.io/9m2pju/meshbeacon:latest

    case "$image_source" in
        local|build)
            image_source=local
            ;;
        ghcr|pull)
            image_source=ghcr
            case "$image" in
                meshbeacon:local)
                    image=$ghcr_image
                    ;;
            esac
            ;;
        *)
            die "MESHBEACON_IMAGE_SOURCE must be local or ghcr."
            ;;
    esac

    app_url=${MESHBEACON_APP_URL:-$(get_env_value APP_URL)}
    [ -n "$app_url" ] || app_url="http://localhost:$port"

    admin_email=${MESHBEACON_ADMIN_EMAIL:-$(get_env_value MESHBEACON_ADMIN_EMAIL)}
    [ -n "$admin_email" ] || admin_email=admin@example.com

    admin_password=${MESHBEACON_ADMIN_PASSWORD:-$(get_env_value MESHBEACON_ADMIN_PASSWORD)}
    generated_admin_password=0
    if [ -z "$admin_password" ]; then
        admin_password="9m2pju@123"
        generated_admin_password=1
    fi

    if [ "$(get_env_value APP_KEY)" = "" ]; then
        set_env_value APP_KEY "$(generate_app_key)"
    fi

    set_env_value APP_ENV production
    set_env_value APP_DEBUG false
    set_env_value APP_URL "$app_url"
    set_env_value MESHBEACON_IMAGE_SOURCE "$image_source"
    set_env_value MESHBEACON_IMAGE "$image"
    set_env_value MESHBEACON_GHCR_IMAGE "$ghcr_image"
    set_env_value MESHBEACON_PORT "$port"
    set_env_value MQTT_BIND_ADDRESS "$mqtt_bind_address"
    set_env_value MQTT_BIND_PORT "$mqtt_bind_port"
    set_env_value MESHBEACON_ADMIN_EMAIL "$admin_email"
    set_env_value MESHBEACON_ADMIN_PASSWORD "$admin_password"
    set_env_value DB_CONNECTION sqlite
    set_env_value QUEUE_CONNECTION database
    set_env_value CACHE_STORE database
    set_env_value SESSION_DRIVER database

    if [ "$system_name" = "FreeBSD" ]; then
        project_path=$(pwd)
        set_env_value DB_DATABASE "$project_path/database/database.sqlite"
        set_env_value MQTT_HOST 127.0.0.1
        set_env_value MQTT_PORT 1883
    else
        set_env_value DB_DATABASE /var/www/database/database.sqlite
        set_env_value MQTT_HOST mqtt-server
        set_env_value MQTT_PORT 1883
    fi

    chmod 600 .env
}

install_linux() {
    command_exists docker || die "Docker is required on Linux. Install Docker Engine and the Compose v2 plugin, then rerun this installer."
    docker compose version >/dev/null 2>&1 \
        || die "Docker Compose v2 is required. The 'docker compose' command was not found."
    docker info >/dev/null 2>&1 \
        || die "The Docker daemon is not running or this user cannot access it."

    mkdir -p services/mosquitto/data services/mosquitto/log

    case "$image_source" in
        local)
            log "Building the application image locally"
            if docker buildx version >/dev/null 2>&1; then
                docker buildx build \
                    --load \
                    --provenance=false \
                    --file Dockerfile.compose \
                    --tag "$image" \
                    .
            else
                docker build \
                    --file Dockerfile.compose \
                    --tag "$image" \
                    .
            fi
            ;;
        ghcr)
            log "Pulling the application image from $image"
            docker pull "$image"
            ;;
    esac

    log "Starting MeshBeacon"
    docker compose up -d --no-build

    log "MeshBeacon is available at $app_url"
    log "Inspect services with: cd $install_dir && docker compose ps"
    log "Follow application logs with: cd $install_dir && docker compose logs -f app mqtt-worker queue-worker"

    if [ "$generated_admin_password" -eq 1 ]; then
        log "Fresh-install administrator email: $admin_email"
        log "Fresh-install administrator password: $admin_password"
        log "Change this password after the first login."
    else
        log "The existing MESHBEACON_ADMIN_PASSWORD value was preserved."
    fi
}

start_native_process() {
    process_name=$1
    process_log=$2
    process_pidfile=$3
    process_command=$4
    process_user=${5:-www}

    if [ -f "$process_pidfile" ]; then
        process_pid=$(cat "$process_pidfile")
        if run_root kill -0 "$process_pid" 2>/dev/null; then
            log "$process_name is already running (PID $process_pid)"
            return
        fi
        run_root rm -f "$process_pidfile"
    fi

    log "Starting $process_name"
    run_root daemon -u "$process_user" -o "$process_log" -p "$process_pidfile" sh -c "$process_command"
}

install_freebsd() {
    command_exists pkg || die "The FreeBSD pkg command is required."

    php_package=${MESHBEACON_PHP_PACKAGE:-php84}
    node_package=${MESHBEACON_NODE_PACKAGE:-node22}

    log "Installing FreeBSD packages"
    run_root pkg install -y \
        git \
        "$php_package" \
        "${php_package}-bcmath" \
        "${php_package}-curl" \
        "${php_package}-intl" \
        "${php_package}-mbstring" \
        "${php_package}-pcntl" \
        "${php_package}-pdo_sqlite" \
        "${php_package}-xml" \
        "${php_package}-zip" \
        composer \
        "$node_package" \
        mosquitto

    command_exists php || die "The selected PHP package did not provide the php command. Set MESHBEACON_PHP_PACKAGE to a supported package."
    command_exists composer || die "Composer was not installed."
    command_exists npm || die "The selected Node package did not provide npm. Set MESHBEACON_NODE_PACKAGE to a package that includes npm."
    command_exists daemon || die "The FreeBSD daemon utility is required."

    cd "$install_dir"
    mkdir -p database services/mosquitto/data services/mosquitto/log storage/logs
    touch database/database.sqlite

    log "Installing PHP and JavaScript dependencies"
    COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
    npm install --no-audit --no-fund
    npm run build
    php artisan vendor:publish --tag=laravel-assets --ansi --force
    mkdir -p public/flux
    ln -sf ../../vendor/livewire/flux/dist/flux-lite.min.js public/flux/flux.js
    ln -sf ../../vendor/livewire/flux/dist/flux-lite.min.js public/flux/flux.min.js

    php artisan migrate --no-interaction --force
    php artisan db:seed --no-interaction --force

    run_root chown -R www:www "$install_dir/database" "$install_dir/storage" "$install_dir/bootstrap/cache"
    run_root chmod -R ug+rwX "$install_dir/database" "$install_dir/storage" "$install_dir/bootstrap/cache"
    run_root chmod -R a+rX "$install_dir"
    run_root chmod 600 "$install_dir/.env"

    service_dir=/usr/local/etc/meshbeacon
    run_root mkdir -p "$service_dir"
    native_mqtt_config=$(mktemp)
    cat > "$native_mqtt_config" <<EOF
persistence true
persistence_location $install_dir/services/mosquitto/data/
log_dest file $install_dir/services/mosquitto/log/mosquitto.log
listener 1883 0.0.0.0
allow_anonymous true
EOF
    run_root install -m 0644 "$native_mqtt_config" "$service_dir/mosquitto.conf"
    rm -f "$native_mqtt_config"
    run_root chown -R mosquitto:mosquitto "$install_dir/services/mosquitto/data" "$install_dir/services/mosquitto/log"

    project_path=$(pwd)
    quoted_project_path=$(shell_quote "$project_path")
    quoted_php=$(shell_quote "$(command -v php)")
    mkdir -p storage/logs
    run_root chown -R www:www "$install_dir/storage"

    start_native_process \
        "MeshBeacon web server" \
        "$project_path/storage/logs/web.log" \
        "$project_path/storage/web.pid" \
        "cd $quoted_project_path && exec $quoted_php artisan serve --host=0.0.0.0 --port=$port"
    start_native_process \
        "MQTT broker" \
        "$project_path/storage/logs/mosquitto.log" \
        "$project_path/storage/mosquitto.pid" \
        "exec /usr/local/sbin/mosquitto -c '$service_dir/mosquitto.conf'" \
        "mosquitto"
    sleep 2
    start_native_process \
        "MQTT worker" \
        "$project_path/storage/logs/mqtt-worker.log" \
        "$project_path/storage/mqtt-worker.pid" \
        "cd $quoted_project_path && while true; do $quoted_php artisan app:mqtt-subscribe; sleep 5; done"
    start_native_process \
        "queue worker" \
        "$project_path/storage/logs/queue-worker.log" \
        "$project_path/storage/queue-worker.pid" \
        "cd $quoted_project_path && while true; do $quoted_php artisan queue:work --queue=sync,default --tries=5 --timeout=0; sleep 5; done"
    start_native_process \
        "scheduler" \
        "$project_path/storage/logs/scheduler.log" \
        "$project_path/storage/scheduler.pid" \
        "cd $quoted_project_path && while true; do $quoted_php artisan schedule:run --no-interaction; sleep 60; done"

    log "MeshBeacon is available at $app_url"
    log "FreeBSD uses the PHP development server for the first-run native install. Put it behind a production web server before exposing it to the Internet."

    if [ "$generated_admin_password" -eq 1 ]; then
        log "Fresh-install administrator email: $admin_email"
        log "Fresh-install administrator password: $admin_password"
        log "Change this password after the first login."
    else
        log "The existing MESHBEACON_ADMIN_PASSWORD value was preserved."
    fi
}

clone_project
prepare_environment

case "$system_name" in
    Linux)
        install_linux
        ;;
    FreeBSD)
        install_freebsd
        ;;
    *)
        die "Unsupported operating system: $system_name. Supported systems are Linux and FreeBSD."
        ;;
esac
