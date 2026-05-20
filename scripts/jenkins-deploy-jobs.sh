#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
CONTAINER="${JENKINS_CONTAINER:-equalvoice_jenkins}"

cd "$ROOT"

if ! docker ps --format '{{.Names}}' | grep -qx "$CONTAINER"; then
  docker compose -f docker-compose.jenkins.yml up -d
  sleep 15
fi

for job in job1 job2 job3; do
  docker exec -u root "$CONTAINER" mkdir -p "/var/jenkins_home/jobs/$job"
  docker cp "jenkins/jobs/$job/config.xml" "$CONTAINER:/var/jenkins_home/jobs/$job/config.xml"
  docker exec -u root "$CONTAINER" chown -R jenkins:jenkins "/var/jenkins_home/jobs/$job"
  echo "Deployed $job"
done

docker restart "$CONTAINER" >/dev/null
sleep 20

echo "Jobs: http://localhost:9090/ (job1 build, job2 test, job3 deployment)"
