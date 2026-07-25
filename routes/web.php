<?php

use Illuminate\Support\Facades\Route;

Route::middleware(config('magic-generator.routes.middleware', ['web']))
    ->prefix(config('magic-generator.routes.prefix', 'magic-generator'))
    ->group(function () {
        Route::get('/', function () {
            return view('magic-generator::dashboard');
        })->name('magic-generator.dashboard');
    });
