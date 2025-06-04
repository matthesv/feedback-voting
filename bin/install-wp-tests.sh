#!/usr/bin/env bash
# Installs the WordPress testing framework.
# Usage: bin/install-wp-tests.sh [db-name] [db-user] [db-pass] [db-host] [wp-version]

set -euo pipefail

DB_NAME=${1:-wptests}
DB_USER=${2:-root}
DB_PASS=${3:-}
DB_HOST=${4:-localhost}
WP_VERSION=${5:-trunk}

WP_CORE_DIR=${WP_CORE_DIR-/tmp/wordpress-develop}
WP_TESTS_DIR="$WP_CORE_DIR/tests/phpunit"
PHPUNIT_POLYFILLS_DIR="/tmp/PHPUnit-Polyfills"

if [ ! -d "$WP_CORE_DIR" ]; then
    echo "Cloning WordPress development repository..."
    git clone --depth=1 https://github.com/WordPress/wordpress-develop.git "$WP_CORE_DIR"
fi

if [ ! -d "$PHPUNIT_POLYFILLS_DIR" ]; then
    echo "Cloning PHPUnit Polyfills library..."
    git clone --depth=1 https://github.com/Yoast/PHPUnit-Polyfills.git "$PHPUNIT_POLYFILLS_DIR"
fi

# Create the database if possible.
if command -v mysqladmin >/dev/null; then
    echo "Creating database $DB_NAME if it does not exist..."
    mysqladmin create "$DB_NAME" --user="$DB_USER" --password="$DB_PASS" --host="$DB_HOST" 2>/dev/null || true
else
    echo "mysqladmin command not found. Please create database '$DB_NAME' manually." >&2
fi

CONFIG_FILE="$WP_TESTS_DIR/wp-tests-config.php"
if [ ! -f "$CONFIG_FILE" ]; then
    echo "Copying wp-tests-config.php..."
    mkdir -p "$WP_TESTS_DIR"
    cp "$(dirname "$0")/../tests/wp-tests-config.php" "$CONFIG_FILE"
fi

echo "WordPress test framework installed at $WP_TESTS_DIR"
