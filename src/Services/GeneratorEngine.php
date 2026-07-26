<?php

namespace YasKSalim\MagicGenerator\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GeneratorEngine
{
    protected string $stubsPath;

    protected array $paths;

    protected array $createdFiles = [];

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
        '{{CastsFields}}',
        '{{SoftDeletesImport}}',
        '{{SoftDeletesTrait}}',
        '{{Timestamps}}',
        '{{Relationships}}',
    ];

    public function __construct()
    {
        $this->stubsPath = config('magic-generator.stubs_path', __DIR__ . '/../../stubs');

        $this->paths = config('magic-generator.paths', [
            'controller'    => 'app/Http/Controllers',
            'model'         => 'app/Models',
            'livewire'      => 'app/Livewire',
            'views'         => 'resources/views',
            'form_request'  => 'app/Http/Requests',
            'policy'        => 'app/Policies',
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
        bool $softDeletes = false,
        bool $timestamps = true,
        bool $generateFormRequest = false,
        bool $generatePolicy = false,
        bool $generateApi = false,
        string $apiPrefix = 'api',
        bool $generateMenuItem = false,
        string $menuLayoutPath = 'resources/views/layouts/contentNavbarLayout.blade.php',
        string $menuLabel = '',
        string $menuIcon = '',
        string $menuRoutePrefix = '',
    ): array {
        $this->createdFiles = [];

        try {
            return $this->execute(
                $table, $techStack, $columns, $columnConfigs,
                $displayNameColumn, $successMessage, $deleteConfirmationMessage,
                $modularFolders, $softDeletes, $timestamps,
                $generateFormRequest, $generatePolicy, $generateApi, $apiPrefix,
                $generateMenuItem, $menuLayoutPath, $menuLabel, $menuIcon, $menuRoutePrefix
            );
        } catch (\Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }

    protected function execute(
        string $table,
        string $techStack,
        array $columns,
        array $columnConfigs,
        string $displayNameColumn,
        string $successMessage,
        string $deleteConfirmationMessage,
        bool $modularFolders,
        bool $softDeletes,
        bool $timestamps,
        bool $generateFormRequest,
        bool $generatePolicy,
        bool $generateApi,
        string $apiPrefix,
        bool $generateMenuItem,
        string $menuLayoutPath,
        string $menuLabel,
        string $menuIcon,
        string $menuRoutePrefix,
    ): array {
        $modelName  = Str::studly(Str::singular($table));
        $tableName  = $table;
        $moduleDir  = $modularFolders ? $modelName : '';
        $camelModel = lcfirst($modelName);
        $namespace  = $this->resolveNamespace($techStack, $moduleDir);

        $casts   = $this->buildCastsFields($columns, $columnConfigs);
        $fields  = [
            'fillable'   => $this->buildFillable($columns),
            'validation' => $this->buildValidationRules($columnConfigs),
            'index'      => $this->buildIndexFields($columns, $columnConfigs),
            'create'     => $this->buildCreateFields($columns, $columnConfigs),
            'edit'       => $this->buildEditFields($columns, $columnConfigs),
            'print'      => $this->buildPrintFields($columns, $columnConfigs),
            'show'       => $this->buildShowFields($columns, $columnConfigs),
            'casts'      => $casts,
        ];

        $softDeletesImport = $softDeletes ? "use Illuminate\\Database\\Eloquent\\SoftDeletes;\n" : '';
        $softDeletesTrait  = $softDeletes ? "    use SoftDeletes;\n\n" : '';
        $timestampsValue   = $timestamps ? 'true' : 'false';
        $relationships     = $this->buildRelationships($columns, $columnConfigs);

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
            '{{CastsFields}}'            => $fields['casts'],
            '{{SoftDeletesImport}}'      => $softDeletesImport,
            '{{SoftDeletesTrait}}'       => $softDeletesTrait,
            '{{Timestamps}}'             => $timestampsValue,
            '{{Relationships}}'          => $relationships,
        ];

        $basePath  = base_path();
        $generated = [];

        if ($techStack === 'ajax') {
            $controllersDir = $moduleDir
                ? "{$basePath}/{$this->paths['controller']}/{$moduleDir}"
                : "{$basePath}/{$this->paths['controller']}";

            File::ensureDirectoryExists($controllersDir);
            $target = "{$controllersDir}/{$modelName}Controller.php";
            $stub = $this->loadStub('controller.stub');

            if ($generateFormRequest) {
                $stub = str_replace(
                    'use Illuminate\Http\Request;',
                    "use App\\Http\\Requests\\{$modelName}Request;\nuse Illuminate\Http\Request;",
                    $stub
                );
                $stub = str_replace(
                    'Request $request',
                    "{$modelName}Request \$request",
                    $stub
                );
            }

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

        if ($generateFormRequest) {
            $reqDir = "{$basePath}/{$this->paths['form_request']}";
            File::ensureDirectoryExists($reqDir);
            $reqTarget = "{$reqDir}/{$modelName}Request.php";
            $reqStub = $this->loadStub('form-request.stub');
            $this->writeFile($reqTarget, $this->replace($reqStub, $replace));
            $generated[] = ['type' => 'FormRequest', 'path' => $reqTarget, 'written' => true];
        }

        if ($generatePolicy) {
            $polDir = "{$basePath}/{$this->paths['policy']}";
            File::ensureDirectoryExists($polDir);
            $polTarget = "{$polDir}/{$modelName}Policy.php";
            $polStub = $this->loadStub('policy.stub');
            $this->writeFile($polTarget, $this->replace($polStub, $replace));
            $generated[] = ['type' => 'Policy', 'path' => $polTarget, 'written' => true];
        }

        $routesAppended = $this->appendRoutes($tableName, $modelName, $moduleDir, $techStack);
        $apiRoutesAppended = false;

        if ($generateApi) {
            $apiRoutesAppended = $this->appendApiRoutes($tableName, $modelName, $moduleDir, $techStack, $apiPrefix);
        }

        $menuItemAppended = false;
        if ($generateMenuItem) {
            $menuItemAppended = $this->appendMenuItem(
                tableName: $tableName,
                modelName: $modelName,
                menuLabel: $menuLabel ?: str_replace('_', ' ', Str::title(Str::singular($tableName))),
                menuIcon: $menuIcon,
                routePrefix: $menuRoutePrefix ?: $tableName,
                layoutPath: $menuLayoutPath,
            );
        }

        return [
            'files'               => $generated,
            'routes_appended'     => $routesAppended,
            'api_routes_appended' => $apiRoutesAppended,
            'menu_item_appended'  => $menuItemAppended,
        ];
    }

    public function deleteGenerated(
        string $table,
        string $techStack = 'livewire-v3',
        bool $modularFolders = true,
        bool $generateApi = false,
        bool $generateMenuItem = false,
        string $menuLayoutPath = 'resources/views/layouts/contentNavbarLayout.blade.php',
        string $apiPrefix = 'api',
    ): array {
        $modelName = Str::studly(Str::singular($table));
        $moduleDir = $modularFolders ? $modelName : '';
        $basePath  = base_path();
        $deleted   = [];

        $filesToDelete = [];

        if ($techStack === 'ajax') {
            $controllersDir = $moduleDir
                ? "{$basePath}/{$this->paths['controller']}/{$moduleDir}"
                : "{$basePath}/{$this->paths['controller']}";
            $filesToDelete[] = "{$controllersDir}/{$modelName}Controller.php";
        } else {
            $livewireDir = $moduleDir
                ? "{$basePath}/{$this->paths['livewire']}/{$moduleDir}"
                : "{$basePath}/{$this->paths['livewire']}";
            foreach (['Index', 'Create', 'Edit', 'Show'] as $action) {
                $filesToDelete[] = "{$livewireDir}/{$action}{$modelName}.php";
            }
        }

        $filesToDelete[] = "{$basePath}/{$this->paths['model']}/{$modelName}.php";
        $filesToDelete[] = "{$basePath}/{$this->paths['form_request']}/{$modelName}Request.php";
        $filesToDelete[] = "{$basePath}/{$this->paths['policy']}/{$modelName}Policy.php";

        $viewsDir = "{$basePath}/{$this->paths['views']}/{$table}";
        foreach (['index', 'create', 'edit', 'show', 'print'] as $view) {
            $filesToDelete[] = "{$viewsDir}/{$view}.blade.php";
        }

        foreach ($filesToDelete as $file) {
            if (File::exists($file)) {
                File::delete($file);
                $deleted[] = ['type' => 'File', 'path' => $file, 'deleted' => true];
            }
        }

        $dirsToClean = [];

        if ($techStack === 'ajax' && $moduleDir) {
            $dirsToClean[] = "{$basePath}/{$this->paths['controller']}/{$moduleDir}";
        } elseif ($moduleDir) {
            $dirsToClean[] = "{$basePath}/{$this->paths['livewire']}/{$moduleDir}";
        }

        $dirsToClean[] = $viewsDir;

        foreach ($dirsToClean as $dir) {
            if (File::isDirectory($dir) && count(File::files($dir)) === 0 && count(File::directories($dir)) === 0) {
                File::deleteDirectory($dir);
                $deleted[] = ['type' => 'Directory', 'path' => $dir, 'deleted' => true];
            }
        }

        $routesRemoved = $this->removeRouteBlock(
            filePath: base_path('routes/web.php'),
            marker: 'MAGIC-GENERATOR: ' . $modelName,
        );

        $apiRoutesRemoved = false;
        if ($generateApi) {
            $apiRoutesRemoved = $this->removeRouteBlock(
                filePath: base_path('routes/api.php'),
                marker: 'MAGIC-GENERATOR-API: ' . $modelName,
            );
        }

        $menuItemRemoved = false;
        if ($generateMenuItem && !empty($menuLayoutPath)) {
            $menuItemRemoved = $this->removeMenuItemBlock(
                layoutPath: $menuLayoutPath,
                modelName: $modelName,
            );
        }

        return [
            'deleted'            => $deleted,
            'routes_removed'     => $routesRemoved,
            'api_routes_removed' => $apiRoutesRemoved,
            'menu_item_removed'  => $menuItemRemoved,
        ];
    }

    protected function removeRouteBlock(string $filePath, string $marker): bool
    {
        if (!File::exists($filePath)) {
            return false;
        }

        $content = File::get($filePath);
        $search = '// ' . $marker;

        if (!str_contains($content, $search)) {
            return false;
        }

        $quoted = preg_quote($search, '/');
        $pattern = '/\n{1,2}' . $quoted . '\n.*?\n\}\);\n?/s';
        $cleaned = preg_replace($pattern, "\n", $content);

        if ($cleaned !== null && $cleaned !== $content) {
            File::put($filePath, $cleaned);
            return true;
        }

        return false;
    }

    protected function removeMenuItemBlock(string $layoutPath, string $modelName): bool
    {
        $filePath = base_path($layoutPath);

        if (!File::exists($filePath)) {
            return false;
        }

        $content = File::get($filePath);
        $search = '<!-- MAGIC-GENERATOR: ' . $modelName . ' -->';

        if (!str_contains($content, $search)) {
            return false;
        }

        $quoted = preg_quote($search, '/');
        $pattern = '/\n\s*' . $quoted . '\n.*?<\/li>\n?/s';
        $cleaned = preg_replace($pattern, "\n", $content);

        if ($cleaned !== null && $cleaned !== $content) {
            File::put($filePath, $cleaned);
            return true;
        }

        return false;
    }

    protected function rollback(): void
    {
        foreach (array_reverse($this->createdFiles) as $file) {
            if (File::exists($file)) {
                File::delete($file);
            }
        }
        $this->createdFiles = [];
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
        $this->createdFiles[] = $path;
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

    protected function buildCastsFields(array $columns, array $columnConfigs): string
    {
        $lines = [];

        foreach ($columns as $col) {
            $name = $col['name'];
            $type = $col['type'];

            $cast = match ($type) {
                'integer', 'bigint', 'smallint', 'tinyint' => 'integer',
                'float', 'double', 'decimal'               => 'float',
                'boolean'                                  => 'boolean',
                'date'                                     => 'date',
                'datetime', 'timestamp'                    => 'datetime',
                'json', 'array'                            => 'array',
                default                                    => null,
            };

            if ($cast !== null) {
                $lines[] = "        '{$name}' => '{$cast}',";
            }
        }

        return implode("\n", $lines);
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
                $label = $columnConfigs[$name]['label'] ?: Str::title(str_replace('_', ' ', $name));
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
            $label = $columnConfigs[$name]['label'] ?: Str::title(str_replace('_', ' ', $name));
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
            $label = $config['label'] ?: Str::title(str_replace('_', ' ', $name));
            $model = $key === 'show_edit' ? "form.{$name}" : $name;

            $lines[] = "                    <div class=\"mb-4\">";
            $lines[] = "                        <label class=\"block text-sm font-medium text-gray-700 mb-1\">{$label}</label>";

            if ($type === 'textarea') {
                $lines[] = "                        <textarea wire:model.live=\"{$model}\" rows=\"3\" class=\"w-full rounded-lg border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500\"></textarea>";
            } elseif ($type === 'select') {
                $optionsHtml = $this->buildSelectOptions($name, $config);
                $searchable = !empty($config['searchable_select']);
                $tsClass = $searchable ? ' ts-select' : '';
                $xInit = $searchable
                    ? ' x-init="$nextTick(() => { if(window.TomSelect && $el.tomselect === undefined) { try { new TomSelect($el); } catch(e) {} } })"'
                    : '';
                $lines[] = "                        <select wire:model.live=\"{$model}\"{$xInit} class=\"w-full rounded-lg border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500{$tsClass}\">";
                $lines[] = "                            <option value=\"\">Select {$label}</option>";
                foreach ($optionsHtml as $opt) {
                    $lines[] = $opt;
                }
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

    protected function buildSelectOptions(string $colName, array $config): array
    {
        $lines = [];

        $staticOptions = trim($config['select_options'] ?? '');
        if (!empty($staticOptions)) {
            foreach (explode("\n", $staticOptions) as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                $parts = explode('|', $line, 2);
                $val = trim($parts[0]);
                $display = trim($parts[1] ?? $val);
                $lines[] = "                            <option value=\"{$val}\">{$display}</option>";
            }
        }

        if (!empty($config['select_from_table']) && !empty($config['select_table'])) {
            $valueCol = $config['select_value_column'] ?? 'id';
            $displayCol = $config['select_display_column'] ?? $valueCol;
            $table = $config['select_table'];
            $lines[] = "                            @foreach(\\App\\Models\\{$this->camel(Str::studly(Str::singular($table)))}::all() as \$option)";
            $lines[] = "                                <option value=\"{{ \$option->{$valueCol} }}\">{{ \$option->{$displayCol} }}</option>";
            $lines[] = "                            @endforeach";
        }

        return $lines;
    }

    protected function buildRelationships(array $columns, array $columnConfigs): string
    {
        $lines = [];
        $seen = [];

        foreach ($columns as $col) {
            $name = $col['name'];
            $config = $columnConfigs[$name] ?? [];

            if (!Str::endsWith($name, '_id')) {
                continue;
            }

            $relatedTable = $config['foreign_table'] ?? '';
            $relatedColumn = $config['foreign_column'] ?? 'id';

            if (empty($relatedTable)) {
                $relatedTable = Str::plural(Str::snake(substr($name, 0, -3)));
            }

            $relatedModel = Str::studly(Str::singular($relatedTable));
            $relName = lcfirst($relatedModel);

            if (isset($seen[$relName])) {
                $relName = $relName . '_' . $name;
            }
            $seen[$relName] = true;

            $lines[] = '';
            $lines[] = "    public function {$relName}()";
            $lines[] = "    {";
            $lines[] = "        return \$this->belongsTo(\\App\\Models\\{$relatedModel}::class, '{$name}', '{$relatedColumn}');";
            $lines[] = "    }";
        }

        if (!empty($lines)) {
            array_unshift($lines, '');
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

    protected function appendApiRoutes(string $table, string $model, string $moduleDir, string $techStack, string $apiPrefix): bool
    {
        $routeFile = base_path('routes/api.php');

        if (!File::exists($routeFile)) {
            return false;
        }

        $content = File::get($routeFile);
        $marker = '// MAGIC-GENERATOR-API: ' . $model;

        if (str_contains($content, $marker)) {
            return false;
        }

        $prefix = $moduleDir ? Str::kebab($moduleDir) : $table;
        $namespace = $moduleDir
            ? "App\\Http\\Controllers\\{$moduleDir}"
            : "App\\Http\\Controllers";

        $uses = $moduleDir
            ? "use App\\Http\\Controllers\\{$moduleDir}\\{$model}Controller;"
            : "use App\\Http\\Controllers\\{$model}Controller;";

        $routes = "\n\n// MAGIC-GENERATOR-API: {$model}\n";
        $routes .= "{$uses}\n";
        $routes .= "Route::prefix('{$apiPrefix}/{$prefix}')->name('{$prefix}.')->group(function () {\n";
        $routes .= "    Route::get('/', [{$model}Controller::class, 'index'])->name('index');\n";
        $routes .= "    Route::post('/', [{$model}Controller::class, 'store'])->name('store');\n";
        $routes .= "    Route::get('/{id}', [{$model}Controller::class, 'show'])->name('show');\n";
        $routes .= "    Route::put('/{id}', [{$model}Controller::class, 'update'])->name('update');\n";
        $routes .= "    Route::delete('/{id}', [{$model}Controller::class, 'destroy'])->name('destroy');\n";
        $routes .= "});\n";

        File::append($routeFile, $routes);

        return true;
    }

    protected function appendMenuItem(
        string $tableName,
        string $modelName,
        string $menuLabel,
        string $menuIcon,
        string $routePrefix,
        string $layoutPath,
    ): bool {
        $filePath = base_path($layoutPath);

        if (!File::exists($filePath)) {
            return false;
        }

        $content = File::get($filePath);
        $marker = '<!-- MAGIC-GENERATOR: ' . $modelName . ' -->';

        if (str_contains($content, $marker)) {
            return false;
        }

        $kebabPrefix = Str::kebab($routePrefix);
        $iconHtml = $menuIcon
            ? "                            <i class=\"{$menuIcon}\"></i>\n"
            : '';

        $menuItem = "\n                    <!-- MAGIC-GENERATOR: {$modelName} -->\n";
        $menuItem .= "                    <li class=\"menu-item {{ request()->routeIs('{$kebabPrefix}.*') ? 'active' : '' }}\">\n";
        $menuItem .= "                        <a href=\"{{ route('{$kebabPrefix}.index') }}\" class=\"menu-link\">\n";
        $menuItem .= "{$iconHtml}                            <div class=\"fw-normal\">{$menuLabel}</div>\n";
        $menuItem .= "                        </a>\n";
        $menuItem .= "                    </li>\n";

        $markerTarget = '@stack(\'scripts\')';

        if (str_contains($content, $markerTarget)) {
            $content = str_replace($markerTarget, $menuItem . "\n    " . $markerTarget, $content);
        } else {
            $bodyEnd = strrpos($content, '</body>');
            if ($bodyEnd !== false) {
                $content = substr_replace($content, $menuItem . "\n", $bodyEnd, 0);
            } else {
                $content .= $menuItem;
            }
        }

        File::put($filePath, $content);

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
