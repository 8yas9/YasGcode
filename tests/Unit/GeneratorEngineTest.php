<?php

namespace YasKSalim\MagicGenerator\Tests\Unit;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\Foundation\Application;
use YasKSalim\MagicGenerator\Services\GeneratorEngine;

class GeneratorEngineTest extends \Orchestra\Testbench\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('magic-generator', [
            'stubs_path' => __DIR__ . '/../../stubs',
            'paths' => [
                'controller'    => 'app/Http/Controllers',
                'model'         => 'app/Models',
                'livewire'      => 'app/Livewire',
                'views'         => 'resources/views',
                'form_request'  => 'app/Http/Requests',
                'policy'        => 'app/Policies',
            ],
            'defaults' => [
                'success_message'             => 'Record created successfully.',
                'delete_confirmation_message' => 'Are you sure?',
            ],
            'routes' => [
                'prefix'    => 'magic-generator',
                'middleware' => ['web'],
            ],
        ]);
    }

    public function test_engine_can_instantiate(): void
    {
        $engine = new GeneratorEngine();
        $this->assertInstanceOf(GeneratorEngine::class, $engine);
    }

    public function test_default_paths_are_set(): void
    {
        $engine = new GeneratorEngine();
        $this->assertStringContainsString('app/Http/Controllers', $engine->getPaths()['controller'] ?? '');
    }

    public function test_run_returns_files_array(): void
    {
        $engine = new GeneratorEngine();
        $result = $engine->run(
            table: 'users',
            techStack: 'ajax',
            columns: [
                ['name' => 'id', 'type' => 'integer', 'nullable' => false],
                ['name' => 'name', 'type' => 'string', 'nullable' => false],
                ['name' => 'email', 'type' => 'string', 'nullable' => false],
            ],
            columnConfigs: [
                'id' => ['show_index' => true, 'show_create' => false, 'show_edit' => false, 'show_print' => true, 'input_type' => 'number', 'validation_rules' => 'required|integer', 'label' => '', 'select_options' => '', 'select_from_table' => false, 'select_table' => '', 'select_value_column' => 'id', 'select_display_column' => '', 'foreign_table' => '', 'foreign_column' => '', 'searchable_select' => false],
                'name' => ['show_index' => true, 'show_create' => true, 'show_edit' => true, 'show_print' => true, 'input_type' => 'text', 'validation_rules' => 'required|string|max:255', 'label' => '', 'select_options' => '', 'select_from_table' => false, 'select_table' => '', 'select_value_column' => 'id', 'select_display_column' => '', 'foreign_table' => '', 'foreign_column' => '', 'searchable_select' => false],
                'email' => ['show_index' => true, 'show_create' => true, 'show_edit' => true, 'show_print' => true, 'input_type' => 'email', 'validation_rules' => 'required|string|max:255', 'label' => '', 'select_options' => '', 'select_from_table' => false, 'select_table' => '', 'select_value_column' => 'id', 'select_display_column' => '', 'foreign_table' => '', 'foreign_column' => '', 'searchable_select' => false],
            ],
            displayNameColumn: 'name',
            successMessage: 'Record created successfully.',
            deleteConfirmationMessage: 'Are you sure?',
            modularFolders: false,
            softDeletes: false,
            timestamps: true,
            generateFormRequest: false,
            generatePolicy: false,
            generateApi: false,
            apiPrefix: 'api',
            generateMenuItem: false,
            menuLayoutPath: 'resources/views/layouts/contentNavbarLayout.blade.php',
            menuLabel: '',
            menuIcon: '',
            menuRoutePrefix: '',
        );

        $this->assertArrayHasKey('files', $result);
        $this->assertArrayHasKey('routes_appended', $result);
        $this->assertArrayHasKey('api_routes_appended', $result);
        $this->assertArrayHasKey('menu_item_appended', $result);
        $this->assertIsArray($result['files']);
        $this->assertNotEmpty($result['files']);

        foreach ($result['files'] as $file) {
            $this->assertArrayHasKey('type', $file);
            $this->assertArrayHasKey('path', $file);
            $this->assertArrayHasKey('written', $file);
            $this->assertTrue($file['written']);
        }
    }

    public function test_run_generates_ajax_controller(): void
    {
        $basePath = $this->app->basePath();
        $controllerFile = "{$basePath}/app/Http/Controllers/UsersController.php";
        $modelFile = "{$basePath}/app/Models/User.php";
        $viewDir = "{$basePath}/resources/views/users";

        @mkdir(dirname($controllerFile), 0755, true);
        @mkdir(dirname($modelFile), 0755, true);
        @mkdir($viewDir, 0755, true);

        $engine = new GeneratorEngine();
        $result = $engine->run(
            table: 'users',
            techStack: 'ajax',
            columns: [
                ['name' => 'id', 'type' => 'integer', 'nullable' => false],
                ['name' => 'name', 'type' => 'string', 'nullable' => false],
            ],
            columnConfigs: [
                'id' => ['show_index' => true, 'show_create' => false, 'show_edit' => false, 'show_print' => true, 'input_type' => 'number', 'validation_rules' => 'required|integer', 'label' => '', 'select_options' => '', 'select_from_table' => false, 'select_table' => '', 'select_value_column' => 'id', 'select_display_column' => '', 'foreign_table' => '', 'foreign_column' => '', 'searchable_select' => false],
                'name' => ['show_index' => true, 'show_create' => true, 'show_edit' => true, 'show_print' => true, 'input_type' => 'text', 'validation_rules' => 'required|string|max:255', 'label' => '', 'select_options' => '', 'select_from_table' => false, 'select_table' => '', 'select_value_column' => 'id', 'select_display_column' => '', 'foreign_table' => '', 'foreign_column' => '', 'searchable_select' => false],
            ],
            displayNameColumn: 'name',
            successMessage: 'Record created successfully.',
            deleteConfirmationMessage: 'Are you sure?',
            modularFolders: false,
        );

        $this->assertFileExists($controllerFile);
        $this->assertFileExists($modelFile);
        $this->assertFileExists("{$viewDir}/index.blade.php");
        $this->assertFileExists("{$viewDir}/create.blade.php");
        $this->assertFileExists("{$viewDir}/edit.blade.php");
        $this->assertFileExists("{$viewDir}/show.blade.php");
        $this->assertFileExists("{$viewDir}/print.blade.php");

        $controllerContent = File::get($controllerFile);
        $this->assertStringContainsString('class UsersController', $controllerContent);

        $modelContent = File::get($modelFile);
        $this->assertStringContainsString('class User extends Model', $modelContent);
        $this->assertStringContainsString("protected \$table = 'users'", $modelContent);
        $this->assertStringContainsString("'name'", $modelContent);
        $this->assertStringContainsString("'email'", $modelContent);

        $engine->deleteGenerated('users', 'ajax', false);
    }

    public function test_run_generates_livewire_components(): void
    {
        $basePath = $this->app->basePath();
        $modelFile = "{$basePath}/app/Models/Users.php";
        $livewireDir = "{$basePath}/app/Livewire";
        $viewDir = "{$basePath}/resources/views/users";

        @mkdir(dirname($modelFile), 0755, true);
        @mkdir($livewireDir, 0755, true);
        @mkdir($viewDir, 0755, true);

        $engine = new GeneratorEngine();
        $result = $engine->run(
            table: 'users',
            techStack: 'livewire-v3',
            columns: [
                ['name' => 'id', 'type' => 'integer', 'nullable' => false],
                ['name' => 'name', 'type' => 'string', 'nullable' => false],
            ],
            columnConfigs: [
                'id' => ['show_index' => true, 'show_create' => false, 'show_edit' => false, 'show_print' => true, 'input_type' => 'number', 'validation_rules' => 'required|integer', 'label' => '', 'select_options' => '', 'select_from_table' => false, 'select_table' => '', 'select_value_column' => 'id', 'select_display_column' => '', 'foreign_table' => '', 'foreign_column' => '', 'searchable_select' => false],
                'name' => ['show_index' => true, 'show_create' => true, 'show_edit' => true, 'show_print' => true, 'input_type' => 'text', 'validation_rules' => 'required|string|max:255', 'label' => '', 'select_options' => '', 'select_from_table' => false, 'select_table' => '', 'select_value_column' => 'id', 'select_display_column' => '', 'foreign_table' => '', 'foreign_column' => '', 'searchable_select' => false],
            ],
            displayNameColumn: 'name',
            successMessage: 'Record created successfully.',
            deleteConfirmationMessage: 'Are you sure?',
            modularFolders: false,
        );

        $this->assertFileExists($modelFile);
        $this->assertFileExists("{$livewireDir}/IndexUsers.php");
        $this->assertFileExists("{$livewireDir}/CreateUsers.php");
        $this->assertFileExists("{$livewireDir}/EditUsers.php");
        $this->assertFileExists("{$livewireDir}/ShowUsers.php");

        $engine->deleteGenerated('users', 'livewire-v3', false);
    }

    public function test_run_with_form_request_generates_request_class(): void
    {
        $basePath = $this->app->basePath();
        $reqDir = "{$basePath}/app/Http/Requests";

        @mkdir($reqDir, 0755, true);
        @mkdir("{$basePath}/app/Http/Controllers", 0755, true);
        @mkdir("{$basePath}/app/Models", 0755, true);
        @mkdir("{$basePath}/resources/views/users", 0755, true);

        $engine = new GeneratorEngine();
        $result = $engine->run(
            table: 'users',
            techStack: 'ajax',
            columns: [
                ['name' => 'id', 'type' => 'integer', 'nullable' => false],
                ['name' => 'name', 'type' => 'string', 'nullable' => false],
            ],
            columnConfigs: [
                'id' => ['show_index' => true, 'show_create' => false, 'show_edit' => false, 'show_print' => true, 'input_type' => 'number', 'validation_rules' => 'required|integer', 'label' => '', 'select_options' => '', 'select_from_table' => false, 'select_table' => '', 'select_value_column' => 'id', 'select_display_column' => '', 'foreign_table' => '', 'foreign_column' => '', 'searchable_select' => false],
                'name' => ['show_index' => true, 'show_create' => true, 'show_edit' => true, 'show_print' => true, 'input_type' => 'text', 'validation_rules' => 'required|string|max:255', 'label' => '', 'select_options' => '', 'select_from_table' => false, 'select_table' => '', 'select_value_column' => 'id', 'select_display_column' => '', 'foreign_table' => '', 'foreign_column' => '', 'searchable_select' => false],
            ],
            displayNameColumn: 'name',
            successMessage: 'Record created successfully.',
            deleteConfirmationMessage: 'Are you sure?',
            modularFolders: false,
            generateFormRequest: true,
        );

        $this->assertFileExists("{$reqDir}/UsersRequest.php");
        $requestContent = File::get("{$reqDir}/UsersRequest.php");
        $this->assertStringContainsString('class UsersRequest extends FormRequest', $requestContent);

        $controllerContent = File::get("{$basePath}/app/Http/Controllers/UsersController.php");
        $this->assertStringContainsString('use App\Http\Requests\UsersRequest;', $controllerContent);
        $this->assertStringContainsString('UsersRequest $request', $controllerContent);

        $engine->deleteGenerated('users', 'ajax', false);
    }

    public function test_run_with_policy_generates_policy_class(): void
    {
        $basePath = $this->app->basePath();
        $policyDir = "{$basePath}/app/Policies";

        @mkdir($policyDir, 0755, true);
        @mkdir("{$basePath}/app/Http/Controllers", 0755, true);
        @mkdir("{$basePath}/app/Models", 0755, true);
        @mkdir("{$basePath}/resources/views/users", 0755, true);

        $engine = new GeneratorEngine();
        $result = $engine->run(
            table: 'users',
            techStack: 'ajax',
            columns: [
                ['name' => 'id', 'type' => 'integer', 'nullable' => false],
                ['name' => 'name', 'type' => 'string', 'nullable' => false],
            ],
            columnConfigs: [
                'id' => ['show_index' => true, 'show_create' => false, 'show_edit' => false, 'show_print' => true, 'input_type' => 'number', 'validation_rules' => 'required|integer', 'label' => '', 'select_options' => '', 'select_from_table' => false, 'select_table' => '', 'select_value_column' => 'id', 'select_display_column' => '', 'foreign_table' => '', 'foreign_column' => '', 'searchable_select' => false],
                'name' => ['show_index' => true, 'show_create' => true, 'show_edit' => true, 'show_print' => true, 'input_type' => 'text', 'validation_rules' => 'required|string|max:255', 'label' => '', 'select_options' => '', 'select_from_table' => false, 'select_table' => '', 'select_value_column' => 'id', 'select_display_column' => '', 'foreign_table' => '', 'foreign_column' => '', 'searchable_select' => false],
            ],
            displayNameColumn: 'name',
            successMessage: 'Record created successfully.',
            deleteConfirmationMessage: 'Are you sure?',
            modularFolders: false,
            generatePolicy: true,
        );

        $this->assertFileExists("{$policyDir}/UsersPolicy.php");
        $policyContent = File::get("{$policyDir}/UsersPolicy.php");
        $this->assertStringContainsString('class UsersPolicy', $policyContent);
        $this->assertStringContainsString('use Illuminate\Auth\Access\HandlesAuthorization;', $policyContent);

        $engine->deleteGenerated('users', 'ajax', false);
    }

    public function test_run_with_soft_deletes_adds_trait(): void
    {
        $basePath = $this->app->basePath();
        $modelFile = "{$basePath}/app/Models/Users.php";

        @mkdir(dirname($modelFile), 0755, true);
        @mkdir("{$basePath}/app/Http/Controllers", 0755, true);
        @mkdir("{$basePath}/resources/views/users", 0755, true);

        $engine = new GeneratorEngine();
        $result = $engine->run(
            table: 'users',
            techStack: 'ajax',
            columns: [
                ['name' => 'id', 'type' => 'integer', 'nullable' => false],
                ['name' => 'name', 'type' => 'string', 'nullable' => false],
            ],
            columnConfigs: [
                'id' => ['show_index' => true, 'show_create' => false, 'show_edit' => false, 'show_print' => true, 'input_type' => 'number', 'validation_rules' => 'required|integer', 'label' => '', 'select_options' => '', 'select_from_table' => false, 'select_table' => '', 'select_value_column' => 'id', 'select_display_column' => '', 'foreign_table' => '', 'foreign_column' => '', 'searchable_select' => false],
                'name' => ['show_index' => true, 'show_create' => true, 'show_edit' => true, 'show_print' => true, 'input_type' => 'text', 'validation_rules' => 'required|string|max:255', 'label' => '', 'select_options' => '', 'select_from_table' => false, 'select_table' => '', 'select_value_column' => 'id', 'select_display_column' => '', 'foreign_table' => '', 'foreign_column' => '', 'searchable_select' => false],
            ],
            displayNameColumn: 'name',
            successMessage: 'Record created successfully.',
            deleteConfirmationMessage: 'Are you sure?',
            modularFolders: false,
            softDeletes: true,
        );

        $modelContent = File::get($modelFile);
        $this->assertStringContainsString('use Illuminate\Database\Eloquent\SoftDeletes;', $modelContent);
        $this->assertStringContainsString('use SoftDeletes;', $modelContent);

        $engine->deleteGenerated('users', 'ajax', false);
    }

    public function test_run_with_timestamps_false(): void
    {
        $basePath = $this->app->basePath();
        $modelFile = "{$basePath}/app/Models/Users.php";

        @mkdir(dirname($modelFile), 0755, true);
        @mkdir("{$basePath}/app/Http/Controllers", 0755, true);
        @mkdir("{$basePath}/resources/views/users", 0755, true);

        $engine = new GeneratorEngine();
        $result = $engine->run(
            table: 'users',
            techStack: 'ajax',
            columns: [
                ['name' => 'id', 'type' => 'integer', 'nullable' => false],
                ['name' => 'name', 'type' => 'string', 'nullable' => false],
            ],
            columnConfigs: [
                'id' => ['show_index' => true, 'show_create' => false, 'show_edit' => false, 'show_print' => true, 'input_type' => 'number', 'validation_rules' => 'required|integer', 'label' => '', 'select_options' => '', 'select_from_table' => false, 'select_table' => '', 'select_value_column' => 'id', 'select_display_column' => '', 'foreign_table' => '', 'foreign_column' => '', 'searchable_select' => false],
                'name' => ['show_index' => true, 'show_create' => true, 'show_edit' => true, 'show_print' => true, 'input_type' => 'text', 'validation_rules' => 'required|string|max:255', 'label' => '', 'select_options' => '', 'select_from_table' => false, 'select_table' => '', 'select_value_column' => 'id', 'select_display_column' => '', 'foreign_table' => '', 'foreign_column' => '', 'searchable_select' => false],
            ],
            displayNameColumn: 'name',
            successMessage: 'Record created successfully.',
            deleteConfirmationMessage: 'Are you sure?',
            modularFolders: false,
            timestamps: false,
        );

        $modelContent = File::get($modelFile);
        $this->assertStringContainsString("public \$timestamps = false;", $modelContent);

        $engine->deleteGenerated('users', 'ajax', false);
    }

    public function test_rollback_deletes_created_files_on_exception(): void
    {
        $engine = new GeneratorEngine();

        $reflection = new \ReflectionClass($engine);
        $createdFilesProp = $reflection->getProperty('createdFiles');
        $createdFilesProp->setAccessible(true);

        $basePath = $this->app->basePath();
        $testFile = "{$basePath}/tmp_test_file.txt";

        @mkdir(dirname($testFile), 0755, true);
        File::put($testFile, 'test');
        $createdFilesProp->setValue($engine, [$testFile]);

        $this->assertFileExists($testFile);

        $engine->rollback();

        $this->assertFileDoesNotExist($testFile);
    }

    public function test_delete_generated_removes_files_routes_and_menu(): void
    {
        $basePath = $this->app->basePath();

        $controllerFile = "{$basePath}/app/Http/Controllers/DeletionsController.php";
        $modelFile = "{$basePath}/app/Models/Deletion.php";
        $viewDir = "{$basePath}/resources/views/deletions";
        $routeFile = $basePath . '/routes/web.php';

        @mkdir(dirname($controllerFile), 0755, true);
        @mkdir(dirname($modelFile), 0755, true);
        @mkdir($viewDir, 0755, true);

        File::put($controllerFile, '<?php // test');
        File::put($modelFile, '<?php // test');
        File::put("{$viewDir}/index.blade.php", '<html></html>');

        File::append($routeFile, "\n\n// MAGIC-GENERATOR: Deletion\nRoute::prefix('deletions')->name('deletions.')->group(function () {\n    Route::get('/', [\\App\\Http\\Controllers\\DeletionController::class, 'index'])->name('index');\n});\n");

        $engine = new GeneratorEngine();
        $result = $engine->deleteGenerated(
            table: 'deletions',
            techStack: 'ajax',
            modularFolders: false,
            generateApi: false,
            generateMenuItem: false,
            menuLayoutPath: 'resources/views/layouts/contentNavbarLayout.blade.php',
            apiPrefix: 'api',
        );

        $this->assertTrue($result['routes_removed']);
        $this->assertFileDoesNotExist($controllerFile);
        $this->assertFileDoesNotExist($modelFile);
        $this->assertDirectoryDoesNotExist($viewDir);

        $routeContent = File::get($routeFile);
        $this->assertStringNotContainsString('MAGIC-GENERATOR: Deletion', $routeContent);
    }

    public function test_build_relationships_with_fk_columns(): void
    {
        $engine = new GeneratorEngine();

        $reflection = new \ReflectionClass($engine);
        $method = $reflection->getMethod('buildRelationships');
        $method->setAccessible(true);

        $columns = [
            ['name' => 'id', 'type' => 'integer', 'nullable' => false],
            ['name' => 'category_id', 'type' => 'integer', 'nullable' => false],
        ];

        $columnConfigs = [
            'id' => ['show_index' => true, 'show_create' => false, 'show_edit' => false, 'show_print' => true, 'input_type' => 'number', 'validation_rules' => 'required|integer', 'label' => '', 'select_options' => '', 'select_from_table' => false, 'select_table' => '', 'select_value_column' => 'id', 'select_display_column' => '', 'foreign_table' => '', 'foreign_column' => '', 'searchable_select' => false],
            'category_id' => ['show_index' => true, 'show_create' => true, 'show_edit' => true, 'show_print' => true, 'input_type' => 'number', 'validation_rules' => 'required|integer', 'label' => '', 'select_options' => '', 'select_from_table' => false, 'select_table' => '', 'select_value_column' => 'id', 'select_display_column' => '', 'foreign_table' => '', 'foreign_column' => '', 'searchable_select' => false],
        ];

        $result = $method->invoke($engine, $columns, $columnConfigs);

        $this->assertStringContainsString('belongsTo', $result);
        $this->assertStringContainsString('Category', $result);
        $this->assertStringContainsString('category_id', $result);
    }

    public function test_build_casts_fields(): void
    {
        $engine = new GeneratorEngine();

        $reflection = new \ReflectionClass($engine);
        $method = $reflection->getMethod('buildCastsFields');
        $method->setAccessible(true);

        $columns = [
            ['name' => 'id', 'type' => 'integer', 'nullable' => false],
            ['name' => 'is_active', 'type' => 'boolean', 'nullable' => false],
            ['name' => 'price', 'type' => 'float', 'nullable' => true],
            ['name' => 'settings', 'type' => 'json', 'nullable' => true],
        ];

        $columnConfigs = [];

        $result = $method->invoke($engine, $columns, $columnConfigs);

        $this->assertStringContainsString("'id' => 'integer'", $result);
        $this->assertStringContainsString("'is_active' => 'boolean'", $result);
        $this->assertStringContainsString("'price' => 'float'", $result);
        $this->assertStringContainsString("'settings' => 'array'", $result);
    }

    public function test_remove_route_block_removes_correct_section(): void
    {
        $engine = new GeneratorEngine();

        $reflection = new \ReflectionClass($engine);
        $method = $reflection->getMethod('removeRouteBlock');
        $method->setAccessible(true);

        $tempRouteFile = sys_get_temp_dir() . '/test_routes_web.php';
        File::put($tempRouteFile, "<?php\n\n// MAGIC-GENERATOR: Test\nRoute::prefix('tests')->name('tests.')->group(function () {\n    Route::get('/', \\App\\Livewire\\IndexTest::class)->name('index');\n});\n\n// Other routes\nRoute::get('/other', function () {});\n");

        $result = $method->invoke($engine, $tempRouteFile, 'MAGIC-GENERATOR: Test');

        $this->assertTrue($result);

        $content = File::get($tempRouteFile);
        $this->assertStringNotContainsString('MAGIC-GENERATOR: Test', $content);
        $this->assertStringNotContainsString("Route::prefix('tests')", $content);
        $this->assertStringContainsString('// Other routes', $content);

        @unlink($tempRouteFile);
    }

    public function test_remove_route_block_returns_false_when_marker_not_found(): void
    {
        $engine = new GeneratorEngine();

        $reflection = new \ReflectionClass($engine);
        $method = $reflection->getMethod('removeRouteBlock');
        $method->setAccessible(true);

        $tempRouteFile = sys_get_temp_dir() . '/test_routes_no_match.php';
        File::put($tempRouteFile, "<?php\n\nRoute::get('/test', function () {});\n");

        $result = $method->invoke($engine, $tempRouteFile, 'MAGIC-GENERATOR: NonExistent');

        $this->assertFalse($result);

        @unlink($tempRouteFile);
    }

    public function test_remove_menu_item_block_removes_correct_section(): void
    {
        $engine = new GeneratorEngine();

        $reflection = new \ReflectionClass($engine);
        $method = $reflection->getMethod('removeMenuItemBlock');
        $method->setAccessible(true);

        $tempLayoutFile = sys_get_temp_dir() . '/test_layout.blade.php';
        File::put($tempLayoutFile, "<html>\n<body>\n    @stack('scripts')\n    <!-- MAGIC-GENERATOR: Test -->\n    <li class=\"menu-item\"><a href=\"#\" class=\"menu-link\"><div class=\"fw-normal\">Test</div></a></li>\n</body>\n</html>");

        $result = $method->invoke($engine, $tempLayoutFile, 'Test');

        $this->assertTrue($result);

        $content = File::get($tempLayoutFile);
        $this->assertStringNotContainsString('MAGIC-GENERATOR: Test', $content);
        $this->assertStringNotContainsString('<li class="menu-item"', $content);
        $this->assertStringContainsString('@stack', $content);

        @unlink($tempLayoutFile);
    }
}