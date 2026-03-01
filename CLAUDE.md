# CLAUDE.md - drawio-nextcloud

## Project Overview

Nextcloud app that integrates the draw.io (diagrams.net) diagram editor. Users can create and edit `.drawio` diagrams and `.dwb` whiteboards directly within Nextcloud. The draw.io editor runs in an iframe and communicates with the Nextcloud backend via postMessage.

- **App ID:** `drawio`
- **Namespace:** `OCA\Drawio`
- **License:** AGPL
- **Nextcloud compatibility:** 32+ (min-version in `appinfo/info.xml`)
- **Version:** defined in both `appinfo/info.xml` and `package.json` (keep in sync)

## Repository Structure

```
appinfo/           App manifest (info.xml) and route definitions (routes.php)
lib/               PHP backend
  AppConfig.php    Configuration manager (get/set for all admin settings)
  AppInfo/         Application bootstrap, DI registration, MIME types
  Controller/      EditorController (file CRUD, revisions) & SettingsController
  Listeners/       Event handlers (file delete cleanup, reference widget loader, template creator)
  Reference/       Reference Provider for inline diagram previews in Text/Collectives/Talk
  Migration/       MIME type registration/unregistration repair steps
  Preview/         Thumbnail generation from cached PNG previews
  Settings/        Admin settings panel registration
src/               JavaScript source (webpack entry points)
  editor.js        Editor page – iframe communication, save/load, autosave, previews
  main.js          File list integration – file actions, new file menu entries
  settings.js      Admin settings form
  reference.js     Reference widget registration for inline diagram previews
  components/      Vue components (DrawioReferenceWidget.vue)
js/                Compiled webpack output (do not edit directly)
templates/         PHP templates for editor and settings pages
css/               Stylesheets (main, editor, settings)
img/               SVG icons (app, drawio file type, whiteboard)
l10n/              Translations (~100 languages, managed in-repo)
scripts/           Build/maintenance scripts (extract-strings.js)
.github/workflows/ CI: release pipeline (release.yml), stale bot (stale.yml)
```

## Build & Development

### Prerequisites
- Node.js 20+
- npm

### Commands
```bash
npm ci                  # Install dependencies (use ci, not install)
npm run build           # Production build (webpack, output to js/)
npm run dev             # Development build
npm run watch           # Development build with file watching
npm run extract-strings # Extract translatable strings to l10n/source-strings.json
```

### Local Development (Docker)
See `DEV.md` for full details. Quick start:
```bash
npm ci
./scripts/dev-setup.sh        # builds, starts NC 33 + MariaDB, enables the app
```
Then open http://localhost:8088 (admin / admin). PHP changes are live (volume-mounted); JS changes require `npm run build`.

**Important:** Do not change the app version in `info.xml` during development — it will break the Nextcloud instance.

## Architecture

### Data Flow
1. User clicks a `.drawio`/`.dwb` file → `main.js` registers file actions via `@nextcloud/files`
2. Editor page loads → `editor.js` creates iframe pointing to draw.io (embed.diagrams.net or self-hosted)
3. draw.io ↔ Nextcloud communication via `postMessage` / "remote invoke" protocol
4. Save/load operations go through `EditorController` PHP endpoints
5. PNG previews are generated client-side and saved via `savePreview` endpoint

### API Routes (all under `/apps/drawio/`)
| Method | URL                    | Controller Method        |
|--------|------------------------|--------------------------|
| GET    | `/edit`                | `EditorController@index` |
| GET    | `/ajax/load`           | `EditorController@load`  |
| GET    | `/ajax/getFileInfo`    | `EditorController@getFileInfo` |
| GET    | `/ajax/getFileRevisions` | `EditorController@getFileRevisions` |
| GET    | `/ajax/loadFileVersion` | `EditorController@loadFileVersion` |
| POST   | `/ajax/new`            | `EditorController@create` |
| PUT    | `/ajax/save`           | `EditorController@save`  |
| POST   | `/ajax/savePreview`    | `EditorController@savePreview` |
| POST   | `/ajax/settings`       | `SettingsController@settings` |

### Key Patterns
- **Concurrency:** ETags for optimistic conflict detection; ILockingProvider for file locking
- **Sharing:** Supports both authenticated users and public share tokens (separate code paths)
- **Configuration:** All admin settings stored via Nextcloud's config API (`AppConfig.php`)
- **MIME types:** `application/x-drawio` (.drawio) and `application/x-drawio-wb` (.dwb), registered in repair steps
- **Translations:** Use `t('drawio', 'key')` in JS (`@nextcloud/l10n`), `$l->t('key')` in PHP templates, and `$this->trans->t('key')` in PHP controllers
- **Frontend globals:** `OCA.DrawIO` namespace used in `main.js`; `editor.js` uses an IIFE with `OCA` parameter

## File Conventions
- PHP follows PSR-2/PSR-12 style
- JavaScript uses ES6+ imports with `@nextcloud/*` packages
- No linter or formatter is configured
- No test framework is set up

## Translations
Managed in-repo. The `l10n/` directory contains `.js` and `.json` files for ~100 languages. These are the runtime format Nextcloud loads directly.

- Run `npm run extract-strings` to regenerate `l10n/source-strings.json` — the canonical list of all English source strings
- The script scans `src/*.js`, `templates/*.php`, and `lib/**/*.php` for translation function calls
- To add/update a translation: edit the corresponding `l10n/{lang}.js` and `l10n/{lang}.json` files directly
- The `.js` format uses `OC.L10N.register("drawio", {...}, "pluralForm")` and the `.json` format uses `{"translations": {...}, "pluralForm": "..."}`

## Release Process
Handled by `.github/workflows/release.yml` on version tags (`v*`):
1. Checkout → npm ci → npm run build
2. Create zip/tar.gz archives (excluding dev files)
3. Upload to GitHub Releases
4. Sign with RSA key and publish to Nextcloud App Store
