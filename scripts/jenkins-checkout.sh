#!/usr/bin/env bash
# Clone application source into Jenkins WORKSPACE (used by job1/job2/job3)
set -euo pipefail

cd "${WORKSPACE:?}"

REPO_SLUG="${GITHUB_REPO:-sheniahbucoy08-ui/equality}"
BRANCH="${GIT_BRANCH:-main}"
STAMP_FILE="${WORKSPACE}/.jenkins-checkout-stamp"
STAMP="${REPO_SLUG}@${BRANCH}"

sync_seed_files() {
  local seed="/var/jenkins_home/project-seed"
  if [[ ! -d "$seed" ]]; then
    return 0
  fi
  for f in docker-compose.ci.yml docker-compose.jenkins.yml docker-compose.override.yml; do
    if [[ -f "${seed}/${f}" ]]; then
      cp "${seed}/${f}" "${f}"
      echo "Synced ${f} from project-seed"
    fi
  done
}

if [[ -f Dockerfile && -f docker-compose.yml && -f "$STAMP_FILE" && "$(cat "$STAMP_FILE")" == "$STAMP" ]]; then
  sync_seed_files
  echo "Workspace ready ($STAMP)"
  exit 0
fi

echo "Preparing workspace for $STAMP..."
find . -mindepth 1 -maxdepth 1 -exec rm -rf {} + 2>/dev/null || true

if [[ -n "${GITHUB_TOKEN:-}" ]]; then
  GIT_URL="https://x-access-token:${GITHUB_TOKEN}@github.com/${REPO_SLUG}.git"
else
  GIT_URL="https://github.com/${REPO_SLUG}.git"
fi

echo "Cloning ${REPO_SLUG} (branch ${BRANCH})..."
if ! git clone --depth 1 --branch "${BRANCH}" "${GIT_URL}" .; then
  echo "ERROR: git clone failed for ${REPO_SLUG} branch ${BRANCH}"
  exit 1
fi

if [[ ! -f Dockerfile ]]; then
  echo "ERROR: Dockerfile missing after clone"
  ls -la
  exit 1
fi

sync_seed_files
echo "$STAMP" > "$STAMP_FILE"
echo "Checkout OK"
