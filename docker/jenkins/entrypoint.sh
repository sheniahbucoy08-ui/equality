#!/bin/bash
set -e

if [ -S /var/run/docker.sock ]; then
    SOCK_GID=$(stat -c '%g' /var/run/docker.sock)
    if ! getent group "$SOCK_GID" > /dev/null 2>&1; then
        groupadd -g "$SOCK_GID" dockerhost
    fi
    GROUP_NAME=$(getent group "$SOCK_GID" | cut -d: -f1)
    usermod -aG "$GROUP_NAME" jenkins
fi

exec gosu jenkins /usr/bin/tini -- /usr/local/bin/jenkins.sh "$@"
