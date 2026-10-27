#!/bin/sh
set -e

PREFIX="${DATABASE_PREFIX:-}"
TEMPLATE="/docker-entrypoint-initdb.d/init.sql.template"

if [ -n "$PREFIX" ] && ! echo "$PREFIX" | grep -Eq '^[a-zA-Z0-9_]+$'; then
    echo "ERROR: Invalid DATABASE_PREFIX '$PREFIX'. Only alphanumeric characters and underscores are allowed." >&2
    exit 1
fi

if [ ! -f "$TEMPLATE" ] || [ ! -r "$TEMPLATE" ]; then
    echo "ERROR: Initialization template '$TEMPLATE' is missing or unreadable." >&2
    exit 1
fi

if [ -n "$PREFIX" ]; then
    echo "Initializing database with table prefix '${PREFIX}'..."
    sed -e "s/\`users\`/\`${PREFIX}users\`/g" \
        -e "s/\`dynamic_qrcodes\`/\`${PREFIX}dynamic_qrcodes\`/g" \
        -e "s/\`static_qrcodes\`/\`${PREFIX}static_qrcodes\`/g" \
        -e "s/\`login_attempts\`/\`${PREFIX}login_attempts\`/g" \
        -e "s/\`schema_version\`/\`${PREFIX}schema_version\`/g" \
        "$TEMPLATE" | mariadb -u root -p"${MARIADB_ROOT_PASSWORD:-$MYSQL_ROOT_PASSWORD}" "${MARIADB_DATABASE:-$MYSQL_DATABASE}"
else
    echo "Initializing database without table prefix..."
    mariadb -u root -p"${MARIADB_ROOT_PASSWORD:-$MYSQL_ROOT_PASSWORD}" "${MARIADB_DATABASE:-$MYSQL_DATABASE}" < "$TEMPLATE"
fi
