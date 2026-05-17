#!/bin/bash
set -e

echo "=========================================="
echo "  Starting EqualVoice Application"
echo "=========================================="

# ── Wait for MySQL ──────────────────────────────────────────────────────────────
echo "[1/4] Waiting for MySQL to be ready..."
COUNTER=0
MAX_WAIT=150
until mysqladmin ping -h"${DB_HOST:-mysql}" -P 3306 --protocol=tcp \
                       -u"${DB_USER}" -p"${DB_PASS}" --silent 2>/dev/null; do
    COUNTER=$((COUNTER + 1))
    if [ "$COUNTER" -ge "$MAX_WAIT" ]; then
        echo "ERROR: MySQL did not become ready within $((MAX_WAIT * 2)) seconds. Aborting."
        exit 1
    fi
    echo "  Attempt $COUNTER/$MAX_WAIT - MySQL not ready yet, retrying..."
    sleep 2
done
echo "  MySQL is ready."

# ── Bootstrap database ──────────────────────────────────────────────────────────
echo "[2/4] Checking database..."
if ! mysql -h"${DB_HOST:-mysql}" -u"${DB_USER}" -p"${DB_PASS}" -e "USE ${DB_NAME};" 2>/dev/null; then
    echo "  Database '${DB_NAME}' not found — creating..."
    mysql -h"${DB_HOST:-mysql}" -u"root" -p"${DB_ROOT_PASSWORD}" <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\`;
CREATE USER IF NOT EXISTS '${DB_USER}'@'%' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'%';
FLUSH PRIVILEGES;
SQL

    if [ -f "/var/www/html/sql/equalvoice.sql" ]; then
        echo "  Importing schema from sql/equalvoice.sql..."
        mysql -h"${DB_HOST:-mysql}" -u"${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" \
            < /var/www/html/sql/equalvoice.sql
        echo "  Schema imported successfully."
    fi
else
    echo "  Database '${DB_NAME}' already exists — skipping import."
fi

# ── File permissions ────────────────────────────────────────────────────────────
# Skipped on startup — the Dockerfile already runs chown/chmod during image
# build. Re-running here every container start was forking one chmod per file
# (because of the `\;` terminator) and could add minutes to startup time, during
# which Apache wasn't yet listening and the healthcheck would keep timing out.
echo "[3/4] File permissions (skipped — already applied at image build time)."

# ── Start Apache ────────────────────────────────────────────────────────────────
echo "[4/4] Starting Apache web server..."
exec apache2-foreground
