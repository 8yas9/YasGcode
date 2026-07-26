<?php

namespace YasKSalim\MagicGenerator\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class GeneratorEngine
{
    protected string $stubsPath;

    protected array $paths;

    protected array $replaceable = [
        '{{ModelName}}',
        '{{modelName}}',
        '{{TableName}}',
        '{{tableName}}',
        '{{Namespace}}',
        '{{namespace}}',
        '{{SuccessMessage}}',
        '{{DeleteConfirmationMessage}}',
        '{{DisplayNameColumn}}',
        '{{DisplayName}}',
        '{{ModuleDir}}',
        '{{moduleDir}}',
        '{{IndexFields}}',
        '{{CreateFields}}',
        '{{EditFields}}',
        '{{PrintFields}}',
        '{{ShowFields}}',
        '{{ValidationRules}}',
        '{{FillableFields}}',
    ];

    public function __construct()
    {
        $this->stubsPath = config('magic-generator.stubs_path', __DIR__ . '/../../stubs');

        $this->paths = config('magic-generator.paths', [
            'controller' => 'app/Http/Controllers',
            'model'      => 'app/Models',
            'livewire'   => 'app/Livewire',
            'views'      => 'resources/views',
        ]);
    }

    public function run(
        string $table,
        string $techStack,
        array $columns,
        array $columnConfigs,
        string $displayNameColumn,
        string $successMessage,
        string $deleteConfirmationMessage,
        bool $modularFolders,
    ): array {
        $modelName  = Str::studly(Str::singular($table));
        $tableName  = $table;
        $moduleDir  = $modularFolders ? $modelName : '';
        $camelModel = lcfirst($modelName);
        $namespace  = $this->resolveNamespace($techStack, $moduleDir);

        $fields = [
            'fillable'   => $this->buildFillable($columns),
            'validation' => $this->buildValidationRules($columnConfigs),
            'index'      => $this->buildIndexFields($columns, $columnConfigs),
            'create'     => $this->buildCreateFields($columns, $columnConfigs),
            'edit'       => $this->buildEditFields($columns, $columnConfigs),
            'print'      => $this->buildPrintFields($columns, $columnConfigs),
            'show'       => $this->buildShowFields($columns, $columnConfigs),
        ];

        $replace = [
            '{{ModelName}}'              => $modelName,
            '{{modelName}}'              => $camelModel,
            '{{TableName}}'              => $tableName,
            '{{tableName}}'              => $tableName,
            '{{Namespace}}'              => $namespace,
            '{{namespace}}'              => str_replace('\\', '\\\\', $namespace),
            '{{SuccessMessage}}'         => $successMessage,
            '{{DeleteConfirmationMessage}}' => $deleteConfirmationMessage,
            '{{DisplayNameColumn}}'      => $displayNameColumn,
            '{{DisplayName}}'            => $displayNameColumn,
            '{{ModuleDir}}'              => $moduleDir,
            '{{moduleDir}}'              => $moduleDir ? Str::kebab($moduleDir) : '',
            '{{IndexFields}}'            => $fields['index'],
            '{{CreateFields}}'           => $fields['create'],
            '{{EditFields}}'             => $fields['edit'],
            '{{PrintFields}}'            => $fields['print'],
            '{{ShowFields}}'             => $fields['show'],
            '{{ValidationRules}}'        => $fields['validation'],
            '{{FillableFields}}'         => $fields['fillable'],
        ];

        $basePath  = base_path();
        $files     = [];
        $generated = [];

        if ($techStack === 'ajax') {
            $controllersDir = $moduleDir
                ? "{$basePath}/{$this->paths['controller']}/{$moduleDir}"
                : "{$basePath}/{$this->paths['controller']}";

            File::ensureDirectoryExists($controllersDir);
            $target = "{$controllersDir}/{$modelName}Controller.php";

            $stub = $this->loadStub('ajax/controller.stub');
            $this->writeFile($target, $this->replace($stub, $replace));
            $generated[] = ['type' => 'Controller', 'path' => $target, 'written' => true];

            $modelTarget = "{$basePath}/{$this->paths['model']}/{$modelName}.php";
            $modelStub = $this->loadStub('model.stub');
            $this->writeFile($modelTarget, $this->replace($modelStub, $replace));
            $generated[] = ['type' => 'Model', 'path' => $modelTarget, 'written' => true];

        } else {
            $livewireDir = $moduleDir
                ? "{$basePath}/{$this->paths['livewire']}/{$moduleDir}"
                : "{$basePath}/{$this->paths['livewire']}";

            File::ensureDirectoryExists($livewireDir);

            foreach (['Index', 'Create', 'Edit', 'Show'] as $action) {
                $target = "{$livewireDir}/{$action}{$modelName}.php";
                $stub = $this->loadStub("livewire/{$action}Component.stub");
                $this->writeFile($target, $this->replace($stub, $replace));
                $generated[] = ['type' => 'Livewire', 'path' => $target, 'written' => true];
            }

            $modelTarget = "{$basePath}/{$this->paths['model']}/{$modelName}.php";
            $modelStub = $this->loadStub('model.stub');
            $this->writeFile($modelTarget, $this->replace($modelStub, $replace));
            $generated[] = ['type' => 'Model', 'path' => $modelTarget, 'written' => true];
        }

        $viewsDir = $moduleDir
            ? "{$basePath}/{$this->paths['views']}/{$tableName}"
            : "{$basePath}/{$this->paths['views']}/{$tableName}";

        File::ensureDirectoryExists($viewsDir);

        $viewStubs = [
            'index'  => 'index-view.stub',
            'create' => 'create-view.stub',
            'edit'   => 'edit-view.stub',
            'show'   => 'show-view.stub',
            'print'  => 'print-view.stub',
        ];

        foreach ($viewStubs as $view => $stub) {
            $target = "{$viewsDir}/{$view}.blade.php";
            $stubPath = $this->locateStub($stub);

            if (File::exists($stubPath)) {
                $content = $this->replace(File::get($stubPath), $replace);
                $this->writeFile($target, $content);
            } else {
                $content = $this->replace($this->fallbackViewStub($view), $replace);
                $this->writeFile($target, $content);
            }

            $generated[] = ['type' => 'View', 'path' => $target, 'written' => true];
        }

        $routesAppended = $this->appendRoutes($tableName, $modelName, $moduleDir, $techStack);

        return [
            'files'           => $generated,
            'routes_appended' => $routesAppended,
        ];
    }

    protected function loadStub(string $name): string
    {
        $path = $this->locateStub($name);

        if (!File::exists($path)) {
            throw new \RuntimeException("Stub not found: {$path}");
        }

        return File::get($path);
    }

    protected function locateStub(string $name): string
    {
        return "{$this->stubsPath}/{$name}";
    }

    protected function replace(string $content, array $replace): string
    {
        return str_replace(array_keys($replace), array_values($replace), $content);
    }

    protected function writeFile(string $path, string $content): void
    {
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $content);
    }

    protected function resolveNamespace(string $techStack, string $moduleDir): string
    {
        $base = 'App\\';

        if ($techStack === 'ajax') {
            $path = $base . 'Http\\Controllers';
        } else {
            $path = $base . 'Livewire';
        }

        if ($moduleDir) {
            $path .= '\\' . $moduleDir;
        }

        return $path;
    }

    protected function buildFillable(array $columns): string
    {
        $names = array_map(fn ($c) => "'{$c['name']}'", $columns);
        return implode(",\n        ", $names);
    }

    protected function buildValidationRules(array $columnConfigs): string
    {
        $lines = [];

        foreach ($columnConfigs as $col => $config) {
            if (!empty($config['show_create'])) {
                $rules = $config['validation_rules'] ?? 'required|string';
                $lines[] = "            '{$col}' => '{$rules}',";
            }
        }

        return implode("\n", $lines);
    }

    protected function buildIndexFields(array $columns, array $columnConfigs): string
    {
        $lines = [];
        foreach ($columns as $col) {
            $name = $col['name'];
            if (!empty($columnConfigs[$name]['show_index'])) {
                $lines[] = "                        <td>{{ \${$this->camel($name)} }}</td>";
            }
        }
        return implode("\n", $lines);
    }

    protected function buildCreateFields(array $columns, array $columnConfigs): string
    {
        return $this->buildFormFields($columns, $columnConfigs, 'show_create');
    }

    protected function buildEditFields(array $columns, array $columnConfigs): string
    {
        $fields = $this->buildFormFields($columns, $columnConfigs, 'show_edit');
        return str_replace(
            ' wire:model.live="',
            ' wire:model.live="form.',
            $fields
        );
    }

    protected function buildPrintFields(array $columns, array $columnConfigs): string
    {
        $lines = [];
        foreach ($columns as $col) {
            $name = $col['name'];
            if (!empty($columnConfigs[$name]['show_print'])) {
                $label = Str::title(str_replace('_', ' ', $name));
                $lines[] = "            <tr>";
                $lines[] = "                <td class=\"font-medium text-gray-600\">{$label}</td>";
                $lines[] = "                <td>{{ \${$this->camel($name)} }}</td>";
                $lines[] = "            </tr>";
            }
        }
        return implode("\n", $lines);
    }

    protected function buildShowFields(array $columns, array $columnConfigs): string
    {
        $lines = [];
        foreach ($columns as $col) {
            $name = $col['name'];
            $label = Str::title(str_replace('_', ' ', $name));
            $lines[] = "            <tr>";
            $lines[] = "                <td class=\"font-medium text-gray-600\">{$label}</td>";
            $lines[] = "                <td>{{ \${$this->camel($name)} }}</td>";
            $lines[] = "            </tr>";
        }
        return implode("\n", $lines);
    }

    protected function buildFormFields(array $columns, array $columnConfigs, string $key): string
    {
        $lines = [];

        foreach ($columns as $col) {
            $name   = $col['name'];
            $config = $columnConfigs[$name] ?? [];

            if (empty($config[$key])) {
                continue;
            }

            $type  = $config['input_type'] ?? 'text';
            $label = Str::title(str_replace('_', ' ', $name));
            $model = "form.{$name}";

            $lines[] = "                    <div class=\"mb-4\">";
            $lines[] = "                        <label class=\"block text-sm font-medium text-gray-700 mb-1\">{$label}</label>";

            if ($type === 'textarea') {
                $lines[] = "                        <textarea wire:model.live=\"{$model}\" rows=\"3\" class=\"w-full rounded-lg border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500\"></textarea>";
            } elseif ($type === 'select') {
                $lines[] = "                        <select wire:model.live=\"{$model}\" class=\"w-full rounded-lg border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500\">";
                $lines[] = "                            <option value=\"\">Select {$label}</option>";
                $lines[] = "                        </select>";
            } elseif ($type === 'file') {
                $lines[] = "                        <input type=\"file\" wire:model.live=\"{$model}\" class=\"block w-full text-sm text-gray-500 file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-600 hover:file:bg-indigo-100\">";
            } else {
                $lines[] = "                        <input type=\"{$type}\" wire:model.live=\"{$model}\" class=\"w-full rounded-lg border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500\">";
            }

            $lines[] = "                        @error('{$model}') <p class=\"mt-1 text-xs text-red-600\">{{ \$message }}</p> @enderror";
            $lines[] = "                    </div>";
        }

        return implode("\n", $lines);
    }

    protected function appendRoutes(string $table, string $model, string $moduleDir, string $techStack): bool
    {
        $routeFile = base_path('routes/web.php');

        if (!File::exists($routeFile)) {
            return false;
        }

        $content = File::get($routeFile);
        $marker = '// MAGIC-GENERATOR: ' . $model;

        if (str_contains($content, $marker)) {
            return false;
        }

        $prefix = $moduleDir ? Str::kebab($moduleDir) : $table;
        $controller = $moduleDir
            ? "{$moduleDir}\\{$model}Controller"
            : "{$model}Controller";

        $routes = "\n\n// MAGIC-GENERATOR: {$model}\n";

        if ($techStack === 'ajax') {
            $namespace = $moduleDir
                ? "App\\Http\\Controllers\\{$moduleDir}"
                : "App\\Http\\Controllers";

            $routes .= "Route::prefix('{$prefix}')->name('{$prefix}.')->group(function () {\n";
            $routes .= "    Route::get('/', [{$namespace}\\{$model}Controller::class, 'index'])->name('index');\n";
            $routes .= "    Route::get('/create', [{$namespace}\\{$model}Controller::class, 'create'])->name('create');\n";
            $routes .= "    Route::post('/', [{$namespace}\\{$model}Controller::class, 'store'])->name('store');\n";
            $routes .= "    Route::get('/{id}', [{$namespace}\\{$model}Controller::class, 'show'])->name('show');\n";
            $routes .= "    Route::get('/{id}/edit', [{$namespace}\\{$model}Controller::class, 'edit'])->name('edit');\n";
            $routes .= "    Route::put('/{id}', [{$namespace}\\{$model}Controller::class, 'update'])->name('update');\n";
            $routes .= "    Route::delete('/{id}', [{$namespace}\\{$model}Controller::class, 'destroy'])->name('destroy');\n";
            $routes .= "});\n";
        } else {
            $namespace = $moduleDir
                ? "App\\Livewire\\{$moduleDir}"
                : "App\\Livewire";

            $routes .= "Route::prefix('{$prefix}')->name('{$prefix}.')->group(function () {\n";
            $routes .= "    Route::get('/', \\{$namespace}\\Index{$model}::class)->name('index');\n";
            $routes .= "    Route::get('/create', \\{$namespace}\\Create{$model}::class)->name('create');\n";
            $routes .= "    Route::get('/{id}/edit', \\{$namespace}\\Edit{$model}::class)->name('edit');\n";
            $routes .= "    Route::get('/{id}', \\{$namespace}\\Show{$model}::class)->name('show');\n";
            $routes .= "});\n";
        }

        File::append($routeFile, $routes);

        return true;
    }

    protected function fallbackViewStub(string $view): string
    {
        return match ($view) {
            'index' => '<x-app-layout><x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">{{ModelName}}</h2></x-slot><div class="py-12"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8"><div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6"><a href="{{ route(\'{{tableName}}.create\') }}" class="inline-block mb-4 px-4 py-2 bg-indigo-600 text-white rounded-lg">Create</a><table class="w-full"><thead><tr class="border-b">{{IndexFields}}</tr></thead><tbody>@foreach(${{modelName}}s as $item)<tr class="border-b">{{IndexFields}}</tr>@endforeach</tbody></table></div></div></div></x-app-layout>',
            'create' => '<x-app-layout><x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Create {{ModelName}}</h2></x-slot><div class="py-12"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8"><div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6"><form wire:submit="save">{{CreateFields}}<button type="submit" class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded-lg">Save</button></form></div></div></div></x-app-layout>',
            'edit'   => '<x-app-layout><x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Edit {{ModelName}}</h2></x-slot><div class="py-12"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8"><div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6"><form wire:submit="update">{{EditFields}}<button type="submit" class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded-lg">Update</button></form></div></div></div></x-app-layout>',
            'show'   => '<x-app-layout><x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">{{ModelName}}</h2></x-slot><div class="py-12"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8"><div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6"><table class="w-full">{{ShowFields}}</table></div></div></div></x-app-layout>',
            'print'  => '<!DOCTYPE html><html><head><meta charset="utf-8"><title>{{ModelName}} Print</title><style>body{font-family:system-ui,sans-serif;padding:2rem}table{width:100%;border-collapse:collapse}td{padding:.5rem;border-bottom:1px solid #e5e7eb}</style></head><body><h1>{{ModelName}}</h1><table>{{PrintFields}}</table></body></html>',
            default  => '<!-- {{ModelName}} {{ucfirst($view)}} -->',
        };
    }

    protected function camel(string $value): string
    {
        return lcfirst(Str::studly($value));
    }
}
