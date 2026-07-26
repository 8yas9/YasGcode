<?php

namespace YasKSalim\MagicGenerator;

use Livewire\Livewire;
use Illuminate\Support\ServiceProvider;

class MagicGeneratorServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/magic-generator.php', 'magic-generator');

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'magic-generator');

        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        $this->publishes([
            __DIR__ . '/../config/magic-generator.php' => config_path('magic-generator.php'),
        ], 'magic-generator-config');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/magic-generator'),
        ], 'magic-generator-views');

        $this->publishes([
            __DIR__ . '/../stubs' => config('magic-generator.stubs_path', base_path('stubs/vendor/magic-generator')),
        ], 'magic-generator-stubs');

        Livewire::component('magic-generator', Http\Livewire\GeneratorDashboard::class);
    }

    public function register(): void
    {
    }
}
