<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class CrudConfigurationMatrix extends Component
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

    public function mount(): void
    {
        $this->loadTables();
    }

    public function loadTables(): void
    {
        $this->tables = Schema::getTableNames()
            ?: Schema::getAllTables();
    }

    public function updatedSelectedTable(): void
    {
        $this->loadColumns();
    }

    public function loadColumns(): void
    {
        $this->columns = [];
        $this->columnConfigs = [];

        if (empty($this->selectedTable)) {
            return;
        }

        $tableName = $this->selectedTable;

        if (class_exists(\Doctrine\DBAL\DriverManager::class)) {
            $columns = Schema::getColumnListing($tableName);
            $builder = Schema::getConnection()->getDoctrineSchemaManager();
            $doctrineColumns = $builder->listTableColumns($tableName);

            foreach ($columns as $col) {
                if (!isset($doctrineColumns[$col])) {
                    continue;
                }

                $doctrine = $doctrineColumns[$col];
                $typeName = $doctrine->getType()->getName();
                $nullable = !$doctrine->getNotnull();
                $length = $doctrine->getLength();
                $isAutoincrement = $doctrine->getAutoincrement();

                if ($isAutoincrement) {
                    continue;
                }

                $this->columns[] = [
                    'name' => $col,
                    'type' => $typeName,
                    'nullable' => $nullable,
                    'length' => $length,
                ];

                $this->columnConfigs[$col] = $this->defaultConfig($col, $typeName, $nullable);
            }
        } else {
            $columns = Schema::getColumnListing($tableName);
            $columnTypes = $this->getColumnTypesSimple($tableName);

            foreach ($columns as $col) {
                $typeInfo = $columnTypes[$col] ?? ['type' => 'string', 'nullable' => false];
                $typeName = $typeInfo['type'];
                $nullable = $typeInfo['nullable'];

                if (in_array($col, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                    continue;
                }

                $this->columns[] = [
                    'name' => $col,
                    'type' => $typeName,
                    'nullable' => $nullable,
                    'length' => null,
                ];

                $this->columnConfigs[$col] = $this->defaultConfig($col, $typeName, $nullable);
            }
        }

        if (count($this->columns) > 0) {
            $this->displayNameColumn = $this->columns[0]['name'];
        }
    }

    protected function getColumnTypesSimple(string $table): array
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
                'type' => $typeName,
                'nullable' => strtoupper($nullRaw) === 'YES',
            ];
        }

        return $types;
    }

    protected function defaultConfig(string $column, string $type, bool $nullable): array
    {
        [$showIndex, $showCreate, $showEdit] = $this->defaultVisibility($column, $type);

        return [
            'show_index' => $showIndex,
            'show_create' => $showCreate,
            'show_edit' => $showEdit,
            'input_type' => $this->defaultInputType($type),
            'validation_rules' => $this->defaultValidationRule($type, $nullable, $column),
        ];
    }

    protected function defaultVisibility(string $column, string $type): array
    {
        $hiddenCols = ['password', 'remember_token', 'api_token'];
        $alwaysHide = in_array($column, $hiddenCols);

        if ($alwaysHide) {
            return [false, false, false];
        }

        if (in_array($column, ['created_at', 'updated_at'])) {
            return [true, false, false];
        }

        return [true, true, true];
    }

    protected function defaultInputType(string $type): string
    {
        return match ($type) {
            'integer', 'bigint', 'smallint', 'tinyint' => 'number',
            'float', 'double', 'decimal' => 'number',
            'boolean' => 'select',
            'date', 'datetime', 'timestamp' => 'date',
            'text', 'mediumtext', 'longtext' => 'text',
            default => 'text',
        };
    }

    protected function defaultValidationRule(string $type, bool $nullable, string $column): string
    {
        $rules = [];

        if (!$nullable) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        $rules[] = match ($type) {
            'integer', 'bigint', 'smallint', 'tinyint' => 'integer',
            'float', 'double', 'decimal' => 'numeric',
            'boolean' => 'boolean',
            'date', 'datetime', 'timestamp' => 'date',
            'text', 'mediumtext', 'longtext' => 'string',
            'string' => 'string|max:255',
            default => 'string',
        };

        return implode('|', $rules);
    }

    public function updatedSelectAllIndex(): void
    {
        foreach ($this->columnConfigs as $col => &$config) {
            $config['show_index'] = $this->selectAllIndex;
        }
    }

    public function updatedSelectAllCreate(): void
    {
        foreach ($this->columnConfigs as $col => &$config) {
            $config['show_create'] = $this->selectAllCreate;
        }
    }

    public function updatedSelectAllEdit(): void
    {
        foreach ($this->columnConfigs as $col => &$config) {
            $config['show_edit'] = $this->selectAllEdit;
        }
    }

    public function autoFillValidation(): void
    {
        foreach ($this->columns as $column) {
            $col = $column['name'];
            $this->columnConfigs[$col]['validation_rules'] = $this->defaultValidationRule(
                $column['type'],
                $column['nullable'],
                $col
            );
        }
    }

    public function getTableNameModelAttribute(): string
    {
        return str_replace('_', '', \Illuminate\Support\Str::title($this->selectedTable));
    }

    public function getTableNameSingularAttribute(): string
    {
        return \Illuminate\Support\Str::singular($this->selectedTable);
    }

    public function getTableNamePluralAttribute(): string
    {
        return $this->selectedTable;
    }

    public function render()
    {
        return view('livewire.crud-configuration-matrix');
    }
}
