#!/bin/bash
set -e

echo "=== Fixing Database Connection ==="

# Create database
echo "Creating database..."
mysql -uroot -e "CREATE DATABASE IF NOT EXISTS spec_specialprogram2025 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" || echo "Database creation failed"

# Create user
echo "Creating user..."
mysql -uroot -e "CREATE USER IF NOT EXISTS 'spec_specialprogram'@'localhost' IDENTIFIED BY '123123zz@';" || echo "User creation failed"

# Grant privileges
echo "Granting privileges..."
mysql -uroot -e "GRANT ALL PRIVILEGES ON spec_specialprogram2025.* TO 'spec_specialprogram'@'localhost'; FLUSH PRIVILEGES;" || echo "Privilege grant failed"

# Test connection
echo "Testing connection..."
export MYSQL_PWD="123123zz@"
mysql -u spec_specialprogram -e "SHOW DATABASES LIKE 'spec_specialprogram2025';" || echo "Connection test failed"

# Import schema if tables don't exist
echo "Checking tables..."
if ! mysql -u spec_specialprogram -e "USE spec_specialprogram2025; SHOW TABLES;" | grep -q .; then
    echo "Importing schema..."
    mysql -u spec_specialprogram spec_specialprogram2025 < /home/specialprogram2025.online/public_html/setup-database.sql || echo "Schema import failed"
else
    echo "Tables already exist"
fi

echo "=== Database setup completed ==="
