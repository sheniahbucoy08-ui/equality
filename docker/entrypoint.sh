#!/bin/bash
set -e

echo "=========================================="
echo "  Starting EqualVoice Application"
echo "=========================================="

# ── Wait for MySQL ──────────────────────────────────────────────────────────────
DB_HOST_NAME="${DB_HOST:-mysql}"
DB_PORT_NUM="${DB_PORT:-3306}"

# The Debian "default-mysql-client" package on Trixie is actually mariadb-client
# 11.x, which validates TLS certificates strictly. The MySQL 8 official image
# presents an auto-generated self-signed cert, which mariadb-client refuses
# with:  ERROR 2026 (HY000): TLS/SSL error: self-signed certificate ...
# On the internal compose network we don't need TLS at all, so disable it.
# `--skip-ssl` works on mariadb-client; `--ssl-mode=DISABLED` works on the
# real mysql-client. We standardize on --skip-ssl because that's what the
# installed client supports.
MYSQL_OPTS="--skip-ssl --protocol=tcp"

# ---- Stage A: wait until the MySQL TCP port is accepting connections ----
# We use bash's built-in /dev/tcp test instead of `mysqladmin ping` because
# the Debian package `default-mysql-client` actually installs mariadb-client.
# mariadb-admin's `ping` exits non-zero when the credentials it sends are
# rejected, even though MySQL itself is alive — which makes it useless as a
# server-reachability probe. /dev/tcp has no auth dependency.
echo "[1/4] Waiting for MySQL TCP port at ${DB_HOST_NAME}:${DB_PORT_NUM}..."
COUNTER=0
MAX_WAIT=150
while ! (echo > "/dev/tcp/${DB_HOST_NAME}/${DB_PORT_NUM}") 2>/dev/null; do
    COUNTER=$((COUNTER + 1))
    if [ "$COUNTER" -ge "$MAX_WAIT" ]; then
        echo "ERROR: MySQL TCP port not reachable after $((MAX_WAIT * 2))s."
        exit 1
    fi
    if [ $((COUNTER % 5)) -eq 0 ]; then
        echo "  ${COUNTER}/${MAX_WAIT} — port still closed (server starting)..."
    fi
    sleep 2
done
echo "  MySQL TCP port is open after ${COUNTER} attempts."

# ---- Stage B: verify our credentials actually work ----
# If this fails, the password/user in .env doesn't match what the MySQL
# init script created. We don't suppress stderr so the real error surfaces.
echo "  Verifying credentials with a SELECT 1 (TLS disabled for local link)..."
AUTH_TRIES=0
MAX_AUTH=30
until mysql ${MYSQL_OPTS} -h"${DB_HOST_NAME}" -P "${DB_PORT_NUM}" \
            -u"${DB_USER}" -p"${DB_PASS}" \
            -e "SELECT 1;" >/dev/null 2>&1; do
    AUTH_TRIES=$((AUTH_TRIES + 1))
    if [ "$AUTH_TRIES" -ge "$MAX_AUTH" ]; then
        echo "ERROR: credentials never accepted after $((MAX_AUTH * 2))s."
        echo "Running once with stderr visible to expose the real error:"
        mysql ${MYSQL_OPTS} -h"${DB_HOST_NAME}" -P "${DB_PORT_NUM}" \
              -u"${DB_USER}" -p"${DB_PASS}" -e "SELECT 1;" || true
        exit 1
    fi
    if [ $((AUTH_TRIES % 5)) -eq 0 ]; then
        echo "  auth ${AUTH_TRIES}/${MAX_AUTH} — not accepted yet, retrying..."
    fi
    sleep 2
done
echo "  MySQL is ready and credentials work."

# ── Bootstrap database ──────────────────────────────────────────────────────────
echo "[2/4] Checking database..."
if ! mysql ${MYSQL_OPTS} -h"${DB_HOST_NAME}" -P "${DB_PORT_NUM}" \
           -u"${DB_USER}" -p"${DB_PASS}" \
           -e "USE ${DB_NAME};" 2>/dev/null; then
    echo "  Database '${DB_NAME}' not found — creating..."
    mysql ${MYSQL_OPTS} -h"${DB_HOST_NAME}" -P "${DB_PORT_NUM}" \
          -u"root" -p"${DB_ROOT_PASSWORD}" <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\`;
CREATE USER IF NOT EXISTS '${DB_USER}'@'%' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'%';
FLUSH PRIVILEGES;
SQL

    if [ -f "/var/www/html/sql/equalvoice.sql" ]; then
        echo "  Importing schema from sql/equalvoice.sql..."
        mysql ${MYSQL_OPTS} -h"${DB_HOST_NAME}" -P "${DB_PORT_NUM}" \
              -u"${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" \
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
