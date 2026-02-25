<?php
/**
 * Simple PHP Project Deployment Example
 * 
 * This example shows how to deploy a simple PHP application
 * without database requirements using minimal deployment flow.
 * 
 * @package SimplePHPExample
 * @author Michael K. Laweh (klytron) (https://www.klytron.com)
 */

namespace Deployer;

///////////////////////////////////////////////////////////////////////////////
// INCLUDE KLYTRON PHP DEPLOYMENT KIT
///////////////////////////////////////////////////////////////////////////////

// Include the Klytron PHP Deployment Kit (framework-agnostic core)
require __DIR__ . '/vendor/klytron/php-deployment-kit/deployment-kit.php';

///////////////////////////////////////////////////////////////////////////////
// PROJECT-SPECIFIC CONFIGURATION
///////////////////////////////////////////////////////////////////////////////

// Configure application using Klytron library
klytron_configure_app(
    'simple-php-project',                         // Application name
    'git@github.com:user/simple-php-project.git', // Repository URL
    [
        'keep_releases' => 2,               // Number of releases to keep
        'default_timeout' => 900,           // Deployment timeout (15 minutes)
    ]
);

// Configure deployment paths
klytron_set_paths('/var/www/projects');

// Set PHP version
klytron_set_php_version('php8.2');

// Configure host
klytron_configure_host('your-server.com', [
    'remote_user' => 'root',
    'branch' => 'main',
    'http_user' => 'www-data',
    'http_group' => 'www-data',
    'labels' => ['stage' => 'production'],
]);

///////////////////////////////////////////////////////////////////////////////
// SIMPLE PHP PROJECT-SPECIFIC SHARED FILES/DIRECTORIES
///////////////////////////////////////////////////////////////////////////////

// Configure shared files (minimal for PHP project)
klytron_configure_shared_files([
    '.env',                    // Environment configuration (if used)
]);

// Configure shared directories (minimal for PHP project)
klytron_configure_shared_dirs([
    'logs',                    // Application logs
    'cache',                   // Cache directory (if used)
]);

// Configure writable directories
klytron_configure_writable_dirs([
    'logs',                    // Logs directory
    'cache',                   // Cache directory
    'uploads',                 // Uploads directory (if used)
]);

///////////////////////////////////////////////////////////////////////////////
// CUSTOM DEPLOYMENT FLOW FOR SIMPLE PHP PROJECT
///////////////////////////////////////////////////////////////////////////////

// Minimal deployment flow for PHP project using framework-agnostic tasks
task('deploy', [
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
])->desc('Deploy Simple PHP project');

///////////////////////////////////////////////////////////////////////////////
// CUSTOM TASKS FOR SIMPLE PHP PROJECT
///////////////////////////////////////////////////////////////////////////////

// Test task
task('test', function () {
    info("🎉 Klytron Deployer Library is working!");
    info("Application: " . get('application'));
    info("Repository: " . get('repository'));
    info("Deploy Path: " . get('deploy_path_parent'));
})->desc('Test the Klytron Deployer Library');

// Help task
task('help', function () {
    info("🐘 ===== SIMPLE PHP PROJECT DEPLOYMENT HELP =====");
    info("");
    info("📋 Available Commands:");
    info("  vendor/bin/dep test    - Test the library");
    info("  vendor/bin/dep deploy  - Deploy Simple PHP Project");
    info("  vendor/bin/dep help    - Show this help");
    info("");
    info("🐘 Simple PHP Project Features:");
    info("  - Minimal deployment flow");
    info("  - No database operations");
    info("  - Basic file and directory management");
    info("  - Environment configuration support");
    info("");
    info("📞 Support:");
    info("  - Documentation: https://github.com/klytron/php-deployment-kit");
    info("================================================");
})->desc('Show Simple PHP Project deployment help');
