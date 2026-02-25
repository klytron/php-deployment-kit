<?php

/**
 * Klytron Simple PHP Deployment Recipe
 *
 * For simple PHP projects that don't use a specific framework.
 * This recipe sets appropriate defaults for basic PHP applications.
 *
 * @package Klytron\Deployer\PHP
 */

namespace Deployer;

// Load core framework-agnostic tasks
require_once __DIR__ . '/../klytron-tasks.php';

///////////////////////////////////////////////////////////////////////////////
// PHP-SPECIFIC CONFIGURATION
///////////////////////////////////////////////////////////////////////////////

// Set PHP-specific defaults
// For simple PHP projects, the public directory is usually the project root
if (!has('public_dir_path')) {
    set('public_dir_path', '{{deploy_path}}/current');
}
if (!has('shared_dir_path')) {
    set('shared_dir_path', '{{deploy_path}}/shared');
}

///////////////////////////////////////////////////////////////////////////////
// PHP-SPECIFIC TASKS
///////////////////////////////////////////////////////////////////////////////

/**
 * Simple PHP deployment flow using framework-agnostic tasks
 */
task('klytron:php:deploy:complete', [
    'deploy:start_timer',
    'klytron:validate:basic',                    // Framework-agnostic validation
    'deploy:unlock',
    'deploy:fix_repo',
    'klytron:deploy:prepare:complete',           // Framework-agnostic preparation
    'deploy:setup',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'klytron:deploy:environment:complete',       // Framework-agnostic env deployment
    'deploy:env',
    'deploy:vendors',
    'deploy:writable',
    'deploy:symlink',
    'klytron:deploy:finalize:complete',          // Framework-agnostic finalization
    'deploy:unlock',
    'deploy:cleanup',
    'klytron:deploy:notify:complete',            // Framework-agnostic success notification
    'deploy:end_timer',
])->desc('Complete PHP deployment flow');

/**
 * Minimal PHP deployment flow for very simple projects
 */
task('klytron:php:deploy:minimal', [
    'deploy:start_timer',
    'deploy:unlock',
    'deploy:setup',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:writable',
    'deploy:symlink',
    'klytron:deploy:create:server_symlink',
    'deploy:unlock',
    'deploy:cleanup',
    'deploy:end_timer',
])->desc('Minimal PHP deployment flow');

///////////////////////////////////////////////////////////////////////////////
// PHP PROJECT HELPERS
///////////////////////////////////////////////////////////////////////////////

/**
 * Configure a simple PHP project
 */
function php_configure_project(string $name, string $repository, array $config = []): void {
    // Use the standard klytron configuration
    klytron_configure_app($name, $repository, $config);
    
    // Set PHP-specific defaults
    klytron_configure_project([
        'type' => 'php',
        'database' => 'none',
        'public_dir_path' => '{{deploy_path}}/current', // PHP projects usually serve from root
        'supports_passport' => false,
        'supports_vite' => false,
        'supports_storage_link' => false,
        'supports_sitemap' => false,
    ]);
}

/**
 * Configure a PHP project with custom public directory
 */
function php_configure_project_with_public(string $name, string $repository, string $publicDir = 'public', array $config = []): void {
    // Use the standard klytron configuration
    klytron_configure_app($name, $repository, $config);
    
    // Set PHP-specific defaults with custom public directory
    klytron_configure_project([
        'type' => 'php',
        'database' => 'none',
        'public_dir_path' => "{{deploy_path}}/current/$publicDir",
        'supports_passport' => false,
        'supports_vite' => false,
        'supports_storage_link' => false,
        'supports_sitemap' => false,
    ]);
}

///////////////////////////////////////////////////////////////////////////////
// PHP-SPECIFIC DEPLOYMENT FLOWS
///////////////////////////////////////////////////////////////////////////////

/**
 * Get the complete PHP deployment flow
 * This uses framework-agnostic tasks suitable for PHP projects
 */
function klytron_php_deploy_flow(): array {
    return [
        'deploy:start_timer',
        'klytron:validate:basic',                    // Framework-agnostic validation
        'deploy:unlock',
        'klytron:deploy:prepare:complete',           // Framework-agnostic preparation
        'deploy:setup',
        'deploy:lock',
        'deploy:release',
        'deploy:fix_repo',                          // Fix repo issues BEFORE code update
        'deploy:update_code',
        'deploy:shared',
        'klytron:deploy:environment:complete',       // Framework-agnostic env deployment
        'deploy:env',
        'deploy:vendors',
        'deploy:writable',
        'deploy:symlink',
        'klytron:deploy:finalize:complete',          // Framework-agnostic finalization
        'deploy:unlock',
        'deploy:cleanup',
        'klytron:deploy:notify:complete',            // Framework-agnostic success notification
        'deploy:end_timer',
    ];
}

/**
 * Get a minimal PHP deployment flow (same as klytron:php:deploy:minimal task)
 */
function klytron_php_deploy_flow_minimal(): array {
    return [
        'deploy:start_timer',
        'deploy:unlock',
        'deploy:setup',
        'deploy:lock',
        'deploy:release',
        'deploy:update_code',
        'deploy:shared',
        'deploy:writable',
        'deploy:symlink',
        'klytron:deploy:create:server_symlink',
        'deploy:unlock',
        'deploy:cleanup',
        'deploy:end_timer',
    ];
}
