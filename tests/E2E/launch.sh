#!/usr/bin/env bash

set -euo pipefail

# --------------------------------------
# CONFIGURATION
# --------------------------------------

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TARGET_DIR="$SCRIPT_DIR/.workdir"

# Server ports (default values can be overridden by positional arguments)
PORT1="${1:-8000}"
PORT2="${2:-8001}"

# Maximum time allowed for server startup
SERVER_TIMEOUT=15

# --------------------------------------
# HELPER FUNCTIONS
# --------------------------------------

# Poll until a given URL responds. Used to ensure servers are live before continuing.
wait_for_url() {
    local url="$1"
    local timeout="${2:-15}"
    local elapsed=0

    while ! curl -s "$url" >/dev/null 2>&1; do
        sleep 1
        elapsed=$((elapsed + 1))
        if [ "$elapsed" -ge "$timeout" ]; then
            echo "Timeout waiting for $url"
            exit 1
        fi
    done
}

# Terminate all background jobs when the script exits. Prevents orphaned servers.
cleanup() {
    echo "Stopping servers..."
    jobs -p | xargs -r kill
}
trap cleanup EXIT

# --------------------------------------
# SERVER STARTUP
# --------------------------------------

cd "$TARGET_DIR"

echo "Starting main PHP server on port $PORT1..."
php artisan serve --host=127.0.0.1 --port="$PORT1" > php_server1.log 2>&1 &
PID1=$!
echo "Main PHP server PID: $PID1"

echo "Starting secondary PHP server on port $PORT2..."
php artisan serve --host=127.0.0.1 --port="$PORT2" > php_server2.log 2>&1 &
PID2=$!
echo "Secondary PHP server PID: $PID2"

# URLs used to verify that both servers are reachable
BACKEND_URL="http://127.0.0.1:$PORT1"
SECONDARY_URL="http://127.0.0.1:$PORT2"

echo "Waiting for main server at $BACKEND_URL..."
wait_for_url "$BACKEND_URL" "$SERVER_TIMEOUT"
echo "Main server ready."

echo "Waiting for secondary server at $SECONDARY_URL..."
wait_for_url "$SECONDARY_URL" "$SERVER_TIMEOUT"
echo "Secondary server ready."

# --------------------------------------
# ENVIRONMENT SETUP
# --------------------------------------

ENV_FILE="$TARGET_DIR/.env"
rm -f "$ENV_FILE"
cp "$SCRIPT_DIR/.env.template" "$ENV_FILE"

# Append or update an environment variable inside the .env file.
append_env_var() {
    local key="$1"
    local value="$2"

    sed -i.bak "/^${key}=*/d" "$ENV_FILE"
    echo "${key}=\"${value}\"" >> "$ENV_FILE"
}

append_env_var "APP_URL" "$BACKEND_URL"
append_env_var "NIMBUS_RELAY_ENDPOINT" "$SECONDARY_URL"

echo "Servers are running..."

while true; do
    sleep 5
done
