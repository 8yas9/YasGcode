<?php

namespace YasKSalim\MagicGenerator\Http\Livewire;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Component;
use YasKSalim\MagicGenerator\Services\GeneratorEngine;

class GeneratorDashboard extends Component
{
    public string $techStack = 'livewire-v3';

    public array $tables = [];

    public string $selectedTable = '';

    public array $columns = [];

    public array $columnConfigs = [];

    public string $successMessage = 'Record created successfully.';

    public string $deleteConfirmationMessage = 'Are you sure you want to delete this record?';

    public string $displayNameColumn = '';

    public bool $modularFolders = true;

    public bool $selectAllIndex = false;
    public bool $selectAllCreate = false;
    public bool $selectAllEdit = false;
    public bool $selectAllPrint = false;

    public bool $generating = false;

    public array $generatedFiles = [];

    public array $deletedFiles = [];

    public array $relatedTableColumns = [];

    public string $searchQuery = '';

    public bool $softDeletes = false;
    public bool $timestamps = true;
    public bool $generateFormRequest = false;
    public bool $generatePolicy = false;
    public bool $generateApi = false;
    public string $apiPrefix = 'api';

    public bool $generateMenuItem = false;
    public string $menuLayoutPath = 'resources/views/layouts/contentNavbarLayout.blade.php';
    public string $menuLabel = '';
    public string $menuIcon = '';
    public string $menuRoutePrefix = '';

    public int $progressCurrent = 0;
    public int $progressTotal = 0;
    public string $progressMessage = '';

    protected array $skipColumns = ['id', 'created_at', 'updated_at', 'deleted_at'];

    protected array $hiddenColumns = ['password', 'remember_token', 'api_token'];

    protected $listeners = ['importConfig'];

    public function mount(): void
    {
        $this->loadTables();
    }

    public function getFilteredTablesProperty(): array
    {
        if (empty($this->searchQuery)) {
            return $this->tables;
        }

        return array_values(array_filter(
            $this->tables,
            fn ($t) => stripos($t, $this->searchQuery) !== false
        ));
    }

    public function loadTables(): void
    {
        if ($this->hasDoctrine()) {
            $schema = Schema::getConnection()->getDoctrineSchemaManager();
            $this->tables = $schema->listTableNames();
        } elseif (method_exists(Schema::class, 'getTableNames')) {
            $result = Schema::getTableNames();
            $this->tables = is_array($result) ? $result : (method_exists($result, 'toArray') ? $result->toArray() : (array) $result);
        } else {
            $rows = DB::select('SHOW TABLES');
            $key = 'Tables_in_' . DB::getDatabaseName();
            $this->tables = array_map(fn ($t) => $t->$key ?? '', $rows);
            $this->tables = array_values(array_filter($this->tables));
        }

        $this->tables = array_values($this->tables);
    }

    public function updatedSelectedTable(): void
    {
        $this->loadColumns();
        $this->generatedFiles = [];
        if ($this->selectedTable) {
            $this->menuLabel = str_replace('_', ' ', Str::title(Str::singular($this->selectedTable)));
            $this->menuRoutePrefix = $this->selectedTable;
        }
    }

    public function loadColumns(): void
    {
        $this->columns = [];
        $this->columnConfigs = [];

        if (empty($this->selectedTable)) {
            return;
        }

        $table = $this->selectedTable;

        if ($this->hasDoctrine()) {
            $this->loadColumnsWithDoctrine($table);
        } else {
            $this->loadColumnsSimple($table);
        }

        if (count($this->columns) > 0) {
            $this->displayNameColumn = $this->columns[0]['name'];
        }
    }

    protected function loadColumnsWithDoctrine(string $table): void
    {
        $schema = Schema::getConnection()->getDoctrineSchemaManager();
        $doctrineColumns = $schema->listTableColumns($table);

        foreach ($doctrineColumns as $colName => $col) {
            if (in_array($colName, $this->skipColumns, true)) {
                continue;
            }

            if ($col->getAutoincrement()) {
                continue;
            }

            $typeName = $col->getType()->getName();

            $this->columns[] = [
                'name'     => $colName,
                'type'     => $typeName,
                'nullable' => !$col->getNotnull(),
                'length'   => $col->getLength(),
            ];

            $this->columnConfigs[$colName] = $this->defaultConfig($colName, $typeName, !$col->getNotnull());
        }
    }

    protected function loadColumnsSimple(string $table): void
    {
        $columns = Schema::getColumnListing($table);
        $types = $this->getColumnTypesFromSchema($table);

        foreach ($columns as $col) {
            if (in_array($col, $this->skipColumns, true)) {
                continue;
            }

            $typeInfo = $types[$col] ?? ['type' => 'string', 'nullable' => false];
            $typeName = $typeInfo['type'];
            $nullable = $typeInfo['nullable'];

            $this->columns[] = [
                'name'     => $col,
                'type'     => $typeName,
                'nullable' => $nullable,
                'length'   => null,
            ];

            $this->columnConfigs[$col] = $this->defaultConfig($col, $typeName, $nullable);
        }
    }

    protected function getColumnTypesFromSchema(string $table): array
    {
        $types = [];
        $results = Schema::getConnection()
            ->select('SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . '`');

        foreach ($results as $row) {
            $row = (array) $row;
            $typeRaw = $row['Type'] ?? $row['type'] ?? '';
            $nullRaw = $row['Null'] ?? $row['null'] ?? 'YES';

            $typeName = 'string';
            if (preg_match('/^(int|bigint|smallint|tinyint)/i', $typeRaw)) {
                $typeName = 'integer';
            } elseif (preg_match('/^(decimal|float|double)/i', $typeRaw)) {
                $typeName = 'float';
            } elseif (preg_match('/^text|longtext|mediumtext/i', $typeRaw)) {
                $typeName = 'text';
            } elseif (preg_match('/^date|datetime|timestamp/i', $typeRaw)) {
                $typeName = 'date';
            } elseif (preg_match('/^tinyint\(1\)/i', $typeRaw)) {
                $typeName = 'boolean';
            }

            $field = $row['Field'] ?? $row['field'] ?? '';
            $types[$field] = [
                'type'     => $typeName,
                'nullable' => strtoupper($nullRaw) === 'YES',
            ];
        }

        return $types;
    }

    protected function hasDoctrine(): bool
    {
        return class_exists(\Doctrine\DBAL\DriverManager::class);
    }

    protected function defaultConfig(string $column, string $type, bool $nullable): array
    {
        [$showIndex, $showCreate, $showEdit] = $this->defaultVisibility($column);

        return [
            'show_index'            => $showIndex,
            'show_create'           => $showCreate,
            'show_edit'             => $showEdit,
            'show_print'            => $showIndex,
            'input_type'            => $this->defaultInputType($type),
            'validation_rules'      => $this->defaultValidationRule($type, $nullable, $column),
            'label'                 => '',
            'select_options'        => '',
            'select_from_table'     => false,
            'select_table'          => '',
            'select_value_column'   => 'id',
            'select_display_column' => '',
            'searchable_select'     => false,
            'foreign_table'         => '',
            'foreign_column'        => '',
        ];
    }

    protected function defaultVisibility(string $column): array
    {
        if (in_array($column, $this->hiddenColumns, true)) {
            return [false, false, false];
        }

        if (in_array($column, ['created_at', 'updated_at'], true)) {
            return [true, false, false];
        }

        return [true, true, true];
    }

    protected function defaultInputType(string $type): string
    {
        return match ($type) {
            'integer', 'bigint', 'smallint', 'tinyint' => 'number',
            'float', 'double', 'decimal'                => 'number',
            'boolean'                                   => 'select',
            'date', 'datetime', 'timestamp'             => 'date',
            'text', 'mediumtext', 'longtext'            => 'textarea',
            default                                     => 'text',
        };
    }

    protected function defaultValidationRule(string $type, bool $nullable, string $column): string
    {
        $rules = $nullable ? ['nullable'] : ['required'];

        $rules[] = match ($type) {
            'integer', 'bigint', 'smallint', 'tinyint' => 'integer',
            'float', 'double', 'decimal'               => 'numeric',
            'boolean'                                  => 'boolean',
            'date', 'datetime', 'timestamp'            => 'date',
            'text', 'mediumtext', 'longtext'           => 'string',
            'string'                                   => 'string|max:255',
            default                                    => 'string',
        };

        return implode('|', $rules);
    }

    public function loadRelatedTableColumns(string $colName): void
    {
        $table = $this->columnConfigs[$colName]['select_table'] ?? '';

        if (empty($table)) {
            return;
        }

        $this->relatedTableColumns[$table] = Schema::getColumnListing($table);
    }

    public function updatedColumnConfigs($value, $key): void
    {
        $parts = explode('.', $key);
        $colName = $parts[0];
        $field = $parts[1] ?? '';

        if ($field === 'select_table' && !empty($value)) {
            $this->loadRelatedTableColumns($colName);
        }

        if ($field === 'input_type') {
            if ($value !== 'select') {
                $this->columnConfigs[$colName]['select_options'] = '';
                $this->columnConfigs[$colName]['select_from_table'] = false;
                $this->columnConfigs[$colName]['select_table'] = '';
            }
        }
    }

    public function updatedSelectAllIndex(): void
    {
        foreach (array_keys($this->columnConfigs) as $col) {
            $this->columnConfigs[$col]['show_index'] = $this->selectAllIndex;
        }
    }

    public function updatedSelectAllCreate(): void
    {
        foreach (array_keys($this->columnConfigs) as $col) {
            $this->columnConfigs[$col]['show_create'] = $this->selectAllCreate;
        }
    }

    public function updatedSelectAllEdit(): void
    {
        foreach (array_keys($this->columnConfigs) as $col) {
            $this->columnConfigs[$col]['show_edit'] = $this->selectAllEdit;
        }
    }

    public function updatedSelectAllPrint(): void
    {
        foreach (array_keys($this->columnConfigs) as $col) {
            $this->columnConfigs[$col]['show_print'] = $this->selectAllPrint;
        }
    }

    public function autoFillValidation(): void
    {
        foreach ($this->columns as $col) {
            $this->columnConfigs[$col['name']]['validation_rules'] = $this->defaultValidationRule(
                $col['type'],
                $col['nullable'],
                $col['name'],
            );
        }
    }

    public function getConfigJson(): string
    {
        return json_encode([
            'techStack'               => $this->techStack,
            'selectedTable'           => $this->selectedTable,
            'successMessage'          => $this->successMessage,
            'deleteConfirmationMessage' => $this->deleteConfirmationMessage,
            'displayNameColumn'       => $this->displayNameColumn,
            'modularFolders'          => $this->modularFolders,
            'columnConfigs'           => $this->columnConfigs,
        ], JSON_PRETTY_PRINT);
    }

    public function exportConfig(): void
    {
        $this->dispatch('g-status', type: 'info', message: 'Config copied to clipboard. Paste into Import to restore.');
        $this->dispatch('copy-to-clipboard', content: $this->getConfigJson());
    }

    public function importConfig($json): void
    {
        $data = json_decode($json, true);

        if (!$data || !isset($data['columnConfigs'])) {
            $this->dispatch('g-status', type: 'error', message: 'Invalid config JSON.');
            return;
        }

        if (isset($data['techStack'])) $this->techStack = $data['techStack'];
        if (isset($data['selectedTable'])) $this->selectedTable = $data['selectedTable'];
        if (isset($data['successMessage'])) $this->successMessage = $data['successMessage'];
        if (isset($data['deleteConfirmationMessage'])) $this->deleteConfirmationMessage = $data['deleteConfirmationMessage'];
        if (isset($data['displayNameColumn'])) $this->displayNameColumn = $data['displayNameColumn'];
        if (isset($data['modularFolders'])) $this->modularFolders = $data['modularFolders'];

        if ($this->selectedTable) {
            $this->loadColumns();
        }

        foreach ($data['columnConfigs'] as $col => $config) {
            if (isset($this->columnConfigs[$col])) {
                $this->columnConfigs[$col] = array_merge($this->columnConfigs[$col], $config);
            }
        }

        $this->dispatch('g-status', type: 'success', message: 'Config imported successfully.');
    }

    public function generate(): void
    {
        $this->generatedFiles = [];

        if (empty($this->selectedTable)) {
            $this->dispatch('g-status', type: 'error', message: 'Please select a table first.');
            return;
        }

        $this->generating = true;
        $this->progressCurrent = 0;
        $this->progressTotal = 0;
        $this->progressMessage = '';

        try {
            $engine = app(GeneratorEngine::class);

            $totalFiles = count($this->columns) + 6;
            $this->progressTotal = $totalFiles;
            $this->progressMessage = 'Starting generation...';
            $this->dispatch('g-progress', current: 0, total: $totalFiles, message: 'Starting...');

            $result = $engine->run(
                table: $this->selectedTable,
                techStack: $this->techStack,
                columns: $this->columns,
                columnConfigs: $this->columnConfigs,
                displayNameColumn: $this->displayNameColumn,
                successMessage: $this->successMessage,
                deleteConfirmationMessage: $this->deleteConfirmationMessage,
                modularFolders: $this->modularFolders,
                softDeletes: $this->softDeletes,
                timestamps: $this->timestamps,
                generateFormRequest: $this->generateFormRequest,
                generatePolicy: $this->generatePolicy,
                generateApi: $this->generateApi,
                apiPrefix: $this->apiPrefix,
                generateMenuItem: $this->generateMenuItem,
                menuLayoutPath: $this->menuLayoutPath,
                menuLabel: $this->menuLabel,
                menuIcon: $this->menuIcon,
                menuRoutePrefix: $this->menuRoutePrefix,
            );

            $this->generatedFiles = $result['files'];
            $this->progressCurrent = count($result['files']);
            $this->progressMessage = 'Complete!';

            $count = count(array_filter($result['files'], fn ($f) => $f['written']));
            $this->dispatch('g-status', type: 'success', message: "{$count} files generated successfully.");

            if ($result['routes_appended']) {
                $this->dispatch('g-status', type: 'info', message: 'Module routes appended to routes/web.php.');
            }
            if (!empty($result['api_routes_appended'])) {
                $this->dispatch('g-status', type: 'info', message: 'API routes appended to routes/api.php.');
            }
            if (!empty($result['menu_item_appended'])) {
                $this->dispatch('g-status', type: 'info', message: 'Menu item appended to layout.');
            }
        } catch (\Throwable $e) {
            $this->dispatch('g-status', type: 'error', message: 'Generation failed: ' . $e->getMessage());
        } finally {
            $this->generating = false;
        }
    }

    public function deleteGenerated(): void
    {
        if (empty($this->selectedTable)) {
            $this->dispatch('g-status', type: 'error', message: 'Please select a table first.');
            return;
        }

        $engine = app(GeneratorEngine::class);

        $result = $engine->deleteGenerated(
            table: $this->selectedTable,
            techStack: $this->techStack,
            modularFolders: $this->modularFolders,
            generateApi: $this->generateApi,
            generateMenuItem: $this->generateMenuItem,
            menuLayoutPath: $this->menuLayoutPath,
            apiPrefix: $this->apiPrefix,
        );

        $this->generatedFiles = [];
        $this->deletedFiles = $result['deleted'];
        $count = count($result['deleted']);

        $message = "{$count} items deleted.";
        $this->dispatch('g-status', type: 'success', message: $message);

        if ($result['routes_removed']) {
            $this->dispatch('g-status', type: 'info', message: 'Routes removed from routes/web.php.');
        }
        if ($result['api_routes_removed']) {
            $this->dispatch('g-status', type: 'info', message: 'API routes removed from routes/api.php.');
        }
        if ($result['menu_item_removed']) {
            $this->dispatch('g-status', type: 'info', message: 'Menu item removed from layout.');
        }
    }

    public function render()
    {
        return view('magic-generator::livewire.generator-dashboard');
    }
}
