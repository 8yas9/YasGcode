<?php

namespace YasKSalim\MagicGenerator\Http\Livewire;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

    protected array $skipColumns = ['id', 'created_at', 'updated_at', 'deleted_at'];

    protected array $hiddenColumns = ['password', 'remember_token', 'api_token'];

    public function mount(): void
    {
        $this->loadTables();
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
            'show_index'       => $showIndex,
            'show_create'      => $showCreate,
            'show_edit'        => $showEdit,
            'show_print'       => $showIndex,
            'input_type'       => $this->defaultInputType($type),
            'validation_rules' => $this->defaultValidationRule($type, $nullable, $column),
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

    public function generate(): void
    {
        $this->generatedFiles = [];

        if (empty($this->selectedTable)) {
            $this->dispatch('g-status', type: 'error', message: 'Please select a table first.');
            return;
        }

        $this->generating = true;

        try {
            $engine = app(GeneratorEngine::class);

            $result = $engine->run(
                table: $this->selectedTable,
                techStack: $this->techStack,
                columns: $this->columns,
                columnConfigs: $this->columnConfigs,
                displayNameColumn: $this->displayNameColumn,
                successMessage: $this->successMessage,
                deleteConfirmationMessage: $this->deleteConfirmationMessage,
                modularFolders: $this->modularFolders,
            );

            $this->generatedFiles = $result['files'];

            $count = count(array_filter($result['files'], fn ($f) => $f['written']));
            $this->dispatch('g-status', type: 'success', message: "{$count} files generated successfully.");

            if ($result['routes_appended']) {
                $this->dispatch('g-status', type: 'info', message: 'Module routes appended to routes/web.php.');
            }
        } catch (\Throwable $e) {
            $this->dispatch('g-status', type: 'error', message: $e->getMessage());
        } finally {
            $this->generating = false;
        }
    }

    public function render()
    {
        return view('magic-generator::livewire.generator-dashboard');
    }
}
