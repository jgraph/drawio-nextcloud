#!/bin/bash
set -e

cd "$(dirname "$0")/.."

echo "Building JavaScript..."
npm run build

echo "Clearing Nextcloud caches..."
docker compose exec -u 33 nextcloud php occ maintenance:repair
docker compose exec -u 33 nextcloud php occ maintenance:mimetype:update-js

echo "Done. Reload your browser."
