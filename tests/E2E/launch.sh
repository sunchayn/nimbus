#!/usr/bin/env bash

set -euo pipefail

print_help() {
    cat <<EOF
Usage: $(basename "$0") [OPTIONS]

Options:
  --workdir=PATH       Path to the work directory (default: .workdir)
  --port1=PORT         Port for the main PHP server (default: 8000)
  --port2=PORT         Port for the secondary PHP server (default: 8001)
  --help               Display this help message and exit

Examples:
  $(basename "$0")
  $(basename "$0") --workdir=custom_workdir
  $(basename "$0") --workdir=custom_workdir --port1=9000 --port2=9001
EOF
    exit 0
}

# --------------------------------------
# CONFIGURATION
# --------------------------------------

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
WORKDIR_PATH=".workdir"
PORT1=8000
PORT2=8001
SERVER_TIMEOUT=15

# --------------------------------------
# PARSE OPTIONS
# --------------------------------------

for arg in "$@"; do
    case $arg in
        --workdir=*)
            WORKDIR_PATH="${arg#*=}"
            shift
            ;;
        --port1=*)
            PORT1="${arg#*=}"
            shift
            ;;
        --port2=*)
            PORT2="${arg#*=}"
            shift
            ;;
        --help)
            print_help
            ;;
        *)
            echo "Unknown option: $arg"
            echo "Use --help to see usage."
            exit 1
            ;;
    esac
done

TARGET_DIR="$SCRIPT_DIR/$WORKDIR_PATH"

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
