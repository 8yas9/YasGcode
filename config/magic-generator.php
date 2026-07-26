<?php

return [

    'stubs_path' => __DIR__ . '/../stubs',

    'paths' => [
        'controller'  => 'app/Http/Controllers',
        'model'        => 'app/Models',
        'livewire'     => 'app/Livewire',
        'views'        => 'resources/views',
        'form_request' => 'app/Http/Requests',
        'policy'       => 'app/Policies',
    ],

    'defaults' => [
        'success_message'             => 'Record created successfully.',
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
