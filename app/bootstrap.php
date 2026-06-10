<?php
$configFile = dirname(__DIR__) . '/config.php';
if (!file_exists($configFile)) {
    $configFile = dirname(__DIR__) . '/config.example.php';
}
$config = require $configFile;
date_default_timezone_set($config['timezone'] ?? 'Asia/Makassar');

spl_autoload_register(function ($class) {
    $base = __DIR__ . '/';
    $paths = [
        $base . 'Core/' . $class . '.php',
        $base . 'Controllers/' . $class . '.php',
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) { require_once $path; return; }
    }
});

Session::start($config);
Database::init($config['db']);
App::init($config);
require_once __DIR__ . '/Core/helpers.php';
