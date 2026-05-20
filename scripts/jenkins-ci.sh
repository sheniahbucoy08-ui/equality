#!/usr/bin/env bash
# Jenkins container helper (mounted at /var/jenkins_home/ci-scripts/)
# Usage: jenkins-ci.sh checkout | deploy
set -euo pipefail

CMD="${1:-}"
cd "${WORKSPACE:?}"

sync_seed_files() {
  local seed="/var/jenkins_home/project-seed"
  [[ -d "$seed" ]] || return 0
  for f in docker-compose.ci.yml docker-compose.jenkins.yml docker-compose.override.yml; do
    if [[ -f "${seed}/${f}" ]]; then
      cp "${seed}/${f}" "${f}"
      echo "Synced ${f} from project-seed"
    fi
  done
}

cmd_checkout() {
  local repo="${GITHUB_REPO:-sheniahbucoy08-ui/equality}"
  local branch="${GIT_BRANCH:-main}"
  local stamp_file="${WORKSPACE}/.jenkins-checkout-stamp"
  local stamp="${repo}@${branch}"

  if [[ -f Dockerfile && -f docker-compose.yml && -f "$stamp_file" && "$(cat "$stamp_file")" == "$stamp" ]]; then
    sync_seed_files
    echo "Workspace ready ($stamp)"
    return 0
  fi

  echo "Preparing workspace for $stamp..."
  find . -mindepth 1 -maxdepth 1 -exec rm -rf {} + 2>/dev/null || true

  local git_url
  if [[ -n "${GITHUB_TOKEN:-}" ]]; then
    git_url="https://x-access-token:${GITHUB_TOKEN}@github.com/${repo}.git"
  else
    git_url="https://github.com/${repo}.git"
  fi

  git clone --depth 1 --branch "${branch}" "${git_url}" .
  [[ -f Dockerfile ]] || { echo "ERROR: Dockerfile missing"; exit 1; }

  sync_seed_files
  echo "$stamp" > "$stamp_file"
  echo "Checkout OK"
}

cmd_deploy() {
  [[ -f .env ]] && { set -a; source .env; set +a; }

  local project="${COMPOSE_PROJECT_NAME:-equalvoice}"
  local compose_file="${COMPOSE_FILE:-docker-compose.yml}"
  local db_root="${DB_ROOT_PASSWORD:-root_password_123}"
  local db_name="${DB_NAME:-equalvoice_db}"

  echo "Job3 - EqualVoice Deploy"
  mkdir -p backups

  docker compose -p "${project}" -f "${compose_file}" stop web mysql phpmyadmin 2>/dev/null || true
  docker compose -p "${project}" -f "${compose_file}" rm -f web mysql phpmyadmin 2>/dev/null || true
  docker compose -p "${project}" -f "${compose_file}" up -d --build web mysql phpmyadmin

  local mysql_id=""
  for i in $(seq 1 30); do
    mysql_id="$(docker compose -p "${project}" -f "${compose_file}" ps -q mysql 2>/dev/null | head -1)"
    if [[ -n "${mysql_id}" ]] && docker exec "${mysql_id}" mysqladmin ping \
        -h 127.0.0.1 -P 3306 --protocol=tcp -u root --password="${db_root}" 2>/dev/null; then
      echo "DB healthy"
      break
    fi
    sleep 5
  done

  if [[ -n "${mysql_id}" && -f sql/equalvoice.sql ]]; then
    cat sql/equalvoice.sql | docker exec -i "${mysql_id}" \
      mysql -uroot -p"${db_root}" "${db_name}" 2>/dev/null || true
  fi

  local web_id=""
  for i in $(seq 1 24); do
    web_id="$(docker compose -p "${project}" -f "${compose_file}" ps -q web 2>/dev/null | head -1)"
    if [[ -n "${web_id}" ]] && docker exec "${web_id}" curl -fsS -o /dev/null http://localhost/index.php 2>/dev/null; then
      echo "Web healthy"
      break
    fi
    sleep 5
  done

  docker compose -p "${project}" -f "${compose_file}" ps
  echo "Deploy complete — App http://localhost:8000 | Jenkins http://localhost:9090"
}

case "$CMD" in
  checkout) cmd_checkout ;;
  deploy)   cmd_deploy ;;
  *)
    echo "Usage: jenkins-ci.sh checkout|deploy"
    exit 1
    ;;
esac
