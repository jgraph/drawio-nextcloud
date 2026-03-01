# ![](screenshots/icon.png) Draw.io integration for Nextcloud

[![GitHub release](https://img.shields.io/github/v/release/jgraph/drawio-nextcloud)](https://github.com/jgraph/drawio-nextcloud/releases)
[![License: AGPL v3](https://img.shields.io/badge/License-AGPL%20v3-blue.svg)](https://www.gnu.org/licenses/agpl-3.0)
[![Nextcloud](https://img.shields.io/badge/Nextcloud-32+-0082c9)](https://apps.nextcloud.com/apps/drawio)

Create and edit [draw.io](https://app.diagrams.net) diagrams and whiteboards directly within [Nextcloud](https://nextcloud.com). Note that draw.io is NOT open source software.

**[Install from the Nextcloud App Store](https://apps.nextcloud.com/apps/drawio)**

![](screenshots/drawio_integration.png)

## Features

- **Diagrams & Whiteboards** — create `.drawio` diagrams and `.dwb` whiteboards from the "New file" menu
- **Real-time collaboration** — multiple users can edit the same diagram simultaneously
- **Dark mode** — automatic or manual dark theme support
- **Diagram previews** — PNG thumbnails displayed in the Nextcloud file list
- **Public sharing** — view or edit diagrams via Nextcloud share links, including password-protected links
- **File versioning** — access and restore previous diagram revisions
- **Self-hosting** — use your own draw.io server instead of the official one
- **Offline mode** — work without an internet connection
- **Multilingual** — translated into 99 languages
- **Admin controls** — configure theme, language, autosave, custom libraries, and more

## Requirements

- [Nextcloud](https://nextcloud.com) >= 32
- [draw.io](https://github.com/jgraph/docker-drawio) >= 20.8.6 (if self-hosting)

## Installation

1. Copy the `drawio` directory to your Nextcloud server's `/apps/` directory (or install from the [App Store](https://apps.nextcloud.com/apps/drawio))
2. Go to **Apps** > **+ Apps** > **Not Enabled** and enable the **Draw.io** application
3. Go to **Admin settings** > **Draw.io** and click **Save** to register MIME types

## Real-time Collaboration

Real-time collaboration requires **Autosave enabled** and the official diagrams.net server (`https://embed.diagrams.net`). Self-hosted draw.io servers do not support real-time collaboration.

## Configuration

Go to **Admin settings** > **Draw.io** to configure:

![](screenshots/drawio_admin.png)

Available settings: draw.io server URL, theme, dark mode, language, autosave, custom libraries, offline mode, diagram previews, and editor configuration JSON.

If you would like to self-host draw.io, see [docker-drawio](https://github.com/jgraph/docker-drawio) (requires version 20.8.6+).

## Known Issues

- If you experience problems while updating Nextcloud, try disabling/removing the draw.io app (`/apps/drawio/`) and reinstalling it after the update completes.
- Clear the PHP cache after updating the app if you get undefined method/class errors. For PHP-FPM: `service php-fpm restart`.
- The Nextcloud integrity check may report a failure because the app registers custom MIME types and icons. This is expected and safe to ignore ([#26](https://github.com/jgraph/drawio-nextcloud/issues/26)).

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for development setup and guidelines.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for the full release history.

## Support and SOC 2

This repo is not covered by the JGraph SOC 2 process. We do not provide commercial services or support for this app.

## License

AGPL-3.0 — see [LICENSE](LICENSE).
