<?php

namespace YasKSalim\MagicGenerator\Engine;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GeneratorEngine
{
    protected array $paths = [];

    public function __construct()
    {
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
        $modelName = Str::studly(Str::singular($table));
        $moduleDir = $modularFolders ? $modelName : '';

        $files = [];
        $basePath = base_path();

        if ($modularFolders && $moduleDir) {
            $controllersPath = "{$basePath}/{$this->paths['controller']}/{$moduleDir}";
            $livewirePath    = "{$basePath}/{$this->paths['livewire']}/{$moduleDir}";
            $viewsPath       = "{$basePath}/{$this->paths['views']}/{$table}";
        } else {
            $controllersPath = "{$basePath}/{$this->paths['controller']}";
            $livewirePath    = "{$basePath}/{$this->paths['livewire']}";
            $viewsPath       = "{$basePath}/{$this->paths['views']}/{$table}";
        }

        if ($techStack === 'ajax') {
            File::ensureDirectoryExists($controllersPath);
            $files[] = ['type' => 'Controller', 'path' => "{$controllersPath}/{$modelName}Controller.php"];
        } else {
            File::ensureDirectoryExists($livewirePath);
            $files[] = ['type' => 'Livewire', 'path' => "{$livewirePath}/Index{$modelName}.php"];
            $files[] = ['type' => 'Livewire', 'path' => "{$livewirePath}/Create{$modelName}.php"];
            $files[] = ['type' => 'Livewire', 'path' => "{$livewirePath}/Edit{$modelName}.php"];
            $files[] = ['type' => 'Livewire', 'path' => "{$livewirePath}/Show{$modelName}.php"];
        }

        $viewFiles = ['index', 'create', 'edit', 'show', 'print'];
        File::ensureDirectoryExists($viewsPath);

        foreach ($viewFiles as $view) {
            $files[] = ['type' => 'View', 'path' => "{$viewsPath}/{$view}.blade.php"];
        }

        $modelPath = "{$basePath}/{$this->paths['model']}/{$modelName}.php";
        $files[] = ['type' => 'Model', 'path' => $modelPath];

        $files = array_map(fn ($f) => [
            'type'    => $f['type'],
            'path'    => $f['path'],
            'written' => false,
        ], $files);

        return [
            'files'            => $files,
            'routes_appended'  => false,
        ];
    }
}
