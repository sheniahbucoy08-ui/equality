#!/usr/bin/env bash
# Full CI/CD: push to GitHub, then trigger Jenkins job1 -> job2 -> job3
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

MESSAGE="${1:-CI/CD automated push $(date -Iseconds)}"
BRANCH="${2:-main}"

if [[ -f .env ]]; then
  set -a
  # shellcheck disable=SC1091
  source .env
  set +a
fi

JENKINS_URL="${JENKINS_URL:-http://localhost:9090}"
JENKINS_USER="${JENKINS_USER:-admin}"
GITHUB_REPO="${GITHUB_REPO:-sheniahbucoy08-ui/equality}"

echo ""
echo "=== EqualVoice CI/CD Auto (GitHub + Jenkins) ==="

bash "$ROOT/scripts/jenkins-deploy-jobs.sh" 2>/dev/null || bash "$ROOT/scripts/jenkins-setup.sh" --jobs-only 2>/dev/null || true

if [[ -n "${GITHUB_TOKEN:-}" ]]; then
  export GITHUB_REPO
  bash "$ROOT/scripts/github-auto-push.sh" "$MESSAGE" "$BRANCH"
else
  echo "GITHUB_TOKEN not set — skipping GitHub push"
fi

if [[ -z "${JENKINS_API_TOKEN:-}" ]]; then
  echo "JENKINS_API_TOKEN not set in .env"
  exit 1
fi

trigger_and_wait() {
  local job=$1
  echo "Triggering $job ..."
  curl -fsS -u "${JENKINS_USER}:${JENKINS_API_TOKEN}" -X POST "${JENKINS_URL}/job/${job}/build"
  local deadline=$((SECONDS + 1800))
  while (( SECONDS < deadline )); do
    local building result
    building=$(curl -fsS -u "${JENKINS_USER}:${JENKINS_API_TOKEN}" \
      "${JENKINS_URL}/job/${job}/lastBuild/api/json" | grep -o '"building":[^,]*' | cut -d: -f2)
    result=$(curl -fsS -u "${JENKINS_USER}:${JENKINS_API_TOKEN}" \
      "${JENKINS_URL}/job/${job}/lastBuild/api/json" | grep -o '"result":"[^"]*"' | cut -d'"' -f4)
    if [[ "$building" == "false" && -n "$result" ]]; then
      if [[ "$result" == "SUCCESS" ]]; then
        echo "  $job SUCCESS"
        return 0
      fi
      echo "  $job $result"
      return 1
    fi
    sleep 10
  done
  echo "Timeout: $job"
  return 1
}

for job in job1 job2 job3; do
  trigger_and_wait "$job"
done

echo ""
echo "=== CI/CD complete ==="
