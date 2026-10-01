<?php

declare(strict_types=1);

/**
 * PSR-4 Autoloader for EidCloud Function Simulator (Zero External Vendor Dependencies)
 */
spl_autoload_register(static function (string $class): void {
    $prefix = 'EidCloud\\FunctionSimulator\\';
    $baseDir = __DIR__ . '/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});
