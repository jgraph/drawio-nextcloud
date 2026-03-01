# Contributing to draw.io for Nextcloud

Thanks for your interest in contributing!

## Development Setup

See [DEV.md](DEV.md) for full instructions. Quick start:

```bash
npm ci
./scripts/dev-setup.sh
```

This starts a local Nextcloud instance at http://localhost:8088 with the draw.io app enabled.

## Making Changes

- **PHP changes** (`lib/`, `templates/`) are live-reloaded (volume-mounted)
- **JS/CSS changes** (`src/`) require rebuilding: `npm run build` or `npm run watch`
- Run through the relevant sections of the [test checklist](DEV.md#test-checklist) before submitting

## Code Style

- PHP: PSR-2/PSR-12
- JavaScript: ES6+ with `@nextcloud/*` packages
- No linter or formatter is configured — follow existing patterns

## Submitting a Pull Request

1. Fork the repository and create your branch from `main`
2. Make your changes and test them locally
3. Open a pull request with a clear description of the change
4. Reference any related issues

## Translations

Translation files are in `l10n/`. To add or update a translation, edit the corresponding `l10n/{lang}.js` and `l10n/{lang}.json` files. Run `npm run extract-strings` to regenerate the source string list.

## License

By contributing, you agree that your contributions will be licensed under the AGPL-3.0 license.
