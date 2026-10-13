<?php

// Vercel's function bundle is read-only; compiled views and package manifests are temporary.
$storage = sys_get_temp_dir().'/task-manager';
foreach (['framework/views', 'framework/cache', 'logs'] as $directory) {
    $path = $storage.'/'.$directory;
    if (! is_dir($path)) {
        mkdir($path, 0755, true);
    }
}

foreach ([
    'LARAVEL_STORAGE_PATH' => $storage,
    'VIEW_COMPILED_PATH' => $storage.'/framework/views',
    'APP_PACKAGES_CACHE' => $storage.'/packages.php',
    'APP_SERVICES_CACHE' => $storage.'/services.php',
] as $key => $value) {
    putenv($key.'='.$value);
    $_ENV[$key] = $_SERVER[$key] = $value;
}

require __DIR__.'/../public/index.php';
