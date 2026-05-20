#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

if [[ -f .env ]]; then
  set -a
  # shellcheck disable=SC1091
  source .env
  set +a
fi

JENKINS_URL="${JENKINS_URL:-http://localhost:9090}"
JENKINS_USER="${JENKINS_USER:-admin}"
JENKINS_API_TOKEN="${JENKINS_API_TOKEN:-}"

echo "=== EqualVoice Jenkins Setup ==="

docker compose -f docker-compose.jenkins.yml up -d

echo "Waiting for Jenkins..."
for i in $(seq 1 60); do
  if curl -fsS "${JENKINS_URL}/login" >/dev/null 2>&1; then
    echo "Jenkins ready"
    break
  fi
  sleep 5
done

bash "$ROOT/scripts/jenkins-deploy-jobs.sh"

if [[ -n "$JENKINS_API_TOKEN" ]]; then
  for job in job1 job2 job3; do
    curl -fsS -u "${JENKINS_USER}:${JENKINS_API_TOKEN}" -X POST "${JENKINS_URL}/job/${job}/build" || true
  done
fi

echo ""
echo "Jenkins: ${JENKINS_URL}"
echo "Jobs: job1 (build), job2 (test), job3 (deployment)"
echo "Run full pipeline: ./scripts/ci-cd-auto.sh"
