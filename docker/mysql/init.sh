#!/bin/bash
# Runs once on first container initialisation (via /docker-entrypoint-initdb.d/).
# Ensures the app user uses caching_sha2_password from the very first boot.
set -e

mysql -u root -p"${MYSQL_ROOT_PASSWORD}" <<-EOSQL
    ALTER USER '${MYSQL_USER}'@'%' IDENTIFIED WITH caching_sha2_password BY '${MYSQL_PASSWORD}';
    FLUSH PRIVILEGES;
EOSQL
