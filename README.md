# Magic Generator

[![Latest Version](https://img.shields.io/github/v/release/yasksalim/magic-generator?style=flat-square)](https://github.com/yasksalim/magic-generator/releases)
[![License](https://img.shields.io/github/license/yasksalim/magic-generator?style=flat-square)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-%5E8.2-blue?style=flat-square)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-10%20%7C%2011%20%7C%2012-red?style=flat-square)](https://laravel.com)
[![Tests](https://img.shields.io/github/actions/workflow/status/yasksalim/magic-generator/tests.yml?style=flat-square&label=tests)](https://github.com/yasksalim/magic-generator/actions)
[![Coverage](https://img.shields.io/badge/coverage-90%25-brightgreen?style=flat-square)](https://github.com/yasksalim/magic-generator)

An advanced CRUD code generator for Laravel with a beautiful Livewire 3 dashboard.
Generate production-ready CRUD modules from your database tables in seconds.

## Features

- 🚀 **Instant CRUD Generation** — Generate controllers, models, Livewire components, and views from a single table
- 🎨 **Two Tech Stacks** — Choose between AJAX Controllers or Livewire v3 full-page components
- 🔍 **Advanced Column Configuration** — Expandable rows with customizable input types, validation, labels, and relationships
- 🔗 **Automatic Relationships** — Detects `_id` columns and generates `belongsTo()` relationships
- 🗃️ **Soft Deletes & Timestamps** — Toggle SoftDeletes trait and `$timestamps` in the model
- 📋 **FormRequest Class** — Auto-generate validation FormRequest classes
- 🛡️ **Policy Class** — Auto-generate authorization Policy classes
- 🌐 **API Routes** — Generate RESTful API routes in `routes/api.php`
- 🔎 **Searchable Selects** — Tom Select integration for database-powered dropdowns
- 📤 **Export/Import Config** — Save and restore your column configurations as JSON
- 📋 **Sidebar Menu Item** — Auto-generate menu items for your navbar layouts
- 🗑️ **Delete Generated** — One-click deletion of all generated files, routes, and menu items
- 🔄 **Rollback on Failure** — Automatic cleanup if generation fails mid-way
- 🌍 **Arabic & English** — RTL support with full Arabic translation
- 📱 **Responsive UI** — Beautiful Tailwind CSS dashboard with dark mode ready
- 🔌 **Flexible Stubs** — Publish and customize all stub templates

## Installation

```bash
composer require yasksalim/magic-generator
```

The package auto-registers its service provider. Publish the config if you want to customize:

```bash
php artisan vendor:publish --tag=magic-generator-config
```

## Usage

1. Visit `/magic-generator` in your browser
2. Select a database table from the dropdown
3. Configure columns in the Matrix tab (input types, validation, visibility)
4. Go to the Advanced tab to enable Soft Deletes, FormRequest, Policy, API Routes, Menu Item
5. Click **Generate CRUD**

## Configuration

```php
// config/magic-generator.php
return [
    'stubs_path' => __DIR__ . '/../stubs',

    'paths' => [
        'controller'    => 'app/Http/Controllers',
        'model'         => 'app/Models',
        'livewire'      => 'app/Livewire',
        'views'         => 'resources/views',
        'form_request'  => 'app/Http/Requests',
        'policy'        => 'app/Policies',
    ],

    'defaults' => [
        'success_message'            => 'Record created successfully.',
        'delete_confirmation_message' => 'Are you sure you want to delete this record?',
    ],

    'routes' => [
        'prefix'    => 'magic-generator',
        'middleware' => ['web'],
    ],

    'menu' => [
        'layout_path' => 'resources/views/layouts/contentNavbarLayout.blade.php',
        'enabled'     => false,
    ],
];
```

## Available Stubs

All stubs are publishable. Run:

```bash
php artisan vendor:publish --tag=magic-generator-stubs
```

| Stub | Path |
|------|------|
| Controller | `stubs/controller.stub` |
| Model | `stubs/model.stub` |
| Livewire Index | `stubs/livewire/IndexComponent.stub` |
| Livewire Create | `stubs/livewire/CreateComponent.stub` |
| Livewire Edit | `stubs/livewire/EditComponent.stub` |
| Livewire Show | `stubs/livewire/ShowComponent.stub` |
| Index View | `stubs/index-view.stub` |
| Create View | `stubs/create-view.stub` |
| Edit View | `stubs/edit-view.stub` |
| Show View | `stubs/show-view.stub` |
| Print View | `stubs/print-view.stub` |
| FormRequest | `stubs/form-request.stub` |
| Policy | `stubs/policy.stub` |

## Placeholders

| Placeholder | Replaced With |
|-------------|---------------|
| `{{ModelName}}` | StudlyCase model name (e.g., `User`) |
| `{{modelName}}` | camelCase model name (e.g., `user`) |
| `{{TableName}}` | Original table name |
| `{{tableName}}` | Same as TableName |
| `{{Namespace}}` | Full PHP namespace |
| `{{namespace}}` | Escaped namespace for use in `use` statements |
| `{{SuccessMessage}}` | Configurable success message |
| `{{DeleteConfirmationMessage}}` | Configurable delete confirmation |
| `{{DisplayNameColumn}}` | The column used for display |
| `{{IndexFields}}` | Generated table cell columns for index |
| `{{CreateFields}}` | Generated form fields for create |
| `{{EditFields}}` | Generated form fields for edit |
| `{{PrintFields}}` | Generated table rows for print view |
| `{{ShowFields}}` | Generated table rows for show view |
| `{{ValidationRules}}` | Generated validation rules |
| `{{FillableFields}}` | Comma-separated fillable column list |
| `{{SoftDeletesImport}}` | `use SoftDeletes;` import if enabled |
| `{{SoftDeletesTrait}}` | `use SoftDeletes;` trait if enabled |
| `{{Timestamps}}` | `true` or `false` |
| `{{Relationships}}` | Auto-generated belongsTo relationship methods |
| `{{CastsFields}}` | Auto-generated $casts array |

## Requirements

- PHP ^8.2
- Laravel ^10.0 ^11.0 ^12.0
- Livewire ^3.0
- `doctrine/dbal` (optional, recommended for precise column detection)

## License

MIT

## Author

**Yas K Salim**
- Email: yasksalim@gmail.com
- GitHub: [@yasksalim](https://github.com/yasksalim)
