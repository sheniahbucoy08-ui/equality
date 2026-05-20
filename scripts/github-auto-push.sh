#!/usr/bin/env bash
# GitHub Auto-Push for EqualVoice CI/CD
# Usage: ./github-auto-push.sh ["commit message"] [branch]
set -euo pipefail

COMMIT_MESSAGE="${1:-Automated build ${BUILD_NUMBER:-local}}"
TARGET_BRANCH="${2:-}"
GITHUB_REPO="${GITHUB_REPO:-sheniahbucoy08-ui/equality}"

if [[ -z "${GITHUB_TOKEN:-}" ]]; then
  echo "Error: set GITHUB_TOKEN in .env"
  exit 1
fi

if [[ -z "$TARGET_BRANCH" ]]; then
  TARGET_BRANCH="$(git branch --show-current)"
fi

REMOTE_URL="https://x-access-token:${GITHUB_TOKEN}@github.com/${GITHUB_REPO}.git"

echo "=== GitHub Auto-Push ==="
echo "Repository: ${GITHUB_REPO}"
echo "Branch: ${TARGET_BRANCH}"
echo "Message: ${COMMIT_MESSAGE}"
echo ""

if ! git config user.name &>/dev/null; then
  git config user.name "Jenkins CI"
  git config user.email "jenkins@equalvoice.local"
fi

HAS_CHANGES=0
if ! git diff-index --quiet HEAD -- 2>/dev/null; then
  HAS_CHANGES=1
elif [[ -n "$(git status --porcelain)" ]]; then
  HAS_CHANGES=1
fi

if [[ "$HAS_CHANGES" -eq 1 ]]; then
  git add -A
  git commit -m "$COMMIT_MESSAGE" || true
else
  echo "No changes to commit"
fi

echo "Syncing with remote..."
if git fetch "$REMOTE_URL" "$TARGET_BRANCH" 2>/dev/null; then
  if git rev-parse -q FETCH_HEAD &>/dev/null; then
    git rebase FETCH_HEAD || {
      echo "Rebase failed (conflicts). Resolve locally, then push again."
      exit 1
    }
  fi
else
  echo "Remote branch new or not found - will create on push"
fi

echo "Pushing to GitHub..."
git push "$REMOTE_URL" "HEAD:${TARGET_BRANCH}"
echo "=== GitHub push OK ==="
