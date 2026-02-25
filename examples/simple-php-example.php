<?php
/**
 * Simple PHP Deployment Example — Static / Non-Framework Site
 *
 * This example shows how to deploy a simple PHP application (no Laravel,
 * no database, no Node.js build step) using the minimal task pipeline.
 *
 * @see templates/simple-php.php.template     — start here for a new project
 * @see docs/quick-start.md                   — 5-minute tutorial
 * @see docs/configuration-reference.md       — every option explained
 */

namespace Deployer;

// ── Package ───────────────────────────────────────────────────────────────────
// Use the plain PHP recipe — no framework-specific tasks loaded.

require __DIR__ . '/vendor/klytron/php-deployment-kit/deployment-kit.php';
require __DIR__ . '/vendor/klytron/php-deployment-kit/recipes/klytron-php-recipe.php';

// ── Application ───────────────────────────────────────────────────────────────
// deploy_path is auto-built as: {deploy_path_parent}/{application}

klytron_configure_app('simple-php-site', 'git@github.com:my-org/simple-php-site.git', [
    'keep_releases'    => 2,     // Small site — keep last 2 releases only
    'default_timeout'  => 600,   // 10 min is plenty for small deployments
    'ssh_multiplexing' => true,
    'git_tty'          => false,
]);

// ── Paths ─────────────────────────────────────────────────────────────────────

klytron_set_paths(
    '/var/www',               // deploy_path = /var/www/simple-php-site
    '/var/www/html'           // document root the web server reads
);

klytron_set_php_version('php8.3');

// ── Host ──────────────────────────────────────────────────────────────────────

klytron_configure_host('your-server.com', [
    'remote_user' => 'deploy',
    'branch'      => 'main',
    'http_user'   => 'www-data',
    'http_group'  => 'www-data',
    'labels'      => ['stage' => 'production'],
    'ssh_options' => [
        'ConnectTimeout'      => 30,
        'ServerAliveInterval' => 60,
        'ServerAliveCountMax' => 3,
    ],
]);

// ── Project capabilities ──────────────────────────────────────────────────────
// Simple PHP projects typically need almost nothing enabled.

klytron_configure_project([
    'type'             => 'php',    // No framework
    'database'         => 'none',   // No DB operations
    'env_file_local'   => '.env',   // Uploaded as-is (no encryption)
    'env_file_remote'  => '.env',
    'supports_nodejs'  => false,    // No npm build
    'supports_sitemap' => false,
    'verify_fonts'     => false,
    'cleanup_assets'   => false,
    'optimize_images'  => false,
    'enable_encryption' => false,
]);

// ── Shared files & dirs ───────────────────────────────────────────────────────
// Keep only what your site actually writes to at runtime.

klytron_configure_shared_files([
    '.env',     // Any environment config (omit if not used)
]);

klytron_configure_shared_dirs([
    'uploads',  // User-uploaded files (if any)
    'cache',    // Page/data cache (if any)
    'logs',     // Application logs
]);

klytron_configure_writable_dirs([
    'uploads',
    'cache',
    'logs',
]);

// ── Deployment flow ───────────────────────────────────────────────────────────
// Minimal pipeline — no artisan, no composer (remove deploy:vendors if no composer.json),
// no Node.js, no database.

task('deploy', [
    'klytron:deploy:start_timer',       // Start wall-clock timer
    'deploy:unlock',                    // Remove stale lock file
    'klytron:deploy:fix_repo',          // git safe-dir + permission fixes
    'deploy:setup',                     // Create directory structure on server
    'deploy:lock',                      // Write deploy.lock
    'deploy:release',                   // Create timestamped release dir
    'deploy:update_code',               // git fetch + checkout
    'deploy:shared',                    // Symlink shared files/dirs
    'klytron:upload:env:production',    // rsync .env file to server
    'deploy:vendors',                   // composer install (remove if no composer.json)
    'deploy:writable',                  // chmod/chown writable dirs
    'deploy:symlink',                   // Atomic: current → new release
    'klytron:deploy:access_permissions', // Final ownership/permission sweep
    'deploy:unlock',                    // Remove deploy.lock
    'deploy:cleanup',                   // Delete old releases (keep_releases)
    'klytron:deploy:end_timer',         // Print elapsed time
])->desc('Deploy simple PHP site to production');

// ── Hooks ─────────────────────────────────────────────────────────────────────

after('deploy:shared', 'klytron:server:deploy:configs'); // Copy any server config files
