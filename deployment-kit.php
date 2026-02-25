<?php

// Klytron PHP Deployment Kit - Main Entry Point
// Framework-agnostic deployment library for PHP applications

// Try to load Composer autoloader to make task classes available
$autoloadPaths = [
    __DIR__ . '/vendor/autoload.php',           // Local development / standalone
    __DIR__ . '/../../autoload.php',            // Installed as composer package
];

foreach ($autoloadPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        break;
    }
}

require __DIR__ . '/deployment-kit-core.php';
