#!/usr/bin/env bash
# Deploy EqualVoice app only (never touches equalvoice_jenkins)
set -eux

if [[ -f .env ]]; then set -a; source .env; set +a; fi

COMPOSE_PROJECT="${COMPOSE_PROJECT_NAME:-equalvoice}"
COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.yml}"
DB_ROOT="${DB_ROOT_PASSWORD:-root_password_123}"
DB_NAME="${DB_NAME:-equalvoice_db}"

echo "Job3 - EqualVoice Deploy"
mkdir -p backups

docker compose -p "${COMPOSE_PROJECT}" -f "${COMPOSE_FILE}" stop web mysql phpmyadmin 2>/dev/null || true
docker compose -p "${COMPOSE_PROJECT}" -f "${COMPOSE_FILE}" rm -f web mysql phpmyadmin 2>/dev/null || true

docker compose -p "${COMPOSE_PROJECT}" -f "${COMPOSE_FILE}" up -d --build web mysql phpmyadmin

MYSQL_ID=""
for i in $(seq 1 30); do
  MYSQL_ID="$(docker compose -p "${COMPOSE_PROJECT}" -f "${COMPOSE_FILE}" ps -q mysql 2>/dev/null | head -1)"
  if [[ -n "${MYSQL_ID}" ]] && docker exec "${MYSQL_ID}" mysqladmin ping \
      -h 127.0.0.1 -P 3306 --protocol=tcp \
      -u root --password="${DB_ROOT}" 2>/dev/null; then
    echo "DB healthy"
    break
  fi
  sleep 5
done

if [[ -n "${MYSQL_ID}" && -f sql/equalvoice.sql ]]; then
  cat sql/equalvoice.sql | docker exec -i "${MYSQL_ID}" \
    mysql -uroot -p"${DB_ROOT}" "${DB_NAME}" 2>/dev/null || true
fi

WEB_ID=""
for i in $(seq 1 24); do
  WEB_ID="$(docker compose -p "${COMPOSE_PROJECT}" -f "${COMPOSE_FILE}" ps -q web 2>/dev/null | head -1)"
  if [[ -n "${WEB_ID}" ]] && docker exec "${WEB_ID}" curl -fsS -o /dev/null http://localhost/index.php 2>/dev/null; then
    echo "Web healthy"
    break
  fi
  sleep 5
done

docker compose -p "${COMPOSE_PROJECT}" -f "${COMPOSE_FILE}" ps
echo "Deploy complete"
echo "  App:        http://localhost:8000  (with docker-compose.override.yml)"
echo "  PHPMyAdmin: http://localhost:8081"
echo "  Jenkins:    http://localhost:9090 (unchanged)"
