#!/bin/bash
# start.sh
set -e

# Run database migrations
echo "=== Starting startup migrations ==="
php /var/www/html/php/migrate.php

# Configure Apache port based on Railway PORT environment variable
if [ -n "$PORT" ]; then
    echo "Configuring Apache to listen on dynamic Railway PORT: $PORT"
    sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
    sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/g" /etc/apache2/sites-available/000-default.conf
else
    echo "No PORT environment variable detected. Defaulting to port 80."
fi

# Execute Apache in foreground
echo "=== Starting Apache Web Server ==="
exec apache2-foreground
