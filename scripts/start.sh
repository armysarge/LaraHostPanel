#!/bin/bash
# LaraHostPanel startup script
# This script starts the LaraHostPanel application and auto-starts configured projects

# Resolve the project root relative to this script's location
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PORT=8001

cd "$PROJECT_DIR"

# Run database migrations
echo "Checking database migrations..."
php artisan migrate --force --no-interaction || true

# Start auto-start projects
echo "Starting auto-start projects..."
php artisan app:start-auto-projects || true

# Start the Laravel development server in the foreground so systemd can track it
echo "Starting LaraHostPanel on port $PORT..."
exec php artisan serve --host=0.0.0.0 --port=$PORT --no-interaction
