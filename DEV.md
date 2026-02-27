# Development Guide

## Prerequisites

- Docker and Docker Compose
- Node.js 20+
- npm

## Quick Start

```bash
npm ci
./scripts/dev-setup.sh
```

This builds the JavaScript, starts Nextcloud 33 with MariaDB in Docker, waits for it to be ready, and enables the draw.io app. Takes about a minute on first run (image pulls).

Open **http://localhost:8088** and log in with **admin / admin**.

## Development Cycle

For **JavaScript/CSS changes** (`src/`):

```bash
npm run build    # rebuild JS bundles
                 # then reload browser
```

Or use `npm run watch` for automatic rebuilds on save.

For **PHP changes** (`lib/`, `templates/`):

Changes are live immediately (volume-mounted). Just reload the browser.

For **MIME type or migration changes**:

```bash
./scripts/dev-rebuild.sh    # rebuilds JS + clears NC caches + re-runs MIME registration
```

## Tearing Down

```bash
docker compose down       # stop containers, keep data
docker compose down -v    # stop containers AND wipe all data (fresh start)
```

## Troubleshooting

**App won't enable / "update required" error:**
Don't change the version in `appinfo/info.xml` during development. If you already did:
```bash
docker compose down -v    # wipe and start fresh
./scripts/dev-setup.sh
```

**Permission errors in container:**
```bash
docker compose exec nextcloud chown -R www-data:www-data /var/www/html/custom_apps/drawio
```

**Stale PHP cache:**
```bash
docker compose exec -u 33 nextcloud php occ maintenance:repair
```

**Rebuilding from scratch:**
```bash
docker compose down -v
./scripts/dev-setup.sh
```

## Test Checklist

After making changes, walk through the relevant sections:

### Admin Settings
- [ ] Navigate to Settings > Administration > Draw.io
- [ ] All form fields render (URL, theme, dark mode, language, offline, autosave, libraries, previews, config textarea)
- [ ] Change settings, click Save > success toast appears
- [ ] Settings persist after page reload

### File Creation
- [ ] In Files app, click "+" > "New draw.io Diagram" appears
- [ ] Click it > new `.drawio` file created, editor opens
- [ ] In Files app, click "+" > "New draw.io Whiteboard" appears
- [ ] Click it > new `.dwb` file created, editor opens

### Editing
- [ ] Open an existing `.drawio` file > draw.io editor loads in iframe
- [ ] Draw something, wait for autosave > status message appears
- [ ] Close editor > returns to Files app, file size updated
- [ ] Reopen the file > previous edits preserved

### Previews
- [ ] With previews enabled: edit a diagram, close > preview thumbnail visible in Files list
- [ ] With previews disabled in admin settings: no thumbnail generated

### Public Sharing
- [ ] Share a `.drawio` file via public link
- [ ] Open the public link in an incognito window > editor loads
- [ ] Verify read-only vs read-write based on share permissions

### Translations
- [ ] Change NC user language to German (Settings > Personal > Language)
- [ ] Admin settings page shows translated strings
- [ ] File menu entries show translated text ("Neues draw.io Diagramm")

### Versions (requires Files Versions app)
- [ ] Edit and save a file multiple times
- [ ] Check if version history is accessible from the editor
