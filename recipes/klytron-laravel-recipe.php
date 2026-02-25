<?php

/**
 * Klytron Laravel Deployment Recipe
 *
 * All Laravel-specific tasks and logic are defined here. This file is intended to be included only for Laravel projects.
 *
 * @package Klytron\Deployer\Laravel
 */

namespace Deployer;

// Try to include Laravel recipe with proper path resolution
if (file_exists('vendor/deployer/deployer/recipe/laravel.php')) {
    require 'vendor/deployer/deployer/recipe/laravel.php';
} elseif (file_exists('recipe/laravel.php')) {
    require 'recipe/laravel.php';
} else {
    throw new \RuntimeException('Laravel recipe not found. Please ensure Deployer is properly installed.');
}

// Load core framework-agnostic tasks
require_once __DIR__ . '/../klytron-tasks.php';

// Import enhanced services (optional - will be checked at runtime)
use Klytron\PhpDeploymentKit\Services\RetryService;

///////////////////////////////////////////////////////////////////////////////
// LARAVEL-SPECIFIC CONFIGURATION
///////////////////////////////////////////////////////////////////////////////

// Set Laravel-specific defaults
// This ensures the framework-agnostic tasks have the right defaults for Laravel
if (!has('public_dir_path')) {
    set('public_dir_path', '{{deploy_path}}/current/public');
}
if (!has('shared_dir_path')) {
    set('shared_dir_path', '{{deploy_path}}/shared');
}

///////////////////////////////////////////////////////////////////////////////
// LARAVEL-SPECIFIC TASKS AND GROUPS
///////////////////////////////////////////////////////////////////////////////

/**
 * Interactive configuration for Laravel deployments with automation support
 *
 * Supports automated deployment configuration through these variables:
 * - auto_confirm_production: true/false/null (skip production confirmation)
 * - auto_deployment_type: 'update'/'fresh'/null (deployment type)
 * - auto_upload_env: true/false/null (upload .env file)
 * - auto_database_operation: 'migrations'/'import'/'both'/'none'/null (database operation)
 * - auto_clear_caches: true/false/null (clear caches)
 * - auto_confirm_settings: true/false/null (skip final confirmation)
 */
task('klytron:laravel:init:questions', function () {
    info("=== Deployment Configuration Setup ===");

    // Get environment information for context
    $appEnv = klytron_getEnvValue('APP_ENV', null, false) ?? 'unknown';
    $appName = klytron_getEnvValue('APP_NAME', null, false) ?? 'Laravel App';

    info("🚀 Application: $appName");
    info("🌍 Environment: $appEnv");
    info("");

    // 1. Confirm deployment to production (with automation support)
    $autoConfirm = get('auto_confirm_production', null);
    if ($autoConfirm !== null) {
        $deployToProduction = $autoConfirm;
        info("🤖 Auto-confirming production deployment: " . ($deployToProduction ? 'YES' : 'NO'));
    } else {
        $deployToProduction = askConfirmation("Are you sure you want to deploy to production?", true);
    }

    if (!$deployToProduction) {
        error("Deployment cancelled by user.");
        throw new \RuntimeException("Deployment cancelled by user.");
    }

    // 2. Check if current deployment exists and ask about deployment type (with automation support)
    $currentExists = test('[ -d "{{deploy_path}}/current" ]');

    if ($currentExists) {
        info("📁 Current deployment found at: {{deploy_path}}/current");

        $autoType = get('auto_deployment_type', null);
        if ($autoType !== null) {
            $deploymentType = $autoType;
            info("🤖 Auto-selecting deployment type: $deploymentType");
        } else {
            $deploymentType = askChoice("What type of deployment do you want to perform?", [
                'update' => 'Update existing deployment',
                'fresh' => 'Fresh installation (will replace current)'
            ], 'update');
        }
        $isUpdate = ($deploymentType === 'update');
    } else {
        info("🆆 No current release found - this is a fresh installation");
        $isUpdate = false;
    }

    // 3. Configure based on deployment type
    if ($isUpdate) {
        //===========================================
        // UPDATE DEPLOYMENT
        //===========================================
        info("📋 Deployment Type: UPDATE EXISTING DEPLOYMENT");
        info("🔄 Updating existing installation");
        info("");

        // Ask about backup
        $shouldBackup = askConfirmation("Create backup before deployment?", true);
        set('shouldBackupBeforeDeployment', $shouldBackup);

        // Enhanced database questions for MySQL/MariaDB (with automation support)
        $databaseType = get('database_type', 'mysql');
        if (in_array($databaseType, ['mysql', 'mariadb'])) {
            $autoDbOp = get('auto_database_operation', null);
            if ($autoDbOp !== null) {
                $dbAction = $autoDbOp;
                info("🤖 Auto-selecting database operation: $dbAction");
            } else {
                $dbAction = askChoice("What database action do you want to perform?", [
                    'migrations' => 'Run migrations only',
                    'import' => 'Import database from SQL file only',
                    'both' => 'Import database AND run migrations',
                    'none' => 'Skip database operations'
                ], 'migrations');
            }

            set('shouldRunMigration', in_array($dbAction, ['migrations', 'both']));
            set('shouldImportDbFile', in_array($dbAction, ['import', 'both']));
        } else {
            $autoDbOp = get('auto_database_operation', null);
            if ($autoDbOp !== null) {
                $shouldRunMigration = ($autoDbOp === 'migrations' || $autoDbOp === 'both');
                info("🤖 Auto-setting migration: " . ($shouldRunMigration ? 'YES' : 'NO'));
            } else {
                $shouldRunMigration = askConfirmation("Run database migrations?", false);
            }
            set('shouldRunMigration', $shouldRunMigration);
            set('shouldImportDbFile', false);
        }

        // Ask about Node.js/Vite build (only if project supports it)
        if (get('supports_nodejs', false)) {
            $shouldBuildAssets = askConfirmation("Build frontend assets with Vite?", true);
            set('shouldBuildAssets', $shouldBuildAssets);
        } else {
            set('shouldBuildAssets', false);
        }

        // Ask about clearing caches (with automation support)
        $autoClearCaches = get('auto_clear_caches', null);
        if ($autoClearCaches !== null) {
            $shouldClearAllCaches = $autoClearCaches;
            info("🤖 Auto-setting clear caches: " . ($shouldClearAllCaches ? 'YES' : 'NO'));
        } else {
            $shouldClearAllCaches = askConfirmation("Clear all caches after deployment?", true);
        }
        set('shouldClearAllCaches', $shouldClearAllCaches);

        // Ask about Passport installation (only if project supports it)
        if (get('supports_passport', false)) {
            $shouldRunPassportInstall = askConfirmation("Install/Update Laravel Passport?", false);
            set('shouldRunPassportInstall', $shouldRunPassportInstall);
        } else {
            set('shouldRunPassportInstall', false);
        }

        // Set other defaults for updates
        set('shouldImportDbFile', false);
        set('shouldUploadEnvFile', false);

    } else {
        //===========================================
        // FRESH INSTALLATION
        //===========================================
        info("📋 Deployment Type: FRESH PROJECT INSTALLATION");
        info("🆆 No current release found - this is a fresh installation");
        info("");

        // For fresh installations, we need more configuration (with automation support)
        $envFileLocal = get('env_file_local', '.env.production');

        $autoUploadEnv = get('auto_upload_env', null);
        if ($autoUploadEnv !== null) {
            $shouldUploadEnvFile = $autoUploadEnv;
            info("🤖 Auto-setting upload env file: " . ($shouldUploadEnvFile ? 'YES' : 'NO'));
        } else {
            $shouldUploadEnvFile = askConfirmation("Upload {$envFileLocal} file?", true);
        }
        set('shouldUploadEnvFile', $shouldUploadEnvFile);

        // Enhanced database questions for fresh installation (with automation support)
        $databaseType = get('database_type', 'mysql');
        if (in_array($databaseType, ['mysql', 'mariadb'])) {
            $autoDbOp = get('auto_database_operation', null);
            if ($autoDbOp !== null) {
                $dbAction = $autoDbOp;
                info("🤖 Auto-selecting database operation: $dbAction");
            } else {
                $dbAction = askChoice("What database setup do you want to perform?", [
                    'migrations' => 'Run migrations only (empty database)',
                    'import' => 'Import database from SQL file only',
                    'both' => 'Import database AND run migrations',
                    'none' => 'Skip database operations'
                ], 'migrations');
            }

            set('shouldRunMigration', in_array($dbAction, ['migrations', 'both']));
            set('shouldImportDbFile', in_array($dbAction, ['import', 'both']));
        } else if ($databaseType !== 'none') {
            // Handle SQLite databases differently
            if ($databaseType === 'sqlite') {
                info("🗄️  SQLite database detected - database import not applicable");
                set('shouldImportDbFile', false);
                
                $autoDbOp = get('auto_database_operation', null);
                if ($autoDbOp !== null) {
                    $shouldRunMigration = ($autoDbOp === 'migrations' || $autoDbOp === 'both');
                    info("🤖 Auto-setting SQLite migration: " . ($shouldRunMigration ? 'YES' : 'NO'));
                } else {
                    $shouldRunMigration = askConfirmation("Run SQLite database migrations?", true);
                }
                set('shouldRunMigration', $shouldRunMigration);
            } else {
                $shouldImportDbFile = askConfirmation("Import database from SQL file?", false);
                set('shouldImportDbFile', $shouldImportDbFile);

                $shouldRunMigration = askConfirmation("Run database migrations?", true);
                set('shouldRunMigration', $shouldRunMigration);
            }
        } else {
            set('shouldImportDbFile', false);
            set('shouldRunMigration', false);
        }

        // Ask about Node.js/Vite build (only if project supports it)
        if (get('supports_nodejs', false)) {
            $shouldBuildAssets = askConfirmation("Build frontend assets with Vite?", true);
            set('shouldBuildAssets', $shouldBuildAssets);
        } else {
            set('shouldBuildAssets', false);
        }

        // Only ask about Passport if project supports it
        if (get('supports_passport', false)) {
            $shouldRunPassportInstall = askConfirmation("Install Laravel Passport?", false);
            set('shouldRunPassportInstall', $shouldRunPassportInstall);
        } else {
            set('shouldRunPassportInstall', false);
        }

        // Ask about clearing caches (with automation support)
        $autoClearCaches = get('auto_clear_caches', null);
        if ($autoClearCaches !== null) {
            $shouldClearAllCaches = $autoClearCaches;
            info("🤖 Auto-setting clear caches: " . ($shouldClearAllCaches ? 'YES' : 'NO'));
        } else {
            $shouldClearAllCaches = askConfirmation("Clear all caches after deployment?", true);
        }
        set('shouldClearAllCaches', $shouldClearAllCaches);

        // Set defaults for fresh installations
        set('shouldBackupBeforeDeployment', false); // No need to backup on fresh install
    }

    info("");
    info("========================================");
    info("📋 DEPLOYMENT CONFIGURATION SUMMARY");
    info("========================================");
    info("Deployment Type: " . ($isUpdate ? "🔄 UPDATE" : "🆆 FRESH INSTALL"));
    info("Upload .env File: " . (get('shouldUploadEnvFile') ? "📄 YES" : "❌ NO"));
    info("Create Backup: " . (get('shouldBackupBeforeDeployment') ? "💾 YES" : "❌ NO"));
    // Show database-specific information
    $databaseType = get('database_type', 'mysql');
    if ($databaseType === 'sqlite') {
        info("Database Type: 🗄️ SQLite");
        info("Import Database: ❌ N/A (SQLite is file-based)");
        info("Run Migrations: " . (get('shouldRunMigration') ? "🚀 YES" : "❌ NO"));
    } else {
        info("Database Type: 🗄️ " . strtoupper($databaseType));
        info("Import Database: " . (get('shouldImportDbFile') ? "📊 YES" : "❌ NO"));
        info("Run Migrations: " . (get('shouldRunMigration') ? "🚀 YES" : "❌ NO"));
    }
    info("Build Assets: " . (get('shouldBuildAssets') ? "⚡ YES" : "❌ NO"));
    info("Install Passport: " . (get('shouldRunPassportInstall') ? "🔐 YES" : "❌ NO"));
    info("Clear Caches: " . (get('shouldClearAllCaches') ? "🧹 YES" : "❌ NO"));
    info("==========================================");

    // Final confirmation (with automation support)
    $autoConfirmSettings = get('auto_confirm_settings', null);
    if ($autoConfirmSettings !== null) {
        $confirmDeployment = $autoConfirmSettings;
        info("🤖 Auto-confirming deployment settings: " . ($confirmDeployment ? 'YES' : 'NO'));
    } else {
        $confirmDeployment = askConfirmation("Proceed with deployment using these settings?", true);
    }

    if (!$confirmDeployment) {
        error("Deployment cancelled by user.");
        throw new \RuntimeException("Deployment cancelled by user.");
    }

    info("✅ Deployment configuration confirmed. Proceeding...");
})->desc('Interactive deployment configuration with automation support');

/**
 * Validate environment configuration locally before deployment
 */
task('klytron:laravel:validate:environment:local', function () {
    writeln("🔍 <info>Validating local environment configuration...</info>");

    // Define critical environment variables
    $criticalVars = [
        'APP_NAME',
        'APP_ENV',
        'APP_KEY',
        'APP_URL',
        'DB_CONNECTION',
        'DB_HOST',
        'DB_PORT',
        'DB_DATABASE',
        'DB_USERNAME'
    ];

    $envFile = get('env_file_local', '.env.production');
    $missingVars = [];
    $foundVars = [];

    // Check if env file exists locally
    if (!file_exists($envFile)) {
        throw new \RuntimeException("Environment file not found: {$envFile}");
    }

    foreach ($criticalVars as $var) {
        $value = klytron_getEnvValue($var, $envFile, false);
        if ($value === null || $value === '') {
            $missingVars[] = $var;
        } else {
            $foundVars[] = $var;
        }
    }

    if (!empty($foundVars)) {
        writeln("✅ <info>Found " . count($foundVars) . " critical environment variables:</info>");
        foreach ($foundVars as $var) {
            $value = klytron_getEnvValue($var, $envFile, false);
            $displayValue = (strlen($value) > 30) ? substr($value, 0, 30) . '...' : $value;
            if (str_contains($var, 'KEY') || str_contains($var, 'PASSWORD')) {
                $displayValue = '[HIDDEN]';
            }
            writeln("   ✓ <comment>$var</comment> = $displayValue");
        }
    }

    if (!empty($missingVars)) {
        writeln("⚠️  <fg=yellow>Missing " . count($missingVars) . " critical environment variables:</fg=yellow>");
        foreach ($missingVars as $var) {
            writeln("   ✗ <fg=red>$var</fg=red>");
        }
        writeln("📝 <comment>Please ensure these variables are set in $envFile</comment>");

        if (!askConfirmation("Continue deployment despite missing environment variables?", false)) {
            throw new \RuntimeException("Deployment cancelled due to missing environment variables.");
        }
    }

    writeln("✅ <info>Local environment validation completed</info>");
})->desc('Validate environment configuration locally');

/**
 * Validate environment configuration for Laravel (runs on remote server after env upload)
 */
task('klytron:laravel:validate:environment', function () {
    info("🔍 Validating environment configuration...");

    // Define critical environment variables
    $criticalVars = [
        'APP_NAME',
        'APP_ENV',
        'APP_KEY',
        'APP_URL',
        'DB_CONNECTION',
        'DB_HOST',
        'DB_PORT',
        'DB_DATABASE',
        'DB_USERNAME'
    ];

    $envFile = get('env_file_remote', '.env');
    $missingVars = [];
    $foundVars = [];

    // Check if env file exists on remote server
    if (!test("[ -f {{deploy_path}}/shared/{$envFile} ]")) {
        warning("⚠️  Environment file not found on remote server: {{deploy_path}}/shared/{$envFile}");
        return;
    }

    foreach ($criticalVars as $var) {
        // Read from remote env file
        $value = run("grep '^{$var}=' {{deploy_path}}/shared/{$envFile} | cut -d'=' -f2- | sed 's/^[\"'\'']//;s/[\"'\'']$//' || echo ''");
        if (empty(trim($value))) {
            $missingVars[] = $var;
        } else {
            $foundVars[] = $var;
        }
    }

    // Report findings
    if (!empty($foundVars)) {
        info("✅ Found " . count($foundVars) . " critical environment variables:");
        foreach ($foundVars as $var) {
            $value = klytron_getEnvValue($var, $envFile, false);
            $displayValue = (strlen($value) > 50) ? substr($value, 0, 47) . '...' : $value;
            if (strpos($var, 'PASSWORD') !== false || strpos($var, 'SECRET') !== false || strpos($var, 'KEY') !== false) {
                $displayValue = '[HIDDEN]';
            }
            info("   ✓ $var = $displayValue");
        }
    }

    if (!empty($missingVars)) {
        warning("⚠️  Missing " . count($missingVars) . " critical environment variables:");
        foreach ($missingVars as $var) {
            warning("   ✗ $var");
        }
        warning("📝 Please ensure these variables are set in $envFile");

        $continueAnyway = askConfirmation("Continue deployment despite missing environment variables?", false);
        if (!$continueAnyway) {
            throw new \RuntimeException("Deployment cancelled due to missing environment variables.");
        }
    }

    info("✅ Environment validation completed");
})->desc('Validate environment configuration');

/**
 * Conditional Node.js asset building
 */
task('klytron:laravel:node:build:conditional', function () {
    if (get('shouldBuildAssets', false)) {
        info("⚡ Building frontend assets with Vite...");
        invoke('klytron:laravel:node:vite:build:local');
    } else {
        info("⏭️  Skipping frontend asset build (not enabled)");
    }
})->desc('Conditionally build frontend assets based on configuration');

/**
 * Deployment confirmation task
 */
task('klytron:laravel:deploy:confirm', [
    'klytron:deploy:confirm'
])->desc('Laravel deployment confirmation');

task('klytron:laravel:validate:domain', [
    'klytron:validate:domain'
])->desc('Laravel domain validation');

// Laravel-specific domain validation extension
task('klytron:laravel:validate:domain:interactive', function () {
    // Run the base domain validation first
    invoke('klytron:validate:domain');

    // Add Laravel-specific interactive features
    $publicHtml = get('application_public_html');
    if (!empty($publicHtml)) {
        // Validate that the public HTML directory exists with interactive creation
        if (!test("[ -d '$publicHtml' ]")) {
            warning("⚠️  Public HTML directory does not exist: $publicHtml");
            $createDir = askConfirmation("Create the directory?", true);
            if ($createDir) {
                run("mkdir -p '$publicHtml'");
                info("✅ Created public HTML directory: $publicHtml");
            }
        }
    }
})->desc('Laravel domain validation with interactive features');

task('klytron:laravel:deploy:backup:create', function () {
    // Set Laravel-specific database configuration from .env file
    set('database_type', klytron_getEnvValue('DB_CONNECTION', '.env.production', 'mysql'));
    set('db_host', klytron_getEnvValue('DB_HOST', '.env.production', 'localhost'));
    set('db_port', klytron_getEnvValue('DB_PORT', '.env.production', '3306'));
    set('db_name', klytron_getEnvValue('DB_DATABASE', '.env.production'));
    set('db_user', klytron_getEnvValue('DB_USERNAME', '.env.production'));
    set('db_pass', klytron_getEnvValue('DB_PASSWORD', '.env.production'));

    // Call the framework-agnostic backup task
    invoke('klytron:deploy:backup:create');

    // Laravel-specific backup cleanup (keep only last 5)
    $backupDir = get('deploy_path') . '/backups';
    if (test("[ -d '$backupDir' ]")) {
        $cleanupCmd = "cd '$backupDir' && ls -t | tail -n +6 | xargs -r rm -rf";
        run($cleanupCmd);
        info("🧹 Cleaned up old backups (keeping 5 most recent)");
    }
})->desc('Creates Laravel backup before deployment with cleanup');

// Local runner for executing artisan commands before deployment.
// Default to 'ddev php' since this workflow assumes DDEV locally.
set('local_artisan_runner', function () {
    return getenv('KLYTRON_LOCAL_ARTISAN_RUNNER') ?: 'ddev php';
});

task('klytron:laravel:local:env:ensure_decrypted', function () {
    $envs = get('env_encryption_environments', []);
    if (!is_array($envs) || empty($envs)) {
        return;
    }

    // Check if any plain env files already exist - if so, no decryption needed
    $needsDecryption = false;
    foreach ($envs as $env) {
        $plainFile = $env === 'production' ? '.env.production' : '.env';
        if (!file_exists($plainFile)) {
            $needsDecryption = true;
            break;
        }
    }

    // If all plain files exist, skip decryption entirely
    if (!$needsDecryption) {
        info("⏭️  Skipping env decryption - plain env files already exist");
        return;
    }

    // Only require key if decryption is actually needed
    $key = getenv('LARAVEL_ENV_ENCRYPTION_KEY');
    if (empty($key)) {
        warning("⚠️  LARAVEL_ENV_ENCRYPTION_KEY not set, but plain env files missing. Attempting to continue without decryption.");
        return;
    }

    $runner = trim((string) get('local_artisan_runner', 'ddev php'));
    if ($runner === '') {
        $runner = 'php';
    }

    foreach ($envs as $env) {
        $plainFile = $env === 'production' ? '.env.production' : '.env';
        $encryptedFile = $env === 'production' ? '.env.production.encrypted' : '.env.encrypted';

        if (file_exists($plainFile)) {
            continue;
        }

        if (!file_exists($encryptedFile)) {
            warning("⚠️  Missing env file '{$plainFile}' - deployment may fail. Create it manually or provide encrypted version.");
            continue;
        }

        info("🔓 Decrypting {$encryptedFile} -> {$plainFile} (local)");

        $artisanArgs = $env === 'production'
            ? 'artisan env:decrypt --env=production --force'
            : 'artisan env:decrypt --force';

        $cmd = "LARAVEL_ENV_ENCRYPTION_KEY=" . escapeshellarg($key) . " {$runner} {$artisanArgs}";

        $output = [];
        $exitCode = 0;
        exec($cmd . ' 2>&1', $output, $exitCode);

        if ($exitCode !== 0 || !file_exists($plainFile)) {
            $details = !empty($output) ? "\n" . implode("\n", $output) : '';
            throw new \RuntimeException("Failed to decrypt {$encryptedFile} into {$plainFile}.{$details}");
        }
    }
})->desc('Ensure plaintext env files exist locally by decrypting from encrypted versions before deployment');

/**
 * Conditional Node.js asset building
 */
task('klytron:laravel:node:vite:build', function () {
    if (get('shouldBuildAssets', false)) {
        info("⚡ Building frontend assets with Vite...");
        invoke('klytron:laravel:node:vite:build:local');
    } else {
        info("⏭️  Skipping frontend asset build (not enabled)");
    }
})->desc('Conditionally build frontend assets based on configuration');

/**
 * Deployment confirmation task
 */
task('klytron:laravel:deploy:confirm', [
    'klytron:deploy:confirm'
])->desc('Laravel deployment confirmation');

task('klytron:laravel:validate:domain', [
    'klytron:validate:domain'
])->desc('Laravel domain validation');

// Laravel-specific domain validation extension
task('klytron:laravel:validate:domain:interactive', function () {
    // Run the base domain validation first
    invoke('klytron:validate:domain');

    // Add Laravel-specific interactive features
    $publicHtml = get('application_public_html');
    if (!empty($publicHtml)) {
        // Validate that the public HTML directory exists with interactive creation
        if (!test("[ -d '$publicHtml' ]")) {
            warning("⚠️  Public HTML directory does not exist: $publicHtml");
            $createDir = askConfirmation("Create the directory?", true);
            if ($createDir) {
                run("mkdir -p '$publicHtml'");
                info("✅ Created public HTML directory: $publicHtml");
            }
        }
    }
})->desc('Laravel domain validation with interactive features');

task('klytron:laravel:deploy:backup:create', function () {
    // Set Laravel-specific database configuration from .env file
    set('database_type', klytron_getEnvValue('DB_CONNECTION', '.env.production', 'mysql'));
    set('db_host', klytron_getEnvValue('DB_HOST', '.env.production', 'localhost'));
    set('db_port', klytron_getEnvValue('DB_PORT', '.env.production', '3306'));
    set('db_name', klytron_getEnvValue('DB_DATABASE', '.env.production'));
    set('db_user', klytron_getEnvValue('DB_USERNAME', '.env.production'));
    set('db_pass', klytron_getEnvValue('DB_PASSWORD', '.env.production'));

    // Call the framework-agnostic backup task
    invoke('klytron:deploy:backup:create');

    // Laravel-specific backup cleanup (keep only last 5)
    $backupDir = get('deploy_path') . '/backups';
    if (test("[ -d '$backupDir' ]")) {
        $cleanupCmd = "cd '$backupDir' && ls -t | tail -n +6 | xargs -r rm -rf";
        run($cleanupCmd);
        info("🧹 Cleaned up old backups (keeping 5 most recent)");
    }
})->desc('Creates Laravel backup before deployment with cleanup');

task('klytron:laravel:node:vite:build', function () {
    // Only run if project supports Vite/asset building
    if (!get('supports_vite', true)) {
        info("⏭️ Asset building not supported for this project");
        return;
    }

    cd('{{release_path}}');

    // Detect Node.js and npm availability with fallbacks (node, nodejs, NVM)
    $nodeBinary = 'node';
    $npmBinary = 'npm';
    $nodeEnvPrefix = '';

    // Helper to check command availability
    $hasNode = test('command -v node >/dev/null 2>&1');
    $hasNodeJs = $hasNode ? true : test('command -v nodejs >/dev/null 2>&1');
    $hasNpm = test('command -v npm >/dev/null 2>&1');
    $nodeFound = false;

    if ($hasNode) {
        $nodeBinary = 'node';
        $nodeFound = true;
    } elseif ($hasNodeJs) {
        $nodeBinary = 'nodejs';
        $nodeFound = true;
    } else {
        // Try to activate Node via NVM if available
        $nvmAvailable = test('[ -s "$HOME/.nvm/nvm.sh" ]');
        if ($nvmAvailable) {
            $hasNvmrc = test("[ -f '{{release_path}}/.nvmrc' ]");
            $nvmUse = $hasNvmrc
                ? 'nvm install >/dev/null 2>&1 && nvm use >/dev/null 2>&1'
                : 'nvm use --lts >/dev/null 2>&1 || (nvm install --lts >/dev/null 2>&1 && nvm use --lts >/dev/null 2>&1)';
            $nodeEnvPrefix = 'export NVM_DIR="$HOME/.nvm"; '
                . '[ -s "$NVM_DIR/nvm.sh" ] && . "$NVM_DIR/nvm.sh"; '
                . $nvmUse . '; ';

            // After NVM activation, verify node is available
            try {
                run($nodeEnvPrefix . 'node --version');
                $nodeBinary = 'node';
                $hasNpm = true; // npm comes with Node under NVM
                $nodeFound = true;
            } catch (\Exception $e) {
                $nodeFound = false;
            }
        }
    }

    // Ensure npm is available unless using NVM prefix which will provide it at runtime
    if (!$hasNpm && $nodeEnvPrefix === '') {
        $nodeFound = false;
    }

    // If project doesn't support Node.js, skip build entirely
    if (!get('supports_nodejs', true)) {
        info("⏭️  Node.js support is disabled for this project");
        return;
    }

    // If Node.js is not found on server, fallback to local build
    if (!$nodeFound) {
        warning("⚠️ Node.js or npm is not installed on the remote server.");
        info("🔄 Switching to local build strategy...");
        
        invoke('klytron:laravel:node:vite:build:local');
        return;
    }

    info('🔍 Node.js version: ' . run($nodeEnvPrefix . $nodeBinary . ' --version'));
    info("📦 Installing Node.js dependencies...");
    // Resilient npm configuration: retries, cache, and registry fallback
    $npmCacheDir = get('npm_cache_dir', '{{deploy_path}}/.npm-cache');
    run("mkdir -p '$npmCacheDir'");
    $npmBaseEnv = 'PUPPETEER_SKIP_DOWNLOAD=1 PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=1 '
        . 'NPM_CONFIG_AUDIT=false NPM_CONFIG_FUND=false '
        . 'NPM_CONFIG_FETCH_RETRIES=5 NPM_CONFIG_FETCH_RETRY_FACTOR=2 '
        . 'NPM_CONFIG_FETCH_RETRY_MINTIMEOUT=20000 NPM_CONFIG_FETCH_RETRY_MAXTIMEOUT=120000 '
        . 'NPM_CONFIG_TIMEOUT=600000 NPM_CONFIG_PREFER_OFFLINE=true '
        . "NPM_CONFIG_CACHE='$npmCacheDir'";
    $primaryRegistry = get('npm_registry', 'https://registry.npmjs.org');
    $mirrorRegistry  = get('npm_registry_mirror', 'https://registry.npmmirror.com');
    $useCi = test("[ -f '{{release_path}}/package-lock.json' ]");
    $installCmd = $nodeEnvPrefix . ($useCi ? 'npm ci' : 'npm install');
    try {
        run("$npmBaseEnv NPM_CONFIG_REGISTRY='$primaryRegistry' $installCmd");
    } catch (\Exception $e) {
        warning('⚠️ npm install failed with primary registry, retrying with mirror...');
        run("$npmBaseEnv NPM_CONFIG_REGISTRY='$mirrorRegistry' $installCmd");
    }

    info("🏗️ Building production assets with Vite...");

    // Load required environment variables
    $envVars = [];
    $envPath = '{{release_path}}/.env';
    if (test("[ -f $envPath ]")) {
        $lines = explode("\n", run("cat $envPath"));
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false && !str_starts_with(trim($line), '#')) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                if (str_starts_with($key, 'MIX_') || str_starts_with($key, 'NODE_') || in_array($key, ['APP_URL', 'ASSET_URL'])) {
                    $envVars[] = "$key=".escapeshellarg(trim($value, "'\""));
                }
            }
        }
    }

    $envString = implode(' ', $envVars);
    run("$envString " . $nodeEnvPrefix . " npm run build");
    info("✅ Vite production build complete");
})->desc('Vite build with precise environment variables');

task('klytron:laravel:node:vite:build:local', function () {
    info("🏗️ Building frontend assets locally...");
    runLocally('npm ci || npm install');
    runLocally('npm run build');
    
    info("📤 Uploading built assets...");
    upload('public/build/', '{{release_path}}/public/build/');
    info("✅ Built assets uploaded");
})->desc('Build Vite assets locally and upload');

///////////////////////////////////////////////////////////////////////////////
// LARAVEL NODE.JS BUILD TASKS
///////////////////////////////////////////////////////////////////////////////

/**
 * Laravel Mix build task
 */
task('klytron:laravel:node:mix:build', function () {
    if (!get('supports_mix', true)) {
        info("⏭️ Laravel Mix asset building not supported for this project");
        return;
    }

    cd('{{release_path}}');

    // Check Node.js version for compatibility
    $nodeVersion = run('node --version');
    info("🔍 Node.js version: $nodeVersion");

    info("📦 Installing Node.js dependencies...");

    // Resilient npm configuration with retries, cache and registry fallback
    $npmCacheDir = get('npm_cache_dir', '{{deploy_path}}/.npm-cache');
    run("mkdir -p '$npmCacheDir'");
    $npmBaseEnv = 'PUPPETEER_SKIP_DOWNLOAD=1 PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=1 '
        . 'NPM_CONFIG_AUDIT=false NPM_CONFIG_FUND=false '
        . 'NPM_CONFIG_FETCH_RETRIES=5 NPM_CONFIG_FETCH_RETRY_FACTOR=2 '
        . 'NPM_CONFIG_FETCH_RETRY_MINTIMEOUT=20000 NPM_CONFIG_FETCH_RETRY_MAXTIMEOUT=120000 '
        . 'NPM_CONFIG_TIMEOUT=600000 NPM_CONFIG_PREFER_OFFLINE=true '
        . "NPM_CONFIG_CACHE='$npmCacheDir'";
    $primaryRegistry = get('npm_registry', 'https://registry.npmjs.org');
    $mirrorRegistry  = get('npm_registry_mirror', 'https://registry.npmmirror.com');
    $useCi = test("[ -f '{{release_path}}/package-lock.json' ]");
    $installCmd = $useCi ? 'npm ci' : 'npm install';
    info($useCi ? '📋 Found package-lock.json, using npm ci for clean install...' : '📋 No package-lock.json found, using npm install...');
    try {
        run("$npmBaseEnv NPM_CONFIG_REGISTRY='$primaryRegistry' $installCmd");
    } catch (\Exception $e) {
        warning('⚠️ npm install failed with primary registry, retrying with mirror...');
        run("$npmBaseEnv NPM_CONFIG_REGISTRY='$mirrorRegistry' $installCmd");
    }

    info("🏗️ Building production assets with Laravel Mix...");

    // Get environment variables for Mix (configurable via project settings)
    $envVars = [];
    $mixEnvVars = get('mix_env_vars', ['MIX_', 'NODE_', 'APP_URL', 'ASSET_URL']);

    $envPath = '{{release_path}}/.env';
    if (test("[ -f $envPath ]")) {
        $lines = explode("\n", run("cat $envPath"));
        foreach ($lines as $line) {
            $line = trim($line);
            if (strpos($line, '=') !== false && !str_starts_with($line, '#') && !empty($line)) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Remove surrounding quotes if present
                if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                    (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                    $value = substr($value, 1, -1);
                }

                // Check if key matches any configured patterns
                $shouldInclude = false;
                foreach ($mixEnvVars as $pattern) {
                    if (str_ends_with($pattern, '_')) {
                        // Pattern like 'MIX_' - check prefix
                        if (str_starts_with($key, $pattern)) {
                            $shouldInclude = true;
                            break;
                        }
                    } else {
                        // Exact match
                        if ($key === $pattern) {
                            $shouldInclude = true;
                            break;
                        }
                    }
                }

                if ($shouldInclude) {
                    $envVars[] = "$key=" . escapeshellarg($value);
                }
            }
        }
    }

    // Build with Node.js compatibility and environment variables
    $buildCommand = get('mix_build_command', 'npm run production');
    $nodeOptions = 'NODE_OPTIONS="--openssl-legacy-provider"';
    $envString = $nodeOptions . ' ' . implode(' ', $envVars);

    try {
        run("$envString $buildCommand");
        info("✅ Laravel Mix production build complete");
    } catch (\Exception $e) {
        warning("⚠️ Mix build failed with legacy provider, trying without...");
        // Fallback: try without the legacy provider
        $envString = implode(' ', $envVars);
        run("$envString $buildCommand");
        info("✅ Laravel Mix production build complete (fallback method)");
    }
})->desc('Laravel Mix build with environment variables and Node.js compatibility');

///////////////////////////////////////////////////////////////////////////////
// GENERIC LARAVEL DEPLOYMENT TASKS
///////////////////////////////////////////////////////////////////////////////

/**
 * @deprecated Use klytron:server:deploy:configs instead
 * This is kept for backward compatibility
 */
task('klytron:laravel:deploy:htaccess', ['klytron:server:deploy:configs']);

/**
 * Generic web assets permissions fixer
 * Fixes permissions for fonts, CSS, JS, and other web assets
 */
task('klytron:laravel:fix:assets:permissions', function () {
    info("🔧 Fixing web assets permissions...");

    $assetsDir = get('assets_directory', 'public/web-assets');
    $assetsPath = "{{release_path}}/$assetsDir";

    // Check if web-assets directory exists
    if (test("[ -d '$assetsPath' ]")) {
        info("📁 Web assets directory found, fixing permissions...");

        // Font files permissions
        if (test("[ -d '$assetsPath/fonts' ]")) {
            info("🔤 Fixing font file permissions...");
            run("find $assetsPath/fonts -type f -name '*.woff*' -exec chmod 644 {} \\; 2>/dev/null || true");
            run("find $assetsPath/fonts -type f -name '*.ttf' -exec chmod 644 {} \\; 2>/dev/null || true");
            run("find $assetsPath/fonts -type f -name '*.eot' -exec chmod 644 {} \\; 2>/dev/null || true");
            run("find $assetsPath/fonts -type f -name '*.svg' -exec chmod 644 {} \\; 2>/dev/null || true");
            run("find $assetsPath/fonts -type d -exec chmod 755 {} \\; 2>/dev/null || true");
            info("✅ Font file permissions fixed.");
        } else {
            info("⏭️ Fonts directory not found, skipping font permissions.");
        }

        // CSS files permissions
        if (test("[ -d '$assetsPath/css' ]")) {
            info("🎨 Fixing CSS file permissions...");
            run("find $assetsPath/css -type f -name '*.css' -exec chmod 644 {} \\; 2>/dev/null || true");
            info("✅ CSS file permissions fixed.");
        } else {
            info("⏭️ CSS directory not found, skipping CSS permissions.");
        }

        // JavaScript files permissions
        if (test("[ -d '$assetsPath/js' ]")) {
            info("📜 Fixing JavaScript file permissions...");
            run("find $assetsPath/js -type f -name '*.js' -exec chmod 644 {} \\; 2>/dev/null || true");
            info("✅ JavaScript file permissions fixed.");
        } else {
            info("⏭️ JavaScript directory not found, skipping JS permissions.");
        }

        // Image files permissions
        if (test("[ -d '$assetsPath/images' ]")) {
            info("🖼️ Fixing image file permissions...");
            run("find $assetsPath/images -type f \\( -name '*.jpg' -o -name '*.jpeg' -o -name '*.png' -o -name '*.gif' -o -name '*.svg' -o -name '*.webp' \\) -exec chmod 644 {} \\; 2>/dev/null || true");
            run("find $assetsPath/images -type d -exec chmod 755 {} \\; 2>/dev/null || true");
            info("✅ Image file permissions fixed.");
        } else {
            info("⏭️ Images directory not found, skipping image permissions.");
        }

    } else {
        info("⏭️ Web assets directory not found at $assetsDir, skipping asset permissions.");
    }

    info("✅ Web assets permissions task completed.");
})->desc('Fix permissions for web assets (fonts, CSS, JS, images)');

task('klytron:laravel:deploy:db:migrate', function () {
    $shouldRunMigration = get('shouldRunMigration', false);
    if (!$shouldRunMigration) {
        info("⏭️ Database migrations skipped (not requested)");
        return;
    }

    info("🚀 Running database migrations...");

    // Detect database type from project configuration
    $databaseType = get('database_type', 'mysql'); // Default to mysql if not specified
    
    // Check if this is a SQLite project
    if ($databaseType === 'sqlite') {
        info("🗄️  Detected SQLite database - setting up SQLite-specific migration...");
        
        try {
            // First, ensure SQLite database file exists
            info("📁 Setting up SQLite database file...");
            run('cd {{release_path}} && {{bin/php}} artisan klytron:sqlite:setup --force');
            info("✅ SQLite database file created/verified");
            
            // Run migrations with force flag for production
            info("🔄 Running SQLite migrations...");
            run('cd {{release_path}} && {{bin/php}} artisan migrate --force');
            info("✅ SQLite database migrations completed successfully");
            
        } catch (\Exception $e) {
            error("❌ SQLite database migration failed: " . $e->getMessage());
            
            // For SQLite, we can be more lenient since it's file-based
            $continueAnyway = askConfirmation("Continue deployment despite SQLite migration failure?", true);
            if (!$continueAnyway) {
                throw new \RuntimeException("Deployment cancelled due to SQLite migration failure.");
            }
            warning("⚠️  Continuing deployment despite SQLite migration failure");
        }
    } else {
        // Handle other database types (MySQL, PostgreSQL, etc.)
        try {
            // Run migrations with force flag for production
            run('cd {{release_path}} && {{bin/php}} artisan migrate --force');
            info("✅ Database migrations completed successfully");
        } catch (\Exception $e) {
            error("❌ Database migration failed: " . $e->getMessage());

            $continueAnyway = askConfirmation("Continue deployment despite migration failure?", false);
            if (!$continueAnyway) {
                throw new \RuntimeException("Deployment cancelled due to migration failure.");
            }
            warning("⚠️  Continuing deployment despite migration failure");
        }
    }
})->desc('Run database migrations with SQLite support');

task('klytron:laravel:deploy:db:import', function () {
    // Only run if shouldImportDbFile is set to true
    if (!get('shouldImportDbFile', false)) {
        info("⏭️ Database import skipped (not requested)");
        return;
    }

    // Detect database type from project configuration
    $databaseType = get('database_type', 'mysql'); // Default to mysql if not specified
    
    // For SQLite projects, database import is not applicable
    if ($databaseType === 'sqlite') {
        info("🗄️  SQLite database detected - skipping database import (not applicable for SQLite)");
        info("💡 SQLite databases are file-based and don't require external imports");
        return;
    }

    info("📊 Importing database from SQL file...");

    // Ensure service providers are discovered for Klytron commands
    info("🔄 Refreshing service provider discovery...");
    run('cd {{release_path}} && {{bin/php}} artisan package:discover --ansi');

    // Get configurable database import path
    $dbImportPath = get('db_import_path', 'database/live-db-exports');

    // Prefer time-sorted discovery: newest files first
    $sqlFiles = [];
    $releasePath = run("echo {{release_path}}");
    $sortedFound = run("find {{release_path}}/{$dbImportPath} -type f \\( -name '*.sql' -o -name '*.sql.encrypted' \\) -printf '%T@ %p\\n' 2>/dev/null | sort -nr | awk '{\\$1=\"\"; sub(/^ /, \"\"); print}' | head -n 10 || true");
    if (!empty(trim($sortedFound))) {
        foreach (array_filter(explode("\n", $sortedFound)) as $absPath) {
            $relativePath = str_replace($releasePath . "/", "", trim($absPath));
            if ($relativePath !== '') {
                $sqlFiles[] = $relativePath;
            }
        }
    }

    // Fallback: known filenames, then unsorted generic discovery
    if (empty($sqlFiles)) {
        $possibleFiles = ['database.sql', 'db.sql', 'dump.sql', 'backup.sql'];
        foreach ($possibleFiles as $file) {
            if (test("[ -f {{release_path}}/{$dbImportPath}/$file ]")) {
                $sqlFiles[] = "{$dbImportPath}/$file";
            }
            if (test("[ -f {{release_path}}/{$dbImportPath}/$file.encrypted ]")) {
                $sqlFiles[] = "{$dbImportPath}/$file.encrypted";
            }
        }

        $foundFiles = run("find {{release_path}}/{$dbImportPath} -type f \\( -name '*.sql' -o -name '*.sql.encrypted' \\) 2>/dev/null | head -n 10 || echo ''");
        if (!empty($foundFiles)) {
            $additionalFiles = array_filter(explode("\n", $foundFiles));
            foreach ($additionalFiles as $file) {
                $relativePath = str_replace($releasePath . "/", "", $file);
                if (!in_array($relativePath, $sqlFiles)) {
                    $sqlFiles[] = $relativePath;
                }
            }
        }
    }

    if (empty($sqlFiles)) {
        warning("⚠️  No SQL files found in {$dbImportPath} directory. Looking for: " . implode(', ', $possibleFiles));
        return;
    }

    // If multiple files found, show candidates (newest first)
    $selectedFile = null;
    $selectionReason = '';
    if (count($sqlFiles) === 1) {
        $selectedFile = $sqlFiles[0];
        $selectionReason = 'single candidate found';
    } else {
        info("Found multiple SQL files:");
        foreach ($sqlFiles as $index => $file) {
            info("  " . ($index + 1) . ". $file");
        }

        // Try to select by datetime embedded in filename first
        $bestByName = null;
        $bestTs = null;
        foreach ($sqlFiles as $file) {
            $base = basename($file);
            $tsCandidate = null;

            // Match patterns like 20250817_075527 or 20250817075527
            if (preg_match('/(\d{8})[_-]?(\d{6})/', $base, $m)) {
                $date = $m[1];
                $time = $m[2];
                $dt = \DateTime::createFromFormat('Ymd His', $date . ' ' . $time);
                if ($dt !== false) {
                    $tsCandidate = (int)$dt->format('U');
                }
            } elseif (preg_match('/(\d{14})/', $base, $m)) {
                $dt = \DateTime::createFromFormat('YmdHis', $m[1]);
                if ($dt !== false) {
                    $tsCandidate = (int)$dt->format('U');
                }
            }

            if ($tsCandidate !== null) {
                if ($bestTs === null || $tsCandidate > $bestTs) {
                    $bestTs = $tsCandidate;
                    $bestByName = $file;
                }
            }
        }

        if ($bestByName !== null) {
            $selectedFile = $bestByName;
            $selectionReason = 'based on datetime in filename';
        } else {
            // Fallback to newest by modification time (list already sorted newest-first)
            $selectedFile = $sqlFiles[0];
            $selectionReason = 'fallback to newest by file modification time';
        }
    }

    info("Using: $selectedFile ($selectionReason)");

    $sqlFilePath = "{{release_path}}/$selectedFile";
    $isEncrypted = strpos($selectedFile, '.encrypted') !== false;

    // Handle encrypted files
    if ($isEncrypted) {
        info("🔐 Decrypting SQL file...");
        $decryptedFilePath = str_replace('.encrypted', '', $sqlFilePath);

        // Try Klytron Deployer's command first, fallback to backward compatible command
        try {
            run("cd {{release_path}} && php artisan klytron:file:decrypt \"$sqlFilePath\"");
            info("✅ SQL file decrypted using klytron:file:decrypt");
        } catch (\Exception $e) {
            info("⚠️  klytron:file:decrypt not available, trying backward compatible command...");
            // Fallback: try without the legacy provider
            run("cd {{release_path}} && php artisan file:decrypt \"$sqlFilePath\"");
            info("✅ SQL file decrypted using file:decrypt (backward compatible)");
        }

        $sqlFilePath = $decryptedFilePath;
    }

    // Get database connection details
    $dbHost = klytron_getEnvValue('DB_HOST', '.env.production', 'localhost');
    $dbPort = klytron_getEnvValue('DB_PORT', '.env.production', '3306');
    $dbDatabase = klytron_getEnvValue('DB_DATABASE', '.env.production');
    $dbUsername = klytron_getEnvValue('DB_USERNAME', '.env.production');
    $dbPassword = klytron_getEnvValue('DB_PASSWORD', '.env.production');

    if (!$dbDatabase || !$dbUsername) {
        throw new \RuntimeException("Database configuration incomplete. Check DB_DATABASE and DB_USERNAME in .env.production");
    }

    // Import the database
    info("📊 Importing database: $dbDatabase");

    $mysqlCmd = "mysql -h$dbHost -P$dbPort -u$dbUsername";
    if ($dbPassword) {
        $mysqlCmd .= " -p'$dbPassword'";
    }
    $mysqlCmd .= " $dbDatabase < '$sqlFilePath'";

    try {
        run($mysqlCmd);
        info("✅ Database imported successfully");
    } catch (\Exception $e) {
        error("❌ Database import failed: " . $e->getMessage());
        throw $e;
    }

    // Clean up decrypted file if it was encrypted
    if ($isEncrypted && strpos($decryptedFilePath, '.encrypted') === false) {
        run("rm '$decryptedFilePath'");
        info("🧹 Decrypted SQL file removed from server for security");
    }

    info("✅ Database import completed");
})->desc('Import database from encrypted or plain SQL files');

task('klytron:laravel:deploy:passport:install', function () {
    // Only run if project supports Passport and it's requested
    if (!get('supports_passport', false)) {
        info("⏭️ Laravel Passport not supported for this project");
        return;
    }

    $shouldRunPassportInstall = get('shouldRunPassportInstall', false);
    if ($shouldRunPassportInstall) {
        info("🔐 Installing Laravel Passport...");

        try {
            // Check if Passport is installed
            $passportInstalled = test('[ -f {{release_path}}/vendor/laravel/passport/composer.json ]');

            if (!$passportInstalled) {
                warning("⚠️  Laravel Passport package not found. Install it first with: composer require laravel/passport");
                return;
            }

            // Run Passport installation
            run('cd {{release_path}} && {{bin/php}} artisan passport:install --force');
            info("✅ Laravel Passport installed successfully");

            // Generate Passport keys if they don't exist
            if (!test('[ -f {{release_path}}/storage/oauth-private.key ]')) {
                info("🔑 Generating Passport encryption keys...");
                run('cd {{release_path}} && {{bin/php}} artisan passport:keys --force');
                info("✅ Passport encryption keys generated");
            } else {
                info("ℹ️ Passport encryption keys already exist");
            }

            // Create personal access client if needed
            info("🔑 Creating Passport personal access client...");
            run('cd {{release_path}} && {{bin/php}} artisan passport:client --personal --name="Personal Access Client" --no-interaction');
            info("✅ Passport personal access client created");

        } catch (\Exception $e) {
            error("❌ Laravel Passport installation failed: " . $e->getMessage());

            $continueAnyway = askConfirmation("Continue deployment despite Passport installation failure?", false);
            if (!$continueAnyway) {
                throw new \RuntimeException("Deployment cancelled due to Passport installation failure.");
            }
            warning("⚠️  Continuing deployment despite Passport installation failure");
        }
    } else {
        info("⏭️ Laravel Passport installation skipped (not requested)");
    }
})->desc('Install Laravel Passport if configured');

task('klytron:laravel:deploy:generate:sitemap', function () {
    // Only run if project supports sitemap generation
    if (!get('supports_sitemap', false)) {
        info("⏭️ Sitemap generation not supported for this project");
        return;
    }

    info("🗺️ Generating sitemap...");

    try {
        // Try the most common sitemap generation command first
        run('{{bin/php}} {{release_path}}/artisan sitemap:generate');
        info("✅ Sitemap generated successfully using: sitemap:generate");
    } catch (\Exception $e) {
        try {
            // Fallback to custom app command
            run('{{bin/php}} {{release_path}}/artisan app:sitemap-generate');
            info("✅ Sitemap generated successfully using: app:sitemap-generate");
        } catch (\Exception $e2) {
            try {
                // Fallback to alternative command
                run('{{bin/php}} {{release_path}}/artisan sitemap:create');
                info("✅ Sitemap generated successfully using: sitemap:create");
            } catch (\Exception $e3) {
                warning("⚠️ No sitemap generation command found. Skipping sitemap generation.");
                info("ℹ️ Tried: sitemap:generate, app:sitemap-generate, sitemap:create");
            }
        }
    }
})->desc('Generate application sitemap');

task('klytron:laravel:storage:link', function () {
    // Only run if project supports storage links
    if (!get('supports_storage_link', true)) {
        info("⏭️ Storage link not supported for this project");
        return;
    }

    info("🔗 Creating storage link...");

    // Try to use the enhanced storage:link-clean command if available
    try {
        run('{{bin/php}} {{release_path}}/artisan klytron:storage:link-clean');
        info("✅ Storage link created successfully using enhanced command");
    } catch (\Exception $e) {
        // Fallback to standard storage:link
        run('{{bin/php}} {{release_path}}/artisan storage:link');
        info("✅ Storage link created successfully using standard command");
    }
})->desc('Create storage symbolic link');

task('klytron:laravel:install:addons', function () {
    // Only run for Laravel projects
    if (get('project_type', 'laravel') !== 'laravel') {
        info("⏭️ Laravel addons not applicable for this project type");
        return;
    }

    info("📦 Installing Laravel addon commands...");
    klytron_install_laravel_addons();
    info("✅ Laravel addon commands installed");
})->desc('Install Laravel addon commands from the library');

task('klytron:laravel:deploy:cache:clear:all', function () {
    $shouldClearCaches = get('shouldClearAllCaches', true);
    if (!$shouldClearCaches) {
        info("ℹ️ Skipping cache clearing as configured.");
        return;
    }

    info("🧹 Clearing all application caches...");

    // Clear various Laravel caches using release_or_current_path
    run('{{bin/php}} {{release_or_current_path}}/artisan cache:clear');
    run('{{bin/php}} {{release_or_current_path}}/artisan config:clear');
    run('{{bin/php}} {{release_or_current_path}}/artisan route:clear');
    run('{{bin/php}} {{release_or_current_path}}/artisan view:clear');
    run('{{bin/php}} {{release_or_current_path}}/artisan clear-compiled');

    info("✅ All caches cleared successfully.");
})->desc('Clear all application caches if configured');

task('klytron:deploy:laravel:access_permissions', function () {
    info("🔐 Setting laravel file permissions and ownership...");
    
    $httpUser = get('http_user', 'www-data');
    $httpGroup = get('http_group', 'www-data');
    
    // Handle storage directory (usually a symlink to shared storage)
    $storagePath = '{{release_or_current_path}}/storage';
    if (test("[ -L $storagePath ]")) {
        $realStoragePath = run("readlink -f $storagePath");
        if (!empty($realStoragePath)) {
            run("sudo chmod -R 775 $realStoragePath");
            run("sudo chown -R $httpUser:$httpGroup $realStoragePath");
            info("✅ Set permissions for storage directory at $realStoragePath");
        }
    } else {
        run("sudo chmod -R 775 $storagePath");
        run("sudo chown -R $httpUser:$httpGroup $storagePath");
    }
    
    // Handle bootstrap/cache directory
    $bootstrapCachePath = '{{release_or_current_path}}/bootstrap/cache';
    if (test("[ -L $bootstrapCachePath ]")) {
        $realBootstrapPath = run("readlink -f $bootstrapCachePath");
        if (!empty($realBootstrapPath)) {
            run("sudo chmod -R 775 $realBootstrapPath");
            run("sudo chown -R $httpUser:$httpGroup $realBootstrapPath");
            info("✅ Set permissions for bootstrap/cache at $realBootstrapPath");
        }
    } else {
        run("sudo chmod -R 775 $bootstrapCachePath");
        run("sudo chown -R $httpUser:$httpGroup $bootstrapCachePath");
    }
    
    // Ensure the .env file has correct permissions
    if (test("[ -f {{release_or_current_path}}/.env ]")) {
        run("sudo chmod 640 {{release_or_current_path}}/.env");
        run("sudo chown $httpUser:$httpGroup {{release_or_current_path}}/.env");
    }

    info("✅ Laravel-specific file permissions and ownership set successfully");
})->desc('Set proper laravel file permissions and ownership');

task('klytron:laravel:notify:done', [
    'klytron:laravel:deploy:success',
]);

task('klytron:laravel:deploy:success', function () {
    // Call the framework-agnostic success message
    invoke('klytron:deploy:success');

    // Add Laravel-specific success information
    info("🚀 Application: " . get('application'));
    info("🌍 Environment: production");
    info("📍 Path: " . get('deploy_path'));

    if ($publicHtml = get('application_public_html')) {
        info("🔗 Symlink: $publicHtml → {{deploy_path}}/current/public");
    }
})->desc('Display Laravel deployment success message');

task('klytron:laravel:deploy:info', function () {
    try {
        $hostname = currentHost()->getHostname();
        $release = get('release_name', 'Unknown');
        $stage = get('stage', 'production');

        info("🐘 ===== Deployment Information =====");
        info("");
        info("📋 Basic Information:");
        info("   🔎 Application: " . get('application', 'No name configured'));
        info("   📦 Repository: " . get('repository', 'No repository configured'));
        info("   🌿 Branch: " . get('branch', 'main'));
        info("   📊 Stage: $stage");
        info("   🔢 Release: $release");
        info("");
        info("🖥️  Server Configuration:");
        info("   🚚 Server: " . get('hostname', 'No server configured'));
        info("   🖥️  Host: $hostname");
        info("   📂 Deploy Path: " . get('deploy_path', 'not set'));
        info("   🔗 Public HTML: " . get('application_public_html', 'Not configured'));
        info("");
        info("🌐 Domain Information:");
        info("   🌐 Domain: " . get('application_public_domain', 'No domain configured'));
        info("   🔗 URL: " . get('application_public_url', 'No domain configured'));
        info("");
        info("⚙️  Deployment Settings:");
        info("   ⏱️  Timeout: " . get('default_timeout', 1800) . " seconds");
        info("   🔄 Releases Kept: " . get('keep_releases', 3));
        info("   🐘 PHP Version: " . get('php_version', 'system default'));
        info("");
        info("================================");
    } catch (\Exception $e) {
        warning("⚠️  Some deployment information could not be retrieved");
        info("🔎 Application: " . get('application', 'Unknown'));
        info("📦 Repository: " . get('repository', 'Unknown'));
        info("🌿 Branch: " . get('branch', 'main'));
    }

})->desc('Display comprehensive deployment information');

///////////////////////////////////////////////////////////////////////////////
// GROUP TASKS
///////////////////////////////////////////////////////////////////////////////

/**
 * Group task for displaying deployment info with all validations
 */
task('klytron:laravel:deploy:display:info', [
    'klytron:laravel:validate:environment:local',  // Validate locally first
    'klytron:laravel:validate:domain',
    'klytron:laravel:deploy:info'
])->desc('Display comprehensive deployment information with validation');

/**
 * Group task for interactive configuration with all validations
 */
task('klytron:laravel:deploy:configure:interactive', [
    'klytron:laravel:validate:environment:local',  // Validate locally first
    'klytron:laravel:validate:domain',
    'klytron:laravel:init:questions'
])->desc('Interactive deployment configuration with validation');

/**
 * Group task for environment deployment (Laravel extends framework-agnostic)
 */
task('klytron:laravel:deploy:environment:complete', [
    'klytron:deploy:environment:complete'
])->desc('Complete Laravel environment file deployment');

/**
 * Group task for deployment completion notification (Laravel extends framework-agnostic)
 */
task('klytron:laravel:deploy:notify:complete', [
    'klytron:laravel:deploy:success'
])->desc('Complete Laravel deployment success notification');

/**
 * Group task for complete validation suite
 */
task('klytron:laravel:validate:all', [
    'klytron:laravel:validate:environment',
    'klytron:laravel:validate:domain'
])->desc('Run all validation checks');

/**
 * Group task for database operations (when needed)
 */
task('klytron:laravel:deploy:database:complete', function () {
    if (get('shouldRunMigration', false)) {
        invoke('klytron:laravel:deploy:db:migrate');
    }
    if (get('shouldImportDbFile', false)) {
        invoke('klytron:laravel:deploy:db:import');
    }
})->desc('Complete database deployment operations based on configuration');

/**
 * Group task for cache operations
 */
task('klytron:laravel:deploy:cache:complete', function () {
    if (get('shouldClearAllCaches', true)) {
        invoke('klytron:laravel:deploy:cache:clear:all');
    }
    invoke('artisan:config:cache');
    invoke('artisan:optimize');
})->desc('Complete cache management and optimization');

/**
 * Group task for post-deployment setup (Laravel extends framework-agnostic)
 */
task('klytron:laravel:deploy:finalize:complete', [
    'klytron:deploy:finalize:complete',
    'klytron:laravel:storage:link'
])->desc('Complete Laravel post-deployment finalization');

/**
 * Group task for backup and preparation (Laravel extends framework-agnostic)
 */
task('klytron:laravel:deploy:prepare:complete', function () {
    invoke('klytron:laravel:deploy:confirm');
    if (get('shouldBackupBeforeDeployment', false)) {
        invoke('klytron:laravel:deploy:backup:create');
    }
})->desc('Complete Laravel deployment preparation with backup');

/**
 * Master group task for full deployment info and validation
 */
task('klytron:laravel:deploy:info:master', [
    'klytron:laravel:validate:all',
    'klytron:laravel:deploy:display:info'
])->desc('Master task for complete deployment information with all validations');

///////////////////////////////////////////////////////////////////////////////
// LARAVEL-SPECIFIC DEPLOYMENT FLOWS
///////////////////////////////////////////////////////////////////////////////

/**
 * Get the complete Laravel deployment flow
 * This extends the framework-agnostic flow with Laravel-specific tasks
 */
function klytron_laravel_deploy_flow(): array {
    return [
        'klytron:deploy:start_timer',
        'klytron:laravel:deploy:display:info',           // Group task: validation + info display
        'klytron:laravel:deploy:configure:interactive',  // Group task: validation + interactive config
        'deploy:unlock',
        'klytron:laravel:backup:pre_deploy',             // Pre-deployment backup (optional)
        'klytron:laravel:deploy:prepare:complete',       // Group task: confirmation + backup
        'deploy:setup',
        'deploy:lock',
        'deploy:release',
        'deploy:fix_repo',                              // Fix repo issues BEFORE code update
        'deploy:update_code',
        'deploy:shared',
        'klytron:laravel:deploy:environment:complete',   // Group task: env upload + deployment
        'deploy:env',
        'deploy:vendors',
'klytron:laravel:node:vite:build:local',
        'klytron:laravel:deploy:database:complete',      // Group task: migration + import (conditional)
        'klytron:laravel:deploy:passport:install',
        'deploy:writable',
        'klytron:laravel:deploy:cache:complete',         // Group task: cache clearing + optimization
        'deploy:symlink',
        'klytron:laravel:deploy:finalize:complete',      // Group task: symlink + permissions + storage
        'deploy:unlock',
        'deploy:cleanup',
        'klytron:deploy:access_permissions',             // Final permissions fix
        'klytron:laravel:deploy:notify:complete',        // Group task: success message + notification
        'klytron:laravel:backup:post_deploy',            // Post-deployment backup restoration (optional)
        'deploy:end_timer',
    ];
}

/**
 * Get a minimal Laravel deployment flow (without assets, migrations, etc.)
 */
function klytron_laravel_deploy_flow_minimal(): array {
    return [
        'klytron:deploy:start_timer',
        'klytron:laravel:init:questions',
        'deploy:unlock',
        'deploy:info',
        'klytron:laravel:deploy:backup:create',
        'deploy:setup',
        'deploy:lock',
        'deploy:release',
        'deploy:update_code',
        'deploy:shared',
        'klytron:upload:env:production',
        'deploy:env',
        'deploy:vendors',
        'klytron:laravel:deploy:cache:clear:all',
        'deploy:writable',
        'deploy:symlink',
        'klytron:deploy:create:server_symlink',
        'klytron:deploy:access_permissions',
        'deploy:unlock',
        'deploy:cleanup',
        'klytron:laravel:notify:done',
        'deploy:end_timer',
    ];
}

/**
 * Get an API-focused Laravel deployment flow
 */
function klytron_laravel_deploy_flow_api(): array {
    return [
        'klytron:deploy:start_timer',
        'klytron:laravel:deploy:display:info',
        'klytron:laravel:deploy:configure:interactive',
        'deploy:unlock',
        'klytron:laravel:deploy:prepare:complete',
        'deploy:setup',
        'deploy:lock',
        'deploy:release',
        'deploy:fix_repo',                              // Fix repo issues BEFORE code update
        'deploy:update_code',
        'deploy:shared',
        'klytron:laravel:deploy:environment:complete',
        'deploy:env',
        'deploy:vendors',
        // Skip Vite build for API-only projects
        'klytron:laravel:deploy:database:complete',
        'klytron:laravel:deploy:passport:install',       // Important for API authentication
        'deploy:writable',
        'klytron:laravel:deploy:cache:complete',
        'deploy:symlink',
        'klytron:laravel:deploy:finalize:complete',
        'deploy:unlock',
        'deploy:cleanup',
        'klytron:deploy:access_permissions',
        'klytron:laravel:deploy:notify:complete',
        'deploy:end_timer',
    ];
}

// Add any additional Laravel-specific tasks below this line.

///////////////////////////////////////////////////////////////////////////////
// LARAVEL BACKUP & RESTORE FUNCTIONALITY
///////////////////////////////////////////////////////////////////////////////

/**
 * Create pre-deployment backup (optional)
 */
task('klytron:laravel:backup:pre_deploy', function () {
    info("💾 Pre-deployment backup check...");
    
    // Check if this is a fresh installation (no current release exists)
    $currentExists = test('[ -d "{{deploy_path}}/current" ]');
    
    if (!$currentExists) {
        info("🆆 Fresh installation detected - no existing data to backup");
        info("⏭️ Skipping pre-deployment backup for fresh installation");
        return;
    }
    
    // Check the backup setting from deployment configuration
    $shouldBackupBeforeDeployment = get('shouldBackupBeforeDeployment', null);
    $autoCreateBackup = get('auto_create_backup', null);
    
    // Determine if we should create backup
    $shouldCreateBackup = false;
    
    if ($shouldBackupBeforeDeployment !== null) {
        // Use the setting from deployment configuration
        $shouldCreateBackup = $shouldBackupBeforeDeployment;
        info("📋 Using backup setting from deployment configuration: " . ($shouldCreateBackup ? 'YES' : 'NO'));
    } elseif ($autoCreateBackup !== null) {
        // Fall back to auto setting
        $shouldCreateBackup = $autoCreateBackup;
        info("🤖 Auto-setting pre-deployment backup: " . ($shouldCreateBackup ? 'YES' : 'NO'));
    } else {
        // Ask user if no setting is available
        $shouldCreateBackup = askConfirmation("Create backup before deployment?", true);
    }
    
    if (!$shouldCreateBackup) {
        info("⏭️ Skipping pre-deployment backup");
        return;
    }
    
    info("🔄 Creating pre-deployment backup...");
    
    try {
        // Run backup command on the current production environment
        $result = run('cd {{current_path}} && {{bin/php}} artisan backup:run');
        info("✅ Pre-deployment backup created successfully");
        info("Backup result: " . $result);
    } catch (\Exception $e) {
        warning("⚠️ Failed to create pre-deployment backup: " . $e->getMessage());
        warning("Continuing with deployment...");
    }
})->desc('Creates a backup before deployment (optional)');

/**
 * Handle post-deployment backup restoration
 */
task('klytron:laravel:backup:post_deploy', function () {
    info("🔄 Post-deployment backup restoration check...");
    
    $autoRestoreBackup = get('auto_restore_backup');
    
    if ($autoRestoreBackup === null) {
        $shouldRestoreBackup = askConfirmation("Restore from backup after deployment?", true);
    } else {
        $shouldRestoreBackup = $autoRestoreBackup;
        info("🤖 Auto-setting post-deployment restore: " . ($shouldRestoreBackup ? 'YES' : 'NO'));
    }
    
    if (!$shouldRestoreBackup) {
        info("⏭️ Skipping backup restoration");
        return;
    }
    
    info("📋 Fetching available backups...");
    
    try {
        // Get list of available backups using the restore command
        $backupList = run('cd {{current_path}} && {{bin/php}} artisan backup:restore-complete --list');
        
        // Parse the backup list output
        $lines = explode("\n", $backupList);
        $availableBackups = [];
        $uniqueBackups = [];
        
        info("📋 Available backups:");
        
        // Join wrapped lines and parse
        $fullText = str_replace("\n", ' ', $backupList);
        
        // Find all backup entries using regex
        preg_match_all('/📁\s+([^)]+\.zip)\s+\(([^)]+)\)\s+-\s+([^\s]+)/', $fullText, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $filename = trim($match[1]);
            $size = trim($match[2]);
            $date = trim($match[3]);
            
            // Deduplicate by filename
            if (!isset($uniqueBackups[$filename])) {
                $uniqueBackups[$filename] = [
                    'filename' => $filename,
                    'size' => $size,
                    'date' => $date
                ];
            }
        }
        
        // Convert to indexed array with 0-based keys for askChoice
        $availableBackups = array_values($uniqueBackups);
        
        // Display with 1-based numbering
        foreach ($availableBackups as $index => $backup) {
            $displayNumber = $index + 1;
            info("  $displayNumber. {$backup['filename']} ({$backup['date']}) - {$backup['size']}");
        }
        
        if (empty($availableBackups)) {
            warning("⚠️ No backups found");
            return;
        }
        
        // Ask user to select backup (askChoice uses 0-based indexing, which now matches our array)
        $selectedIndex = askChoice("Select backup to restore from:", array_keys($availableBackups));
        $selectedBackup = $availableBackups[$selectedIndex];
        
        if (!$selectedBackup) {
            warning("⚠️ Invalid backup selection");
            return;
        }
        
        info("🔄 Restoring from backup: " . ($selectedBackup['filename'] ?? 'Unknown'));
        
        // Safety check - ensure we're restoring to production
        $currentPath = get('current_path');
        info("📍 Current deployment path: $currentPath");
        
        if (str_contains($currentPath, 'local') || str_contains($currentPath, 'dev')) {
            warning("⚠️  WARNING: You appear to be restoring to a local/development environment!");
            if (!askConfirmation("Are you sure you want to continue with restore to this environment?", false)) {
                info("❌ Restore operation cancelled.");
                return;
            }
        }
        
        // Check if this is an SQLite project (files-only backup)
        $databaseType = get('database_type', 'mysql');
        $isSqliteProject = $databaseType === 'sqlite';
        
        if ($isSqliteProject) {
            info("ℹ️  SQLite project detected - skipping database restoration (files-only backup)");
            $restoreDatabase = false;
            $restoreFiles = true;
            $restoreOptions = ['--files-only'];
        } else {
            // Ask for restoration options for non-SQLite projects
            $restoreOptions = [];
            
            $restoreDatabase = askConfirmation("Restore database?", true);
            $restoreFiles = askConfirmation("Restore files?", true);
            
            if ($restoreDatabase && $restoreFiles) {
                // Restore everything (no specific flags needed)
                $restoreOptions = [];
            } elseif ($restoreDatabase) {
                $restoreOptions[] = '--database-only';
            } elseif ($restoreFiles) {
                $restoreOptions[] = '--files-only';
            } else {
                // If neither selected, restore everything
                $restoreOptions = [];
            }
        }
        
        // Only ask about database reset when restoring the database (and not SQLite)
        if (!$isSqliteProject && $restoreDatabase) {
            if (askConfirmation("Reset database before restore (drop all tables)?", true)) {
                $restoreOptions[] = '--reset';
            }
        }
        
        // Auto-confirm skip confirmation prompts for automated deployments
        $autoSkipConfirm = get('auto_restore_backup', null);
        if ($autoSkipConfirm !== null) {
            $skipConfirm = $autoSkipConfirm;
            info("🤖 Auto-setting skip confirmation prompts: " . ($skipConfirm ? 'YES' : 'NO'));
        } else {
            $skipConfirm = askConfirmation("Skip confirmation prompts?", true);
        }
        
        if ($skipConfirm) {
            $restoreOptions[] = '--force';
        }
        
        // Build restore command with proper environment context
        // Use Google Drive disk for production backups
        $restoreCommand = 'cd {{current_path}} && {{bin/php}} artisan backup:restore-complete --disk=google --backup="' . $selectedBackup['filename'] . '"';
        if (!empty($restoreOptions)) {
            $restoreCommand .= ' ' . implode(' ', $restoreOptions);
        }
        
        info("🔄 Executing restore command on production server: $restoreCommand");
        info("📍 Restore target: {{current_path}} (production server)");
        
        // Execute restore on production server
        $result = run($restoreCommand);
        info("✅ Backup restoration completed successfully");
        info("Restore result: " . $result);
        
        // Run custom health checks after restoration
        info("🏥 Running post-restoration health checks...");
        try {
            $healthResult = run('cd {{current_path}} && {{bin/php}} artisan klytron:backup:health-check');
            info("✅ Post-restoration health check completed");
            info($healthResult);
        } catch (\Exception $e) {
            warning("⚠️  Health check failed but continuing deployment: " . $e->getMessage());
            info("💡 You can run health checks manually later with: vendor/bin/dep klytron:laravel:backup:manage");
        }
        
    } catch (\Exception $e) {
        error("❌ Failed to restore backup: " . $e->getMessage());
        throw $e;
    }
})->desc('Restores from backup after deployment (optional) - runs on production server');

/**
 * Interactive backup management task
 */
task('klytron:laravel:backup:manage', function () {
    $appName = get('application', 'Laravel Application');
    info("💾 Backup Management for $appName");
    info("");
    
    $action = askChoice("Select action:", [
        'list' => 'List available backups',
        'create' => 'Create new backup',
        'restore' => 'Restore from backup',
        'clean' => 'Clean old backups',
        'cleanup_restoration' => 'Clean up restoration backup files',
        'monitor' => 'Monitor backup health'
    ]);
    
    switch ($action) {
        case 'list':
            info("📋 Fetching available backups...");
            $backupList = run('cd {{current_path}} && {{bin/php}} artisan backup:list');
            info($backupList);
            break;
            
        case 'create':
            info("🔄 Creating new backup...");
            $result = run('cd {{current_path}} && {{bin/php}} artisan backup:run');
            info("✅ Backup created successfully");
            info($result);
            break;
            
        case 'restore':
            invoke('klytron:laravel:backup:post_deploy');
            break;
            
        case 'clean':
            info("🧹 Cleaning old backups...");
            $result = run('cd {{current_path}} && {{bin/php}} artisan backup:clean');
            info("✅ Backup cleanup completed");
            info($result);
            break;
            
        case 'cleanup_restoration':
            invoke('klytron:laravel:backup:cleanup:restoration');
            break;
            
        case 'monitor':
            info("🏥 Monitoring backup health...");
            $result = run('cd {{current_path}} && {{bin/php}} artisan backup:monitor');
            info("✅ Backup health check completed");
            info($result);
            
            info("🏥 Running custom health checks...");
            try {
                $healthResult = run('cd {{current_path}} && {{bin/php}} artisan klytron:backup:health-check');
                info("✅ Custom health check completed");
                info($healthResult);
            } catch (\Exception $e) {
                warning("⚠️  Custom health check failed: " . $e->getMessage());
            }
            break;
    }
})->desc('Interactive backup management for Laravel applications');

/**
 * Generic backup health check task
 */
task('klytron:laravel:backup:health_check', function () {
    info("🏥 Performing backup health checks...");
    
    try {
        $result = run('cd {{current_path}} && {{bin/php}} artisan klytron:backup:health-check');
        info("✅ Backup health check completed");
        info($result);
    } catch (\Exception $e) {
        warning("⚠️  Backup health check failed: " . $e->getMessage());
        info("💡 This is normal if the custom health check command is not implemented");
    }
})->desc('Perform backup health checks for Laravel applications');

after('klytron:deploy:access_permissions', 'klytron:deploy:laravel:access_permissions');

/**
 * Validate Laravel deployment configuration
 */
task('klytron:laravel:validate:config', function () {
    info("🔍 Validating Laravel deployment configuration...");
    
    try {
        $config = [
            'application_name' => get('application_name', ''),
            'repository_url' => get('repository', ''),
            'deploy_path' => get('deploy_path', ''),
            'domain' => get('domain', ''),
            'php_version' => get('php_version', ''),
            'http_user' => get('http_user', ''),
            'branch' => get('branch', ''),
            'database' => get('database', []),
            'supports_vite' => get('supports_vite', false),
            'supports_storage_link' => get('supports_storage_link', false),
            'shared_dirs' => get('shared_dirs', []),
            'vite_config' => get('vite_config', [])
        ];
        
        $validation = ConfigurationValidationService::validateLaravelConfig($config);
        
        if ($validation['valid']) {
            info("✅ Configuration validation passed");
        } else {
            error("❌ Configuration validation failed:");
            foreach ($validation['errors'] as $error) {
                error("  - {$error['field']}: {$error['message']}");
                if ($error['suggestion']) {
                    info("    💡 {$error['suggestion']}");
                }
            }
        }
        
        if (!empty($validation['warnings'])) {
            warning("⚠️ Configuration warnings:");
            foreach ($validation['warnings'] as $warning) {
                warning("  - {$warning['field']}: {$warning['message']}");
                if ($warning['suggestion']) {
                    info("    💡 {$warning['suggestion']}");
                }
            }
        }
        
        if (!$validation['valid']) {
            throw new \RuntimeException("Configuration validation failed");
        }
        
    } catch (\Exception $e) {
        error("❌ Configuration validation error: " . $e->getMessage());
        throw new \RuntimeException("Configuration validation error: " . $e->getMessage());
    }
})->desc('Validate Laravel deployment configuration');

/**
 * Enhanced deployment success with metrics
 */
task('klytron:laravel:deploy:success', function () {
    try {
        info("🎉 Laravel deployment completed successfully!");
        
        // Metrics service not available - skip metrics display
        if (class_exists('Klytron\PhpDeploymentKit\Services\DeploymentMetricsService')) {
            // Save final metrics first to calculate total duration
            \Klytron\PhpDeploymentKit\Services\DeploymentMetricsService::endDeployment();
            
            $metrics = \Klytron\PhpDeploymentKit\Services\DeploymentMetricsService::getMetrics();
            
            if (!empty($metrics)) {
                info(\Klytron\PhpDeploymentKit\Services\DeploymentMetricsService::getSummary());
            }
        }
        
    } catch (\Exception $e) {
        error("❌ Error in deployment success handler: " . $e->getMessage());
    }
})->desc('Enhanced Laravel deployment success with metrics');

after('klytron:laravel:deploy:success', 'klytron:system:restart');

/**
 * Environment Decryption Integration
 * Automatically decrypt environment files if encrypted versions exist
 */
task('klytron:laravel:env:decrypt', function () {
    if (test('[ -f {{release_path}}/.env.production.encrypted ]')) {
        info("🔓 Found encrypted production environment file, decrypting...");
        
        // Check if decryption key is available
        $key = getenv('LARAVEL_ENV_ENCRYPTION_KEY') ?: get('env_encryption_key', null);
        
        if (!$key) {
            warning("⚠️  No decryption key found. Set LARAVEL_ENV_ENCRYPTION_KEY environment variable.");
            warning("   You can also run: vendor/bin/dep klytron:env:setup");
            return;
        }
        
        try {
            \Klytron\PhpDeploymentKit\Tasks\EnvironmentDecryptTask::decrypt('production', $key, true);
            info("✅ Production environment file decrypted successfully");
        } catch (\Exception $e) {
            error("❌ Failed to decrypt production environment file: " . $e->getMessage());
            throw $e;
        }
    } else {
        info("ℹ️  No encrypted production environment file found, skipping decryption");
    }
})->desc('Decrypt Laravel environment files if encrypted versions exist');

// Only add encryption hooks if encryption environments are configured
$encryptionEnvs = get('env_encryption_environments', []);
if (!empty($encryptionEnvs)) {
    // Automatically run environment decryption after shared directories are set up
    after('deploy:shared', 'klytron:laravel:env:decrypt');

    // Ensure env files exist locally before upload tasks run
    before('klytron:deploy:env', 'klytron:laravel:local:env:ensure_decrypted');
}

/**
 * Clean up temporary backup files created during restoration
 */
task('klytron:laravel:backup:cleanup:restoration', function () {
    info("🧹 Cleaning up temporary backup files from restoration process...");
    
    $currentPath = get('current_path');
    $deployPath = get('deploy_path');
    
    // Find and remove storage backup directories
    $storageBackups = run("find '$currentPath' -maxdepth 1 -type d -name 'storage_backup_*' 2>/dev/null || echo ''");
    if (!empty($storageBackups)) {
        $backupDirs = explode("\n", trim($storageBackups));
        foreach ($backupDirs as $backupDir) {
            if (!empty($backupDir)) {
                info("🗑️  Removing storage backup: $backupDir");
                run("rm -rf '$backupDir'");
            }
        }
    }
    
    // Find and remove public backup directories
    $publicBackups = run("find '$currentPath' -maxdepth 1 -type d -name 'public_backup_*' 2>/dev/null || echo ''");
    if (!empty($publicBackups)) {
        $backupDirs = explode("\n", trim($publicBackups));
        foreach ($backupDirs as $backupDir) {
            if (!empty($backupDir)) {
                info("🗑️  Removing public backup: $backupDir");
                run("rm -rf '$backupDir'");
            }
        }
    }
    
    // Also check in the deploy path for any other backup directories
    $otherBackups = run("find '$deployPath' -maxdepth 2 -type d -name '*_backup_*' 2>/dev/null | grep -E '(storage_backup_|public_backup_)' || echo ''");
    if (!empty($otherBackups)) {
        $backupDirs = explode("\n", trim($otherBackups));
        foreach ($backupDirs as $backupDir) {
            if (!empty($backupDir)) {
                info("🗑️  Removing backup directory: $backupDir");
                run("rm -rf '$backupDir'");
            }
        }
    }
    
    info("✅ Temporary backup files cleanup completed");
})->desc('Clean up temporary backup files created during restoration process');
