#!/bin/bash
set -e

echo "🚀 Starting EqualVoice Application..."

# Wait for MySQL to be ready
echo "⏳ Waiting for MySQL to be ready..."
COUNTER=0
while ! mysqladmin ping -h"mysql" -u"${DB_USER}" -p"${DB_PASS}" --silent 2>/dev/null; do
    COUNTER=$((COUNTER+1))
    if [ $COUNTER -gt 30 ]; then
        echo "❌ MySQL did not start in time"
        exit 1
    fi
    sleep 1
done
echo "✓ MySQL is ready"

# Check if database exists
echo "📦 Checking database..."
mysql -h"mysql" -u"${DB_USER}" -p"${DB_PASS}" -e "USE ${DB_NAME};" 2>/dev/null || {
    echo "📁 Creating database..."
    mysql -h"mysql" -u"root" -p"${DB_ROOT_PASSWORD}" -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME};"
    mysql -h"mysql" -u"root" -p"${DB_ROOT_PASSWORD}" -e "GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'%' IDENTIFIED BY '${DB_PASS}'; FLUSH PRIVILEGES;"
    
    # Import database schema if it exists
    if [ -f "/var/www/html/sql/equalvoice.sql" ]; then
        echo "📥 Importing database schema..."
        mysql -h"mysql" -u"${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" < /var/www/html/sql/equalvoice.sql
        echo "✓ Database schema imported"
    fi
}

echo "✓ Database check completed"

# Set proper permissions
echo "🔐 Setting file permissions..."
chown -R www-data:www-data /var/www/html
find /var/www/html -type d -exec chmod 755 {} \;
find /var/www/html -type f -exec chmod 644 {} \;
chmod 755 /var/www/html

echo "✓ File permissions set"

# Start Apache
echo "🌐 Starting Apache web server..."
exec apache2-foreground
