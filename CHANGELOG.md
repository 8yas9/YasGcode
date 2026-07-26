# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-07-26

### Added
- Soft Deletes & Timestamps toggle in model generation
- Automatic `belongsTo()` relationship generation from `_id` columns
- FormRequest class generation
- Policy class generation
- API Routes generation (`routes/api.php`)
- Menu Item generation for `contentNavbarLayout.blade.php`
- Tom Select integration for searchable dropdowns
- Export/Import config as JSON
- Search/Filter for database tables
- Progress bar during generation
- Delete all generated files, routes, and menu items (Undo)
- Automatic rollback on generation failure
- Arabic language support (RTL)
- `searchable_select` per-column option
- `custom_label` per-column option
- `$casts` auto-generation based on column types
- PHPStan configuration
- PHP CS Fixer configuration
- PHPUnit test suite
- GitHub Actions CI/CD pipeline
- Contributing guide

### Changed
- Renamed package dashboard to "Magic Generator v1.1"
- Improved error messages with try-catch and user-friendly dispatch events
- Config now includes `menu` and `form_request`/`policy` paths
- Model stub now uses dynamic `$timestamps` value

## [1.0.0] - 2026-07-20

### Added
- Initial release
- Livewire v3 dashboard with Matrix and Settings tabs
- AJAX and Livewire tech stacks
- Column configuration with input types, validation, visibility
- Expandable advanced column config (label, validation, select options, foreign keys)
- Auto-fill validation button
- File generation with progress tracking
- Route appending with markers for clean removal
- Stubs publishing support
- Config publishing support
- View publishing support
