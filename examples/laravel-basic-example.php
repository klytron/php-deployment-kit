<?php
/**
 * Laravel Deployment Example — Full Feature Set
 *
 * This is a real-world example of a Laravel project deployment using
 * klytron/php-deployment-kit. It demonstrates all available configuration
 * options so you can pick what applies to your project.
 *
 * @see templates/laravel-deploy.php.template  — start here for a new project
 * @see docs/quick-start.md                    — 5-minute tutorial
 * @see docs/configuration-reference.md        — every option explained
 */

namespace Deployer;

// ── Package ───────────────────────────────────────────────────────────────────
// deployment-kit.php bootstraps Deployer and registers all klytron_* helpers.
// The Laravel recipe adds all Laravel-specific tasks (artisan, Vite, passport…)

require __DIR__ . '/vendor/klytron/php-deployment-kit/deployment-kit.php';
require __DIR__ . '/vendor/klytron/php-deployment-kit/recipes/klytron-laravel-recipe.php';

// ── Application ───────────────────────────────────────────────────────────────
// The third parameter passes any standard Deployer global config.
// deploy_path is constructed as: {deploy_path_parent}/{application}

klytron_configure_app('my-laravel-app', 'git@github.com:my-org/my-laravel-app.git', [
    'keep_releases'    => 3,      // Old releases kept on server for rollback
    'default_timeout'  => 1800,   // Max time per SSH command (30 min)
    'ssh_multiplexing' => true,   // Reuse SSH control socket for faster deploys
    'git_tty'          => false,  // Must be false for non-interactive deploys
]);

// ── Paths ─────────────────────────────────────────────────────────────────────
// Arg 1 (deploy_path_parent): where your apps live, e.g. /var/www
// Arg 2 (application_public_html): document root the web server reads from.
//
// ${APP_URL_DOMAIN} is a dynamic placeholder — it resolves to the value passed
// to klytron_set_domain(). This lets you share a deploy.php across environments
// that use different domain names.

klytron_set_paths(
    '/var/www',
    '/var/www/${APP_URL_DOMAIN}/public_html'
);

klytron_set_domain('my-laravel-app.com');  // Resolves ${APP_URL_DOMAIN}
klytron_set_php_version('php8.3');         // Used for artisan, composer

// ── Project capabilities ──────────────────────────────────────────────────────
// Set only the flags that match your project. Everything else defaults to false/none.
//
// database options:   mysql | mariadb | postgresql | sqlite | none
// type options:       laravel | yii2 | php

klytron_configure_project([
    'type'     => 'laravel',
    'database' => 'mysql',          // Enables migration and import tasks

    // .env file handling
    'env_file_local'  => '.env.production', // File read from your local machine
    'env_file_remote' => '.env',            // Written to shared/.env on server

    // Database import path (used when database operation = 'import' or 'both')
    'db_import_path' => 'database/live-db-exports',

    // Frontend build — choose one (or none)
    'supports_nodejs' => true,    // Enables npm install on server
    'supports_vite'   => true,    // npm run build via Vite
    'supports_mix'    => false,   // npm run production via Laravel Mix

    // Laravel specifics
    'supports_storage_link' => true,   // artisan storage:link
    'supports_passport'     => false,  // passport:install (generates OAuth keys)

    // Post-deploy enhancements
    'supports_sitemap' => true,    // Generate and verify sitemap
    'verify_fonts'     => true,    // HTTP-check web font accessibility
    'cleanup_assets'   => true,    // Remove any rogue .htaccess in build/
    'optimize_images'  => false,   // Compress images in storage/app/public/

    // Laravel env encryption (requires LARAVEL_ENV_ENCRYPTION_KEY env var)
    'enable_encryption' => false,  // true = decrypt .env on server before use
]);

// ── Host ──────────────────────────────────────────────────────────────────────
// Add multiple klytron_configure_host() calls for staging/production/etc.
// Target specific hosts with: vendor/bin/dep deploy --stage=staging

klytron_configure_host('your-server.com', [
    'remote_user' => 'deploy',           // SSH user
    'branch'      => 'main',             // Git branch deployed from
    'http_user'   => 'www-data',         // chown target (web server user)
    'http_group'  => 'www-data',         // chown target (web server group)
    'labels'      => ['stage' => 'production'],
    'ssh_options' => [
        'ConnectTimeout'      => 30,
        'ServerAliveInterval' => 60,
        'ServerAliveCountMax' => 3,
    ],
]);

// ── Shared files & dirs ───────────────────────────────────────────────────────
// Shared items persist across all releases (symlinked into each release dir).
//
// shared_files: individual files uploaded once (e.g. .env, custom .htaccess)
// shared_dirs:  directories written to by the running app at runtime
// writable_dirs: receives chmod/chown — must include everything the app writes to

klytron_configure_shared_files(['.env']);

klytron_configure_shared_dirs([
    'storage',          // Logs, sessions, filesystem cache, app files
    'public/storage',   // Public storage symlink target
    'bootstrap/cache',  // Routes, config, services bootstrap cache
]);

klytron_configure_writable_dirs([
    'bootstrap/cache',
    'storage',
    'storage/app',
    'storage/app/public',
    'storage/framework',
    'storage/framework/cache',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
    'public/storage',
]);

// ── Server config files ───────────────────────────────────────────────────────
// Files copied from your repo onto the server every deploy — useful for web
// server configs that differ per environment (.htaccess, robots.txt, etc.)

set('server_config_files', [
    [
        'source'    => 'server/.htaccess.production',
        'target'    => 'public/.htaccess',
        'mode'      => 0644,
        'overwrite' => true,
    ],
]);

// ── Unattended deployment (CI/CD) ────────────────────────────────────────────
// Uncomment the lines below to run fully unattended deploys without prompts.
// Leave all as null (or omit them) to keep interactive prompts for production.
//
// set('auto_confirm_production', true);         // Skip "deploy to production?" prompt
// set('auto_deployment_type', 'update');        // 'update' | 'fresh'
// set('auto_upload_env', true);                 // Skip .env upload prompt
// set('auto_database_operation', 'migrations'); // 'migrations'|'import'|'both'|'none'
// set('auto_clear_caches', true);               // Skip cache-clear prompt
// set('auto_confirm_settings', true);           // Skip settings confirmation

// ── Deployment flow ───────────────────────────────────────────────────────────
// This is the full Laravel pipeline. Comment out or remove tasks you don't need.
// The package skips redundant steps automatically based on your project config.

task('deploy', [
    // — Preparation —
    'klytron:deploy:start_timer',                     // Start wall-clock timer
    'deploy:unlock',                                  // Remove stale lock file
    'klytron:deploy:fix_repo',                        // git safe-dir + permission fixes
    'klytron:laravel:deploy:prepare:complete',        // Confirm + optional backup

    // — Code update —
    'deploy:setup',                                   // Create dirs on server first time
    'deploy:lock',                                    // Write deploy.lock
    'deploy:release',                                 // Create timestamped release dir
    'deploy:update_code',                             // git fetch + checkout
    'deploy:shared',                                  // Symlink shared files/dirs
    'klytron:deploy:fix_git_ownership',               // Fix ownership after clone

    // — Environment —
    'klytron:laravel:deploy:environment:complete',    // Upload .env + optional decrypt

    // — Dependencies —
    'deploy:vendors',                                 // composer install --no-dev

    // — Build (skipped when supports_vite = false) —
    'klytron:laravel:node:vite:build',                // npm install + npm run build

    // — Database (skipped when database = 'none') —
    'klytron:laravel:deploy:database:complete',       // Migrations and/or DB import

    // — Optimise —
    'deploy:writable',                                // chmod/chown writable dirs
    'klytron:laravel:deploy:cache:complete',          // cache:clear + config:cache + optimize

    // — Go live (atomic symlink switch) —
    'deploy:symlink',                                 // current → new release
    'klytron:laravel:deploy:finalize:complete',       // storage:link + permissions + web symlink

    // — Post-deploy enhancements —
    'klytron:assets:map',                             // Map hashed Vite assets for DB compat
    'klytron:assets:cleanup',                         // Remove rogue .htaccess in build/
    'klytron:sitemap:generate',                       // Generate sitemap.xml
    'klytron:sitemap:verify',                         // Check sitemap was written
    'klytron:sitemap:check',                          // HTTP-check sitemap is reachable
    'klytron:fonts:verify',                           // HTTP-check web fonts are accessible
    'klytron:images:optimize',                        // Compress images (if enabled)

    // — Cleanup & close —
    'deploy:unlock',                                  // Remove deploy.lock
    'deploy:cleanup',                                 // Delete old releases (keep_releases)
    'klytron:deploy:access_permissions',              // Final ownership/permission sweep
    'klytron:laravel:deploy:notify:complete',         // Success summary + optional notify
    'klytron:deploy:end_timer',                       // Print elapsed time
])->desc('Deploy Laravel application to production');

// ── Hooks ─────────────────────────────────────────────────────────────────────
// Hooks run extra tasks at specific points without touching the flow above.

after('klytron:laravel:deploy:success', 'klytron:system:restart'); // Reload PHP-FPM
after('deploy:shared', 'klytron:server:deploy:configs');           // Copy server config files

// ── Guard ─────────────────────────────────────────────────────────────────────

if (!file_exists('.env.production')) {
    throw new \RuntimeException('.env.production is required. Create it before deploying.');
}