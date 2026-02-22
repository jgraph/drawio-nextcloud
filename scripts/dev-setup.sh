#!/bin/bash
set -e

cd "$(dirname "$0")/.."

echo "Building JavaScript..."
npm run build

echo "Starting Nextcloud 32..."
docker compose up -d

echo "Waiting for Nextcloud to be ready (this may take a minute on first run)..."
MAX_WAIT=120
WAITED=0
until curl -s -o /dev/null -w "%{http_code}" http://localhost:8088/status.php 2>/dev/null | grep -q "200"; do
    sleep 3
    WAITED=$((WAITED + 3))
    if [ $WAITED -ge $MAX_WAIT ]; then
        echo "Timed out waiting for Nextcloud. Check: docker compose logs nextcloud"
        exit 1
    fi
done

echo "Enabling drawio app..."
docker compose exec -u 33 nextcloud php occ app:enable drawio

echo ""
echo "========================================="
echo "  Nextcloud 32 is ready!"
echo "  URL:   http://localhost:8088"
echo "  Login: admin / admin"
echo "========================================="
