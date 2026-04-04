#!/bin/bash
set -e

# Generate a SQL init file from the actual runtime credentials.
# --init-file is executed by mysqld on every server start, so this ensures
# the app user always uses caching_sha2_password regardless of how the
# volume was originally initialised.
cat > /tmp/fix-auth.sql <<EOF
ALTER USER IF EXISTS '${MYSQL_USER}'@'%' IDENTIFIED WITH caching_sha2_password BY '${MYSQL_PASSWORD}';
FLUSH PRIVILEGES;
EOF

# Hand off to the real MySQL entrypoint with our init file injected.
# exec replaces this shell so mysqld gets correct PID and signal handling.
exec /usr/local/bin/docker-entrypoint.sh mysqld --init-file=/tmp/fix-auth.sql
