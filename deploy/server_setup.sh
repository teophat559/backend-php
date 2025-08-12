#!/usr/bin/env bash
set -euo pipefail

# Cross-distro Apache + PHP + MySQL setup and vhost provisioning

# ===== Config =====
DOMAIN=${DOMAIN:-"specialprogram2025.online"}
DOCROOT=${DOCROOT:-"/home/${DOMAIN}/public_html"}
PHP_VERSION=${PHP_VERSION:-"8.1"}

# Database (override via env or export before running)
DB_HOST=${DB_HOST:-"localhost"}
DB_NAME=${DB_NAME:-"spec_specialprogram2025"}
DB_USER=${DB_USER:-"spec_specialprogram"}
DB_PASS=${DB_PASS:-"123123zz@"}

# App env
APP_URL=${APP_URL:-"https://${DOMAIN}"}
ADMIN_SECURITY_KEY=${ADMIN_SECURITY_KEY:-"SP2025_ADMIN_SECURE_KEY_$(tr -dc A-Za-z0-9 </dev/urandom | head -c 24)"}
TIMEZONE=${TIMEZONE:-"Asia/Ho_Chi_Minh"}
SESSION_SECURE_COOKIE=${SESSION_SECURE_COOKIE:-"1"}
DATA_ENCRYPTION_KEY=${DATA_ENCRYPTION_KEY:-"$(tr -dc A-Za-z0-9 </dev/urandom | head -c 32)"}

# ===== Detect OS =====
OS_ID="unknown"
if [ -f /etc/os-release ]; then
  . /etc/os-release
  OS_ID=${ID}
fi

echo "[INFO] Detected OS: ${OS_ID}"

# ===== Install packages =====
if [[ ${OS_ID} == "ubuntu" || ${OS_ID} == "debian" ]]; then
  export DEBIAN_FRONTEND=noninteractive
  apt-get update -y
  apt-get install -y apache2 curl unzip gnupg lsb-release ca-certificates software-properties-common

  # PHP (Ubuntu 22.04 ships PHP 8.1 by default)
  apt-get install -y php php-cli php-common php-mysql php-curl php-xml php-gd php-zip php-mbstring libapache2-mod-php

  # MySQL server
  apt-get install -y mysql-server

  a2enmod rewrite headers deflate expires proxy proxy_http

  APACHE_CONF_DIR="/etc/apache2/sites-available"
  APACHE_USER="www-data"
  APACHE_SERVICE="apache2"

elif [[ ${OS_ID} == "centos" || ${OS_ID} == "rhel" || ${OS_ID} == "fedora" ]]; then
  yum -y install epel-release || true
  yum -y update
  yum -y install httpd curl unzip policycoreutils-python-utils
  # PHP 8.x (use remi on EL7/8, on EL9 appstream may have 8.1)
  yum -y install php php-cli php-common php-mysqlnd php-curl php-xml php-gd php-zip php-mbstring
  yum -y install mariadb-server || yum -y install mysql-server

  systemctl enable firewalld || true
  systemctl start firewalld || true
  firewall-cmd --permanent --add-service=http || true
  firewall-cmd --permanent --add-service=https || true
  firewall-cmd --reload || true

  APACHE_CONF_DIR="/etc/httpd/conf.d"
  APACHE_USER="apache"
  APACHE_SERVICE="httpd"
else
  echo "[ERROR] Unsupported OS. Please install Apache/PHP/MySQL manually."
  exit 1
fi

# ===== Create docroot =====
mkdir -p "${DOCROOT}"
chown -R ${APACHE_USER}:${APACHE_USER} "${DOCROOT}"
chmod -R 755 "${DOCROOT}"

# ===== VirtualHost config =====
VHOST_FILE="${APACHE_CONF_DIR}/${DOMAIN}.conf"
cat >"${VHOST_FILE}" <<EOF
<VirtualHost *:80>
    ServerName ${DOMAIN}
    ServerAlias www.${DOMAIN}
    DocumentRoot ${DOCROOT}

    <Directory ${DOCROOT}>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog logs/${DOMAIN}-error.log
    CustomLog logs/${DOMAIN}-access.log combined

    <IfModule mod_headers.c>
        Header always set X-Content-Type-Options nosniff
        Header always set X-Frame-Options DENY
        Header always set X-XSS-Protection "1; mode=block"
        Header always set Referrer-Policy "strict-origin-when-cross-origin"
        Header always set Permissions-Policy "geolocation=(), microphone=(), camera=()"
    </IfModule>

    <IfModule mod_deflate.c>
        AddOutputFilterByType DEFLATE text/plain text/html text/xml text/css application/xml application/xhtml+xml application/rss+xml application/javascript application/x-javascript application/json
    </IfModule>

    <IfModule mod_expires.c>
        ExpiresActive on
        ExpiresByType text/css "access plus 1 year"
        ExpiresByType application/javascript "access plus 1 year"
        ExpiresByType image/png "access plus 1 year"
        ExpiresByType image/jpg "access plus 1 year"
        ExpiresByType image/jpeg "access plus 1 year"
        ExpiresByType image/gif "access plus 1 year"
        ExpiresByType image/webp "access plus 1 year"
        ExpiresByType image/svg+xml "access plus 1 year"
        ExpiresByType font/woff "access plus 1 year"
        ExpiresByType font/woff2 "access plus 1 year"
    </IfModule>
</VirtualHost>
EOF

if [[ ${OS_ID} == "ubuntu" || ${OS_ID} == "debian" ]]; then
  a2ensite "${DOMAIN}.conf" || true
fi

# ===== MySQL: create DB/user if needed =====
MYSQL_ROOT_FLAGS=""
if mysql -uroot -e "SELECT 1" >/dev/null 2>&1; then
  MYSQL_CMD="mysql -uroot"
elif mysql -uroot -p"${DB_PASS}" -e "SELECT 1" >/dev/null 2>&1; then
  MYSQL_CMD="mysql -uroot -p'${DB_PASS}'"
else
  MYSQL_CMD="mysql" # last resort
fi

${MYSQL_CMD} <<SQL || true
CREATE DATABASE IF NOT EXISTS \
  \
  ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL

# ===== .env provisioning (if project already synced) =====
ENV_FILE="${DOCROOT}/.env"
if [ ! -f "${ENV_FILE}" ]; then
  cat >"${ENV_FILE}" <<ENV
APP_NAME=Special Program 2025
APP_URL=${APP_URL}
ADMIN_SECURITY_KEY=${ADMIN_SECURITY_KEY}
TIMEZONE=${TIMEZONE}
ERROR_DISPLAY=0
ERROR_LOG_PATH=/var/log/php_errors.log
SESSION_TIMEOUT=3600
MAX_LOGIN_ATTEMPTS=5
LOGIN_TIMEOUT=900
SESSION_SECURE_COOKIE=1
DB_HOST=${DB_HOST}
DB_NAME=${DB_NAME}
DB_USER=${DB_USER}
DB_PASS=${DB_PASS}
BROWSER_API_URL=http://127.0.0.1:40000
BROWSER_API_KEY=
BROWSER_PROFILE_ID=
DATA_ENCRYPTION_KEY=${DATA_ENCRYPTION_KEY}
ENV
  chown ${APACHE_USER}:${APACHE_USER} "${ENV_FILE}"
  chmod 600 "${ENV_FILE}"
fi

# ===== Import database schema if provided in docroot =====
if [ -f "${DOCROOT}/setup-database.sql" ]; then
  echo "[INFO] Importing database schema from setup-database.sql"
  TMP_SCHEMA="/tmp/schema_${DB_NAME}.sql"
  # Remove DB/user creation and adjust DB/user names if present
  sed -E \
    -e '/^CREATE DATABASE/Id' \
    -e '/^CREATE USER/Id' \
    -e '/^GRANT ALL PRIVILEGES/Id' \
    -e '/^FLUSH PRIVILEGES/Id' \
    -e '/^USE /Id' \
    -e "s/specialprogram2025/${DB_NAME}/g" \
    -e "s/`echo specialprogram`/${DB_USER}/g" \
    "${DOCROOT}/setup-database.sql" > "${TMP_SCHEMA}"
  mysql -u"${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" < "${TMP_SCHEMA}" || true
  rm -f "${TMP_SCHEMA}"
fi

# ===== Apache restart =====
systemctl enable ${APACHE_SERVICE}
systemctl restart ${APACHE_SERVICE}

echo "[OK] Server setup completed for ${DOMAIN} with docroot ${DOCROOT}"
