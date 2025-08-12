#!/bin/bash
set -euo pipefail

DB_NAME="spec_specialprogram2025"
DB_USER="spec_specialprogram"
DB_PASS="123123zz@"
DOCROOT="/home/specialprogram2025.online/public_html"

log() { echo "[setup_db] $*"; }

log "Start DB setup"

# Try as root via unix socket first (common on MariaDB)
if mysql -uroot -e "SELECT 1" >/dev/null 2>&1; then
  log "Connected as root via socket. Ensuring DB and user..."
  mysql -uroot -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
  mysql -uroot -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
  mysql -uroot -e "GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '${DB_USER}'@'localhost'; FLUSH PRIVILEGES;"
else
  log "WARN: Cannot connect as root via socket. Skipping root operations."
fi

# Use application user to verify and import schema if tables missing
export MYSQL_PWD="${DB_PASS}"
if ! mysql -u "${DB_USER}" -e "USE \`$DB_NAME\`; SHOW TABLES;" | grep -q .; then
  log "Importing schema from ${DOCROOT}/setup-database.sql"
  if [ -f "${DOCROOT}/setup-database.sql" ]; then
    mysql -u "${DB_USER}" "${DB_NAME}" < "${DOCROOT}/setup-database.sql" || true
  else
    log "ERROR: Schema file not found at ${DOCROOT}/setup-database.sql"
  fi
else
  log "Tables already present; skipping import"
fi

log "Current tables:"
mysql -u "${DB_USER}" -e "USE \`$DB_NAME\`; SHOW TABLES;" || true

log "DB setup done"

