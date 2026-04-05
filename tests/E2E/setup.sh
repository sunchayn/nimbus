#!/usr/bin/env bash
# Note: this script is intended for the CI job.
# Local usage:
#   - Ensure the current branch is up to date with remote, OR
#   - Skip running setup.sh and directly use the launch script with a local dev repository
#     e.g. `bash tests/E2E/launch.sh --workdir=../../../nimbus-dev`

set -euo pipefail

# --------------------------------------
# CONFIGURATION
# --------------------------------------

DEV_REPO_URL="https://github.com/sunchayn/nimbus-dev.git"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TARGET_DIR="$SCRIPT_DIR/.workdir"
ROOT_DIR="$SCRIPT_DIR/../../"

# --------------------------------------
# HELPER FUNCTIONS
# --------------------------------------

print_help() {
    cat <<EOF
Usage: $(basename "$0") BRANCH_NAME

Arguments:
  BRANCH_NAME        Name of the Nimbus branch to set up and install. Required.
  REPO_URL           Url of the Nimbus repo to fetch the branch from (Needed for Forks).

Notes:
  - Intended for CI usage. For local Playwright runs, you can skip setup.sh and run launch.sh
    with a local dev repository:
      bash tests/E2E/launch.sh --workdir=../../../nimbus-dev
EOF
    exit 0
}

# Check for help flag
if [[ "${1:-}" == "--help" || "${1:-}" == "-h" ]]; then
    print_help
fi

# --------------------------------------
# ARGUMENT PARSING
# --------------------------------------

BRANCH_NAME="${1:-}"
REPO_URL="${2:-}"

if [[ -z "$BRANCH_NAME" ]]; then
    echo "Error: BRANCH_NAME argument is required."
    echo "Usage: $0 BRANCH_NAME [REPO_URL]"
    exit 1
fi

echo "Using branch name: $BRANCH_NAME"
if [[ -n "$REPO_URL" ]]; then
    echo "Using repository URL: $REPO_URL"
fi

# --------------------------------------
# REPOSITORY SETUP
# --------------------------------------

echo "Resetting working directory at $TARGET_DIR..."
rm -rf "$TARGET_DIR"
mkdir -p "$TARGET_DIR"

echo "Cloning Nimbus Dev repository into temporary directory..."
TEMP_DIR="$(mktemp -d)"
git clone "$DEV_REPO_URL" "$TEMP_DIR"

echo "Syncing repository to target directory..."
rsync -a --delete "$TEMP_DIR"/ "$TARGET_DIR"/
rm -rf "$TEMP_DIR"

cd "$TARGET_DIR"

# --------------------------------------
# DEPENDENCY INSTALLATION (Inside Nimbus-Dev repository)
# --------------------------------------

# Install PHP dependencies
if command -v composer >/dev/null 2>&1; then
    echo "Setting current Nimbus branch in composer..."
    php "$SCRIPT_DIR/install-current-nimbus-branch.php" "$BRANCH_NAME" "$REPO_URL"

    echo "Installing/updating nimbus PHP package..."
    composer update sunchayn/nimbus --no-progress --ansi
else
    echo "Composer is not installed. Aborting."
    exit 1
fi

# Install Node dependencies
if command -v npm >/dev/null 2>&1; then
    echo "Installing Node.js dependencies..."
    npm install
else
    echo "npm is not installed. Aborting."
    exit 1
fi

# --------------------------------------
# ENVIRONMENT SETUP (Inside Nimbus-Dev repository)
# --------------------------------------

ENV_FILE="$TARGET_DIR/.env"
echo "Setting up environment file..."
rm -f "$ENV_FILE"
cp "$SCRIPT_DIR/.env.template" "$ENV_FILE"

# --------------------------------------
# APPLICATION BOOTSTRAP (Inside Nimbus-Dev repository)
# --------------------------------------

echo "Bootstrapping application..."

# Run migrations against a local SQLite database
touch database/database.sqlite
php artisan migrate --force

# --------------------------------------
# Publish Nimbus-related frontend assets from the current branch.
# --------------------------------------

cd "$ROOT_DIR"

echo "Building dev assets for Nimbus..."
npm run build:dev

# Publish Nimbus-related frontend assets
cp -a "$ROOT_DIR/resources/dist/." "$TARGET_DIR/public/vendor/nimbus/"

echo "Setup complete. Ready for E2E tests."
