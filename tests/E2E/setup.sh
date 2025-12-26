#!/usr/bin/env bash
# Note: this is meant for the CI job.
# If you want to run PW locally, you either make sure the current branch is up to date with remote.
# Or, you skip running setup.sh and directly use the launch script after setting the local dev repository
# e.g. `bash tests/E2E/launch.sh --workdir=../../../nimbus-dev`.

set -euo pipefail

# --------------------------------------
# CONFIGURATION
# --------------------------------------

REPO_URL="https://github.com/sunchayn/nimbus-dev.git"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TARGET_DIR="$SCRIPT_DIR/.workdir"
BRANCH_NAME="$(git branch --show-current)"

# --------------------------------------
# REPOSITORY SETUP
# --------------------------------------

# Reset the working directory to a clean state.
rm -rf "$TARGET_DIR"
mkdir "$TARGET_DIR"

# Clone repository into a temporary location, then move it into place.
TEMP_DIR="$(mktemp -d)"
git clone "$REPO_URL" "$TEMP_DIR"

rsync -a --delete "$TEMP_DIR"/ "$TARGET_DIR"/
rm -rf "$TEMP_DIR"

cd "$TARGET_DIR"

# --------------------------------------
# DEPENDENCY INSTALLATION
# --------------------------------------

# Install PHP dependencies.
if command -v composer >/dev/null 2>&1; then
    # Set current nimbus's version
    php "$SCRIPT_DIR/install-current-nimbus-branch.php" "$BRANCH_NAME"

    composer update sunchayn/nimbus --no-progress --ansi
else
    echo "Composer is not installed. Aborting."
    exit 1
fi

# --------------------------------------
# ENVIRONMENT SETUP
# --------------------------------------

ENV_FILE="$TARGET_DIR/.env"
rm -f ENV_FILE
cp "$SCRIPT_DIR/.env.template" "$ENV_FILE"

# Install Node dependencies.
if command -v npm >/dev/null 2>&1; then
    npm install
else
    echo "npm is not installed. Aborting."
    exit 1
fi

# --------------------------------------
# APPLICATION BOOTSTRAP
# --------------------------------------

# Run migrations against a local SQLite database.
touch database/database.sqlite
php artisan migrate --force

# Publish Nimbus-related frontend assets.
php artisan vendor:publish --tag=nimbus-assets
