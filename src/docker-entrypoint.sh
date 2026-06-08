#!/bin/sh
set -e

# Run database migrations
echo "Running database migrations..."
php artisan migrate --force

# Execute the main container command
echo "Starting Apache Web Server..."
exec "$@"
