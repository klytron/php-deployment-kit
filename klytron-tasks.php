<?php
/**
 * Klytron Deployer - Core Tasks (Framework-Agnostic)
 *
 * All framework-specific logic (e.g., Laravel, artisan, Passport, etc.) has been moved to recipes/klytron-laravel-recipe.php.
 * Only generic, reusable tasks and helpers remain here.
 */

namespace Deployer;

use Klytron\PhpDeploymentKit\Tasks\AssetMappingTask;
use Klytron\PhpDeploymentKit\Tasks\SitemapTask;
use Klytron\PhpDeploymentKit\Tasks\ImageOptimizationTask;
use Klytron\PhpDeploymentKit\Tasks\DeploymentMetricsTask;
use Klytron\PhpDeploymentKit\Tasks\EnvironmentDecryptTask;
use Klytron\PhpDeploymentKit\Services\DeploymentMetricsService;

///////////////////////////////////////////////////////////////////////////////
// CHECK IF DEPLOYER FUNCTIONS ARE AVAILABLE
///////////////////////////////////////////////////////////////////////////////

// Deployer CLI loads functions automatically from its phar
// If not available (e.g., being included outside Deployer context), exit early
if (!function_exists('Deployer\set')) {
    return;
}

///////////////////////////////////////////////////////////////////////////////
// CONFIGURATION
///////////////////////////////////////////////////////////////////////////////

// Set default configuration values if not already set
set('temp_dir', function () {
    return sys_get_temp_dir();
});

// Default environment file names
set('env_file_local', '.env.production');
set('env_file_remote', '.env');

// Default file permissions
set('default_file_permissions', 0644);
set('default_dir_permissions', 0755);

// Default system users and groups
set('http_user', 'www-data');
set('http_group', 'www-data');

// Default backup configuration
set('backup_path', '{{deploy_path}}/backups');
set('backup_keep', 5);

///////////////////////////////////////////////////////////////////////////////
// TIMER TASKS
///////////////////////////////////////////////////////////////////////////////

/**
 * Start deployment timer
 */
task('klytron:deploy:start_timer', function () {
    set('deploy_start_time', time());
    info("⏱️ Deployment started at " . date('Y-m-d H:i:s'));
    DeploymentMetricsService::startDeployment();
});

/**
 * End deployment timer and show duration
 */
task('klytron:deploy:end_timer', function () {
    $startTime = get('deploy_start_time', time());
    $endTime = time();
    $duration = $endTime - $startTime;

    $minutes = floor($duration / 60);
    $seconds = $duration % 60;

    info("⏱️ Deployment completed at " . date('Y-m-d H:i:s', $endTime));

    if ($minutes > 0) {
        info("⏱️ Deployment completed in {$minutes}m {$seconds}s");
    } else {
        info("⏱️ Deployment completed in {$seconds}s");
    }
});

///////////////////////////////////////////////////////////////////////////////
// VALIDATION TASKS
///////////////////////////////////////////////////////////////////////////////

/**
 * Validate deploy_path_parent configuration
 * This prevents deployments to incorrect locations
 */
task('klytron:validate:deploy_path_parent', function () {
    klytron_validate_deploy_path_parent();
})->desc('Validate deploy_path_parent configuration');

///////////////////////////////////////////////////////////////////////////////
// REPOSITORY TASKS
///////////////////////////////////////////////////////////////////////////////

/**
 * Fix repository cache issues and permissions
 */
task('klytron:deploy:fix_repo', function () {
    info("🔧 Fixing repository cache and permission issues...");

    $repoPath = get('deploy_path') . '/.dep/repo';
    $deployPath = get('deploy_path');

    info("📁 Repository cache path: $repoPath");

    // Check if the repo directory exists and remove if corrupted
    if (test("[ -d '$repoPath' ]")) {
        info("🗑️ Removing corrupted repository cache...");
        run("rm -rf '$repoPath'");
        info("✅ Repository cache removed successfully");
    } else {
        info("ℹ️ Repository cache directory does not exist");
    }

    // Check for git lock files that might be causing issues
    if (test("[ -f '$deployPath/.git/index.lock' ]")) {
        info("🔓 Removing git lock file...");
        run("rm -f '$deployPath/.git/index.lock'");
        info("✅ Git lock file removed");
    }

    // Pre-emptively add the repository cache directory to Git safe directories
    // This prevents "dubious ownership" errors when the repo is cloned
    info("🔒 Pre-configuring Git safe directory for repository cache...");
    run("git config --global --add safe.directory '$repoPath' || true");
    
    // Also add the deploy path as a safe directory in case there's a .git directory there
    run("git config --global --add safe.directory '$deployPath' || true");
    
    // Add common Git directories that might cause ownership issues
    // Use environment variables or configuration instead of hardcoded paths
    $commonGitDirs = [];
    
    // Add deploy_path_parent if configured
    if (has('deploy_path_parent')) {
        $commonGitDirs[] = get('deploy_path_parent');
    }
    
    // Add deploy_path if different from deploy_path_parent
    if (has('deploy_path') && (!has('deploy_path_parent') || get('deploy_path') !== get('deploy_path_parent'))) {
        $commonGitDirs[] = get('deploy_path');
    }
    
    // Add common web server directories
    $commonGitDirs = array_merge($commonGitDirs, [
        '/var/www',
        '/var/www/html',
        '~/*/public_html',
        '~/*/domains/*/public_html',
        '{{deploy_path}}/public_html',
        '{{deploy_path}}/current/public',
        get('deploy_path') . '/*/public_html',
        get('deploy_path') . '/*/current/public'
    ]);
    
    foreach ($commonGitDirs as $gitDir) {
        if (test("[ -d '$gitDir' ]")) {
            run("git config --global --add safe.directory '$gitDir' || true");
            info("✅ Added safe directory: $gitDir");
        }
    }

    // If the repo directory exists, fix its permissions
    if (test("[ -d '$repoPath' ]")) {
        info("🔧 Fixing repository permissions...");
        // Use the repository cache path for git commands.
        run("cd '$repoPath' && git config --global --add safe.directory '$repoPath' || true");
        run("cd '$repoPath' && git status || true");
        info("✅ Repository permissions fixed");
    } else {
        info("ℹ️ Repository cache path not yet created, will be handled during code update");
    }

    info("🎉 Repository fix completed. You can now re-run your deployment.");
    info("💡 Run: vendor/bin/dep deploy");
})->desc('Fix repository cache issues and permissions when deploy:update_code fails');

/**
 * Fix Git ownership issues after code update
 * This handles "dubious ownership" errors that occur during deploy:update_code
 */
task('klytron:deploy:fix_git_ownership', function () {
    info("🔧 Fixing Git ownership issues after code update...");
    
    $repoPath = get('deploy_path') . '/.dep/repo';
    $releasePath = get('release_path');
    
    // Add the repository cache directory to Git safe directories
    if (test("[ -d '$repoPath' ]")) {
        info("🔒 Adding repository cache to Git safe directories...");
        run("git config --global --add safe.directory '$repoPath' || true");
        info("✅ Repository cache added to safe directories");
    }
    
    // Add the release directory to Git safe directories (in case it has a .git directory)
    if (test("[ -d '$releasePath' ]")) {
        info("🔒 Adding release directory to Git safe directories...");
        run("git config --global --add safe.directory '$releasePath' || true");
        info("✅ Release directory added to safe directories");
    }
    
    // Fix ownership of the repository cache if it exists
    if (test("[ -d '$repoPath' ]")) {
        info("🔧 Fixing repository cache ownership...");
        $httpUser = get('http_user', 'www-data');
        $httpGroup = get('http_group', 'www-data');
        
        run("chown -R $httpUser:$httpGroup '$repoPath' || true");
        run("chmod -R 755 '$repoPath' || true");
        info("✅ Repository cache ownership fixed");
    }
    
    info("🎉 Git ownership issues fixed");
})->desc('Fix Git ownership issues after code update');

// Hook to automatically fix Git ownership issues after code update
after('deploy:update_code', 'klytron:deploy:fix_git_ownership');

///////////////////////////////////////////////////////////////////////////////
// BACKUP TASKS
///////////////////////////////////////////////////////////////////////////////

// [Framework-specific backup tasks moved to recipes/klytron-laravel-recipe.php]

///////////////////////////////////////////////////////////////////////////////
// ENVIRONMENT TASKS
///////////////////////////////////////////////////////////////////////////////

// [Framework-specific environment tasks moved to recipes/klytron-laravel-recipe.php]

///////////////////////////////////////////////////////////////////////////////
// NODE.JS AND ASSET TASKS (GENERIC)
///////////////////////////////////////////////////////////////////////////////

/**
 * Intelligent Node.js build dispatcher (generic)
 * - Prefers Vite when vite.config.* and a build script are present
 * - Falls back to Laravel Mix only for Laravel projects
 */
task('klytron:node:build', function () {
    if (!get('supports_nodejs', true)) {
        info("⏭️ Node.js builds not supported for this project");
        return;
    }

    info("🔍 Detecting build system configuration...");

    // Check for Vite configuration
    $hasViteConfig = test("[ -f '{{release_path}}/vite.config.js' ]") || test("[ -f '{{release_path}}/vite.config.ts' ]");
    $packageJson = '';
    if (test("[ -f '{{release_path}}/package.json' ]")) {
        $packageJson = run("cat {{release_path}}/package.json");
    }
    $hasBuildScript = strpos($packageJson, '"build"') !== false;
    $hasProductionScript = strpos($packageJson, '"production"') !== false;

    if ($hasViteConfig && $hasBuildScript) {
        info("✅ Vite configuration detected - using Vite build system");
        invoke('klytron:node:vite:build');
        return;
    }

    if ($hasProductionScript) {
        $projectType = get('project_type', 'laravel');
        if ($projectType === 'laravel') {
            info("✅ Laravel Mix configuration detected - using Laravel Mix build");
            // Delegate to Laravel-specific Mix task exposed by the Laravel recipe
            invoke('klytron:laravel:node:mix:build');
            return;
        }

        error("❌ Laravel Mix build script detected but project type is not Laravel");
        throw new \RuntimeException("Laravel Mix is supported only for Laravel projects. Set project_type to 'laravel' or switch to Vite.");
    }

    error("❌ No supported build system found!");
    error("   Expected: vite.config.js + 'build' script OR 'production' script in package.json");
    throw new \RuntimeException("No supported build system configuration found");
})->desc('Generic Node.js build dispatcher (Vite preferred, Mix only for Laravel)');

/**
 * Generic Vite build task with environment variable support and Node.js compatibility
 */
task('klytron:node:vite:build', function () {
    if (!get('supports_vite', true)) {
        info("⏭️ Vite asset building not supported for this project");
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

    if ($hasNode) {
        $nodeBinary = 'node';
    } elseif ($hasNodeJs) {
        $nodeBinary = 'nodejs';
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
            } catch (\Exception $e) {
                error('❌ Node.js not found after attempting NVM activation.');
                throw new \RuntimeException('Node.js is required for Vite builds. Please install Node.js or set up NVM on the server.');
            }
        } else {
            error('❌ Node.js is not installed on the server (no node/nodejs, and NVM not found).');
            throw new \RuntimeException('Node.js is required for Vite builds. Install Node.js (node + npm) or NVM with an LTS version.');
        }
    }

    // Ensure npm is available unless using NVM prefix which will provide it at runtime
    if (!$hasNpm && $nodeEnvPrefix === '') {
        error('❌ npm command not found. Please install npm on the server.');
        throw new \RuntimeException('npm not found. Install npm to continue.');
    }

    info('🔍 Node.js version: ' . run($nodeEnvPrefix . $nodeBinary . ' --version'));
    info('📦 Installing Node.js dependencies...');

    // Environment to avoid Puppeteer's Chromium download and speed up npm
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

    // Smart package installation: npm ci vs npm install
    $useCi = test("[ -f '{{release_path}}/package-lock.json' ]");
    $installCmd = $useCi ? 'npm ci' : 'npm install';
    info($useCi ? '📋 Found package-lock.json, using npm ci for clean install...' : '📋 No package-lock.json found, using npm install...');

    try {
        run($nodeEnvPrefix . "$npmBaseEnv NPM_CONFIG_REGISTRY='$primaryRegistry' $installCmd");
    } catch (\Exception $e) {
        warning('⚠️ npm install failed with primary registry, retrying with mirror...');
        run($nodeEnvPrefix . "$npmBaseEnv NPM_CONFIG_REGISTRY='$mirrorRegistry' $installCmd");
    }

    info("🏗️ Building production assets with Vite...");

    // Get environment variables for Vite (configurable via project settings)
    $envVars = [];
    $viteEnvVars = get('vite_env_vars', ['APP_NAME', 'APP_ENV', 'APP_URL', 'VITE_PUSHER_APP_KEY', 'VITE_PUSHER_APP_CLUSTER']);

    $envFile = '{{release_path}}/' . get('env_file_remote', '.env');
    if (test("[ -f '$envFile' ]")) {
        // Avoid printing .env to logs: download to local temp file, then parse
        $tempDir = get('temp_dir', sys_get_temp_dir());
        $tmpLocal = tempnam($tempDir, 'klytron_env_');
        try {
            download($envFile, $tmpLocal);
            $envContent = @file_get_contents($tmpLocal) ?: '';
        } finally {
            if (is_file($tmpLocal)) {
                @unlink($tmpLocal);
            }
        }
        $lines = explode("\n", $envContent);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }

            if (strpos($line, '=') !== false) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value, "\"'{}");

                // Include configured environment variables
                if (in_array($key, $viteEnvVars)) {
                    $envVars[] = $key . '=' . escapeshellarg($value);
                }
            }
        }
    }

    // Build with environment variables and Node.js compatibility
    $envString = implode(' ', $envVars);
    $buildCommand = get('vite_build_command', 'npm run build');

    try {
        // Try with modern Node.js first
        run($nodeEnvPrefix . "$envString $buildCommand");
        info("✅ Vite build complete");
    } catch (\Exception $e) {
        warning("⚠️ Modern Vite build failed, trying with legacy OpenSSL provider...");
        try {
            // Fallback for older Node.js versions
            run($nodeEnvPrefix . "NODE_OPTIONS=\"--openssl-legacy-provider\" $envString $buildCommand");
            info("✅ Vite build complete (with legacy OpenSSL)");
        } catch (\Exception $e2) {
            error("❌ Vite build failed with both modern and legacy methods");
            throw $e2;
        }
    }
})->desc('Generic Vite build with environment variables and Node.js compatibility');

///////////////////////////////////////////////////////////////////////////////
// DATABASE TASKS
///////////////////////////////////////////////////////////////////////////////

// [Framework-specific database tasks moved to recipes/klytron-laravel-recipe.php]

///////////////////////////////////////////////////////////////////////////////
// STORAGE AND CACHE TASKS
///////////////////////////////////////////////////////////////////////////////

// [Framework-specific storage and cache tasks moved to recipes/klytron-laravel-recipe.php]

///////////////////////////////////////////////////////////////////////////////
// SERVER CONFIGURATION TASKS
///////////////////////////////////////////////////////////////////////////////

// [Framework-specific server configuration tasks moved to recipes/klytron-laravel-recipe.php]

///////////////////////////////////////////////////////////////////////////////
// NOTIFICATION TASKS
///////////////////////////////////////////////////////////////////////////////

// [Framework-specific notification tasks moved to recipes/klytron-laravel-recipe.php]

///////////////////////////////////////////////////////////////////////////////
// INFORMATION DISPLAY TASKS
///////////////////////////////////////////////////////////////////////////////

task('klytron:deploy:info', function () {
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

    // call the built-in Function info for server Info
    invoke('deploy:info');
})->desc('Display comprehensive deployment information');

///////////////////////////////////////////////////////////////////////////////
// SYSTEM TASKS
///////////////////////////////////////////////////////////////////////////////

task('klytron:system:restart', function () {
    //https://www.cyberciti.biz/faq/howto-reboot-linux/
    //https://opensource.com/article/19/7/reboot-linux
    //https://laracasts.com/discuss/channels/laravel/deployer
    //https://linuxize.com/post/reboot-linux-using-command-line/
    //https://www.geeksforgeeks.org/reboot-command-in-linux-with-examples/

    //Reboot Immediately
    //run('sudo reboot');

    //waits 1 minute before rebooting
    run('sudo shutdown -r');

    //this is to allow deploy to complete and return success response instead of
    //failed response due to immediate rebooting.
})->desc('Restart the system after deployment');


task('klytron:deploy:access_permissions', function () {
    info("🔐 Setting file permissions and ownership...");

    // Get user and group from configuration
    $httpUser = get('http_user');
    $httpGroup = get('http_group');
    $webServerUser = 'www-data'; // Default web server user
    
    // Get default permissions from configuration
    $filePerms = get('default_file_permissions', 0644);
    $dirPerms = get('default_dir_permissions', 0755);
    $filePermsOct = decoct($filePerms);
    $dirPermsOct = decoct($dirPerms);

    // Add web server user to the http_group if different
    if ($httpGroup !== 'www-data' && $httpGroup !== $webServerUser) {
        run("sudo usermod -a -G $httpGroup $webServerUser 2>/dev/null || true");
        info("✅ Added web server user '$webServerUser' to group '$httpGroup'");
    }

    // Set ownership for the entire deployment
    run("sudo chown -R $httpUser:$httpGroup {{deploy_path}}");
    info("✅ Set ownership for deployment directory to $httpUser:$httpGroup");

    // Set ownership for public HTML if configured
    $publicHtml = get('application_public_html');
    if (!empty($publicHtml)) {
        run("sudo chown -R $httpUser:$httpGroup $publicHtml");
        info("✅ Set ownership for public HTML directory to $httpUser:$httpGroup");
    }

    // First, handle the .htaccess file in the public directory (follow symlinks)
    $htaccessPath = '{{deploy_path}}/current/public/.htaccess';
    
    // Check if .htaccess exists and is a symlink
    if (test("[ -L $htaccessPath ]")) {
        // Handle symlink - get the real path and set permissions on the target
        $realPath = run("readlink -f $htaccessPath");
        if (!empty($realPath)) {
            run("sudo chmod $filePermsOct $realPath");
            run("sudo chown $httpUser:$httpGroup $realPath");
            info("✅ Set correct permissions for .htaccess symlink target at $realPath");
        }
    } 
    
    // Also handle any other .htaccess files in the project
    run('find {{deploy_path}}/current -name ".htaccess" -type f -exec sudo chmod ' . $filePermsOct . ' {} +');
    run('find {{deploy_path}}/current -name ".htaccess" -type f -exec sudo chown $httpUser:$httpGroup {} +');
    
    // Handle the public directory if it's a symlink
    if (test("[ -L {{deploy_path}}/current/public ]")) {
        $publicRealPath = run("readlink -f {{deploy_path}}/current/public");
        if (!empty($publicRealPath)) {
            run("sudo chmod -R $dirPermsOct $publicRealPath");
            run("sudo find $publicRealPath -type f -name '.htaccess' -exec sudo chmod $filePermsOct {} +");
            info("✅ Set correct permissions for files in symlinked public directory");
        }
    }
    
    // Handle the public_html .htaccess file using configured path
    $publicHtml = get('application_public_html', '');
    if (!empty($publicHtml)) {
        $publicHtaccess = rtrim($publicHtml, '/') . '/.htaccess';
        if (test("[ -f $publicHtaccess ]")) {
            run("sudo chmod $filePermsOct $publicHtaccess");
            run("sudo chown $httpUser:$httpGroup $publicHtaccess");
            info("✅ Set correct permissions for $publicHtaccess");
        } else {
            info("ℹ️ .htaccess not found at $publicHtaccess");
        }
    }

    // Set the setgid bit on directories to ensure new files inherit the group
    run('find {{deploy_path}}/current -type d -exec sudo chmod g+s {} \\;');
    info("✅ Set setgid bit on directories for proper group inheritance");

    // Ensure the web server can access the directories with configured permissions
    run('find {{deploy_path}}/current -type d -exec sudo chmod ' . $dirPermsOct . ' {} +');
    run('find {{deploy_path}}/current -type f -exec sudo chmod ' . $filePermsOct . ' {} +');
    
    // Special handling for storage and bootstrap/cache in Laravel
    if (has('laravel')) {
        $storagePerms = get('laravel_storage_permissions', 0775);
        $cachePerms = get('laravel_cache_permissions', 0775);
        
        run('chmod -R ' . $storagePerms . ' {{deploy_path}}/current/storage');
        run('chmod -R ' . $cachePerms . ' {{deploy_path}}/current/bootstrap/cache');
        info("✅ Set special permissions for Laravel storage and cache directories");
    }

    info("✅ File permissions and ownership set successfully");
})->desc('Set proper file permissions and ownership');


task('klytron:deploy:create:server_symlink', function () {
    info("🔗 Setting up web server symlink for domain...");

    // Get the configurable public directory path
    $publicDirPath = get('public_dir_path');
    if (empty($publicDirPath)) {
        info("ℹ️ No public directory path configured (public_dir_path), skipping server symlink creation.");
        info("💡 Tip: Set 'public_dir_path' in your deploy.php or let your framework recipe set it automatically.");
        return;
    }

    // Check if the target public directory exists
    if (!test("[ -d \"$publicDirPath\" ]")) {
        error("❌ Public directory not found: $publicDirPath");
        throw new \RuntimeException("Public directory not found: $publicDirPath");
    }

    $publicHtml = get('application_public_html');
    if (empty($publicHtml)) {
        info("ℹ️ No public HTML path configured, skipping server symlink creation.");
        return;
    }

    info("🎯 Target symlink: $publicHtml");
    info("🎯 Source directory: $publicDirPath");

    // Remove existing symlink or directory
    if (test("[ -L \"$publicHtml\" ]")) {
        run("rm \"$publicHtml\"");
        info("🗑️ Removed existing symlink");
    } elseif (test("[ -d \"$publicHtml\" ]")) {
        run("rm -rf \"$publicHtml\"");
        info("🗑️ Removed existing directory");
    } elseif (test("[ -f \"$publicHtml\" ]")) {
        run("rm \"$publicHtml\"");
        info("🗑️ Removed existing file");
    }

    // Create the symlink
    run("ln -sf \"$publicDirPath\" \"$publicHtml\"");

    // Verify the symlink was created
    if (test("[ -L \"$publicHtml\" ]")) {
        info("✅ Server symlink created successfully");

        // Show the symlink details for verification
        $linkDetails = run("ls -la \"$publicHtml\"");
        info("🔗 Symlink details: $linkDetails");
    } else {
        error("❌ Failed to create symlink");

        // Debug information
        $parentDir = dirname($publicHtml);
        if (test("[ -d \"$parentDir\" ]")) {
            info("📁 Parent directory exists: $parentDir");
            $permissions = run("ls -la \"$parentDir\" | grep " . basename($publicHtml) . " || echo 'Target not found'");
            info("🔍 Debug info: $permissions");
        } else {
            error("❌ Parent directory does not exist: $parentDir");
        }

        throw new \RuntimeException("Failed to create symlink");
    }
})->desc('Create web server symlink for domain');

/**
 * Create additional web server symlinks for alias domains
 * Configure with `application_public_html_aliases` (array or string)
 */
task('klytron:deploy:create:server_symlink_aliases', function () {
    info("🔗 Setting up additional web server symlinks for alias domains...");

    // Source public directory
    $publicDirPath = get('public_dir_path');
    if (empty($publicDirPath) || !test("[ -d \"$publicDirPath\" ]")) {
        info("ℹ️ No valid public_dir_path found; skipping alias symlink creation.");
        return;
    }

    // Fetch aliases from configuration (string, array of strings, or array of maps with per-alias owner)
    $aliases = [];
    if (has('application_public_html_aliases')) {
        $raw = get('application_public_html_aliases');
        if (is_string($raw) && trim($raw) !== '') {
            $aliases = [ [ 'path' => trim($raw) ] ];
        } elseif (is_array($raw)) {
            $normalized = [];
            foreach ($raw as $entry) {
                if (is_string($entry)) {
                    $path = trim($entry);
                    if ($path !== '') {
                        $normalized[] = [ 'path' => $path ];
                    }
                } elseif (is_array($entry)) {
                    // Accept keys: path, user, group (optional)
                    $path = $entry['path'] ?? ($entry[0] ?? null);
                    if (is_string($path)) {
                        $path = trim($path);
                    }
                    if (!empty($path)) {
                        $alias = [ 'path' => $path ];
                        if (!empty($entry['user'])) { $alias['user'] = $entry['user']; }
                        if (!empty($entry['group'])) { $alias['group'] = $entry['group']; }
                        $normalized[] = $alias;
                    }
                }
            }
            $aliases = $normalized;
        }
    }

    if (empty($aliases)) {
        info("ℹ️ No alias domains configured (application_public_html_aliases). Skipping.");
        return;
    }

    foreach ($aliases as $aliasEntry) {
        $target = is_array($aliasEntry) ? ($aliasEntry['path'] ?? '') : (string)$aliasEntry;
        $aliasUser = is_array($aliasEntry) ? ($aliasEntry['user'] ?? null) : null;
        $aliasGroup = is_array($aliasEntry) ? ($aliasEntry['group'] ?? null) : null;
        if (empty($target)) { continue; }

        info("— Alias target: $target");

        // Ensure parent directory exists
        $parent = dirname($target);
        run("mkdir -p \"$parent\"");

        // Remove existing entity at target path
        if (test("[ -L \"$target\" ]")) {
            run("rm \"$target\"");
            info("🗑️ Removed existing symlink");
        } elseif (test("[ -d \"$target\" ]")) {
            $backupPath = $target . '_backup_' . date('Y-m-d_H-i-s');
            run("mv \"$target\" \"$backupPath\"");
            warning("⚠️ Existing directory moved to backup: $backupPath");
        } elseif (test("[ -f \"$target\" ]")) {
            run("rm \"$target\"");
            info("🗑️ Removed existing file");
        }

        // Create alias symlink
        run("ln -sfn \"$publicDirPath\" \"$target\"");

        // Verify and set symlink ownership
        if (test("[ -L \"$target\" ]")) {
            // Priority: per-alias user/group -> host http_user/http_group -> parent owner
            $ownerGroup = '';
            if (!empty($aliasUser) && !empty($aliasGroup)) {
                $ownerGroup = $aliasUser . ':' . $aliasGroup;
            } elseif (has('http_user') && has('http_group') && !empty(get('http_user')) && !empty(get('http_group'))) {
                $ownerGroup = get('http_user') . ':' . get('http_group');
            } else {
                $ownerGroup = run("stat -c '%U:%G' \"$parent\"");
            }

            if (!empty($ownerGroup)) {
                run("chown -h $ownerGroup \"$target\"");
            }
            info("✅ Created alias symlink: $target → $publicDirPath");
        } else {
            error("❌ Failed to create alias symlink at $target");
            throw new \RuntimeException("Failed to create alias symlink at $target");
        }
    }
})->desc('Create web server symlinks for alias domains');

task('klytron:deploy:env', [
    'klytron:upload:env:production',
]);

task('klytron:upload:env:production', function () {
    $envFileLocal = get('env_file_local');
    $envFileRemote = get('env_file_remote');
    
    info("📤 Uploading environment file: $envFileLocal -> $envFileRemote");

    // Get the configurable shared directory path
    $sharedDirPath = get('shared_dir_path');
    if (empty($sharedDirPath)) {
        info("ℹ️ No shared directory path configured (shared_dir_path), skipping env file upload.");
        info("💡 Tip: Set 'shared_dir_path' in your deploy.php or let your framework recipe set it automatically.");
        return;
    }

    $envFilePath = "$sharedDirPath/{$envFileRemote}";

    upload($envFileLocal, $envFilePath, [
        'timeout' => null,
        'progress' => true,
        'display_stats' => true
    ]);
    info("✅ Uploaded {$envFileLocal} file to {$envFilePath}");
});

///////////////////////////////////////////////////////////////////////////////
// DOMAIN VALIDATION TASKS
///////////////////////////////////////////////////////////////////////////////

task('klytron:validate:domain', function () {
    info("🌐 Validating domain configuration...");

    $domain = get('application_public_domain');
    $publicHtml = get('application_public_html');

    if (empty($domain)) {
        warning("⚠️  No domain configured. Please set 'application_public_domain'.");
        warning("💡 You can use: klytron_set_domain('your-domain.com') in your deploy.php");
        return;
    }

    info("🔍 Domain: $domain");

    if (empty($publicHtml)) {
        warning("⚠️  No public HTML path configured. Please set 'application_public_html'.");
        warning("💡 You can use: klytron_set_paths('/parent/dir', '/public/html/path') in your deploy.php");
        return;
    }

    info("📁 Using configured public HTML path: $publicHtml");

    // Check if public HTML directory exists
    if (!test("[ -d \"$publicHtml\" ]")) {
        warning("⚠️  Public HTML directory does not exist: $publicHtml");
        warning("💡 The directory will be created during deployment if needed");
    } else {
        info("✅ Public HTML directory exists: $publicHtml");
    }

    info("✅ Domain validation completed");
})->desc('Validate and configure domain settings');

///////////////////////////////////////////////////////////////////////////////
// DEPLOYMENT CONFIRMATION TASKS
///////////////////////////////////////////////////////////////////////////////

task('klytron:deploy:confirm', function () {
    // This task is intentionally empty as confirmation is now handled by interactive questions
    // Kept for backward compatibility with existing deployment flows
    info("✅ Deployment confirmation completed");
})->desc('Deployment confirmation (handled by interactive questions)');

///////////////////////////////////////////////////////////////////////////////
// BACKUP TASKS
///////////////////////////////////////////////////////////////////////////////

task('klytron:deploy:backup:create', function () {
    $shouldBackup = get('shouldBackupBeforeDeployment', true);
    if (!$shouldBackup) {
        info("Skipping backup creation as configured.");
        return;
    }

    info("💾 Creating deployment backup...");

    $backupDir = get('deploy_path') . '/backups';
    $timestamp = date('Y-m-d_H-i-s');
    $backupName = "backup_before_deploy_$timestamp";
    $backupPath = "$backupDir/$backupName";

    // Create backup directory if it doesn't exist
    run("mkdir -p '$backupDir'");

    // Check if current release exists
    if (test('[ -d "{{deploy_path}}/current" ]')) {
        info("📁 Backing up current release...");
        run("cp -r {{deploy_path}}/current '$backupPath'");
        info("✅ Current release backed up to: $backupPath");

        // Also backup the database if configured and database type is not 'none'
        $shouldBackupDb = get('shouldBackupDatabase', true);
        $databaseType = get('database_type', 'none');

        if ($shouldBackupDb && $databaseType !== 'none') {
            info("📊 Backing up database...");
            $dbBackupFile = "$backupPath/database_$timestamp.sql";

            try {
                // Framework-agnostic database backup
                $dbConnection = get('database_type', 'mysql');
                $dbHost = get('db_host', 'localhost');
                $dbPort = get('db_port', '3306');
                $dbName = get('db_name', '');
                $dbUser = get('db_user', '');
                $dbPass = get('db_pass', '');

                if (empty($dbName) || empty($dbUser)) {
                    warning("⚠️  Database credentials not configured, skipping database backup");
                } else {
                    // Create database backup based on type
                    switch ($dbConnection) {
                        case 'mysql':
                        case 'mariadb':
                            run("mysqldump -h '$dbHost' -P '$dbPort' -u '$dbUser' -p'$dbPass' '$dbName' > '$dbBackupFile'");
                            break;
                        case 'postgresql':
                            run("PGPASSWORD='$dbPass' pg_dump -h '$dbHost' -p '$dbPort' -U '$dbUser' '$dbName' > '$dbBackupFile'");
                            break;
                        default:
                            warning("⚠️  Database backup not supported for type: $dbConnection");
                    }

                    if (test("[ -f '$dbBackupFile' ]")) {
                        info("✅ Database backed up to: $dbBackupFile");
                    }
                }
            } catch (\Exception $e) {
                warning("⚠️  Database backup failed: " . $e->getMessage());
                warning("💡 Continuing deployment without database backup");
            }
        }
    } else {
        info("ℹ️  No current release found, skipping backup");
    }

    info("✅ Backup process completed");
})->desc('Creates backup before deployment if configured');

///////////////////////////////////////////////////////////////////////////////
// SUCCESS NOTIFICATION TASKS
///////////////////////////////////////////////////////////////////////////////

task('klytron:deploy:success', function () {
    $startTime = get('deploy_start_time', time());
    $deployTime = time() - $startTime;
    $minutes = floor($deployTime / 60);
    $seconds = $deployTime % 60;

    info("==================================");
    info("🎉 DEPLOYMENT SUCCESSFUL! 🎉");
    info("==================================");

    $timeString = $minutes > 0 ? "{$minutes}m {$seconds}s" : "{$seconds}s";
    info("⏱️  Total deployment time: $timeString");

    $domain = get('application_public_domain', 'your-domain.com');
    if ($domain !== 'your-domain.com') {
        info("🌐 Your application is now live at: https://$domain");
    }

    info("==================================");
})->desc('Display deployment success message');

///////////////////////////////////////////////////////////////////////////////
// FRAMEWORK-AGNOSTIC GROUP TASKS
///////////////////////////////////////////////////////////////////////////////

/**
 * Group task for environment deployment
 */
task('klytron:deploy:environment:complete', [
    'klytron:upload:env:production',
    'klytron:deploy:env'
])->desc('Complete environment file deployment');

/**
 * Group task for deployment completion notification
 */
task('klytron:deploy:notify:complete', [
    'klytron:deploy:success'
])->desc('Complete deployment success notification');

/**
 * Group task for basic validation
 */
task('klytron:validate:basic', [
    'klytron:validate:deploy_path_parent',
    'klytron:validate:domain'
])->desc('Run basic validation checks');

/**
 * Group task for backup and preparation
 */
task('klytron:deploy:prepare:complete', function () {
    invoke('klytron:deploy:confirm');
    if (get('shouldBackupBeforeDeployment', false)) {
        invoke('klytron:deploy:backup:create');
    }
})->desc('Complete deployment preparation with backup');

/**
 * Group task for post-deployment finalization
 */
task('klytron:deploy:finalize:complete', [
    'klytron:deploy:create:server_symlink',
    'klytron:deploy:create:server_symlink_aliases',
    'klytron:deploy:access_permissions'
])->desc('Complete post-deployment finalization');

///////////////////////////////////////////////////////////////////////////////
// GROUP TASKS - Combine related tasks with their dependencies
///////////////////////////////////////////////////////////////////////////////

// [All group tasks that depend on framework-specific logic moved to recipes/klytron-laravel-recipe.php]

// Only add new framework-agnostic tasks and helpers below this line
///////////////////////////////////////////////////////////////////////////////
// GENERIC DEPLOYMENT HOOKS
///////////////////////////////////////////////////////////////////////////////

// Generic deployment failure handling - ensures deploy:unlock is always called
after('deploy:failed', 'deploy:unlock');

///////////////////////////////////////////////////////////////////////////////
// PROJECT MANAGEMENT TASKS
///////////////////////////////////////////////////////////////////////////////

/**
 * Delete the entire project from the production server
 * ⚠️  WARNING: This will permanently delete all project files and data!
 */
task('klytron:delete:project', function () {
    info("🗑️  DELETE PROJECT FROM PRODUCTION SERVER");
    info("⚠️  WARNING: This will permanently delete all project files and data!");
    info("📍 Target: {{deploy_path}}");
    
    // Safety confirmation
    $confirm = askConfirmation("Are you absolutely sure you want to delete the entire project?", false);
    if (!$confirm) {
        info("❌ Project deletion cancelled.");
        return;
    }
    
    // Double confirmation for safety
    $doubleConfirm = ask("This action cannot be undone. Type 'DELETE' to confirm:");
    if (trim($doubleConfirm) !== 'DELETE') {
        info("❌ Project deletion cancelled - incorrect confirmation.");
        return;
    }
    
    info("🚨 DELETING PROJECT FROM PRODUCTION SERVER...");
    
    try {
        // Check if deploy path exists
        if (test("[ -d {{deploy_path}} ]")) {
            // Remove the entire deploy directory
            run("rm -rf {{deploy_path}}");
            info("✅ Project directory deleted: {{deploy_path}}");
        } else {
            info("ℹ️  Project directory does not exist: {{deploy_path}}");
        }
        
        // Also check for any related directories that might exist
        $projectName = get('application');
        if ($projectName) {
            $possiblePaths = [
                "/var/www/$projectName",
                "/opt/$projectName",
                get('deploy_path_parent', '{{deploy_path}}/..') . "/$projectName",
                '~/' . get('remote_user', 'deploy') . "/$projectName"
            ];
            
            foreach ($possiblePaths as $path) {
                if (test("[ -d $path ]")) {
                    info("🗑️  Found additional project directory: $path");
                    $remove = askConfirmation("Delete additional directory: $path?", false);
                    if ($remove) {
                        run("rm -rf $path");
                        info("✅ Deleted: $path");
                    }
                }
            }
        }
        
        info("✅ Project deletion completed successfully!");
        info("🆆 You can now run a fresh deployment with: vendor/bin/dep deploy");
        
    } catch (\Exception $e) {
        error("❌ Error deleting project: " . $e->getMessage());
        throw $e;
    }
})->desc('Delete the entire project from production server (⚠️ DESTRUCTIVE)');

///////////////////////////////////////////////////////////////////////////////
// ASSET AND OPTIMIZATION TASKS
///////////////////////////////////////////////////////////////////////////////

// Advanced asset tasks are implemented using external classes
// ensure you load Composer autoloader to use them

/**
 * Map asset files for database compatibility
 */
task('klytron:assets:map', function () {
    if (!get('supports_vite', false) && !get('supports_mix', false)) {
        info("⏭️  Skipping asset mapping - neither Vite nor Mix is enabled");
        return;
    }
    
    if (!get('cleanup_assets', true)) {
        info("⏭️  Skipping asset mapping - cleanup_assets is disabled");
        return;
    }
    AssetMappingTask::mapAssets();
})->desc('Map asset files for database compatibility');

/**
 * Clean up problematic .htaccess files
 */
task('klytron:assets:cleanup', function () {
    if (!get('cleanup_assets', true)) {
        info("⏭️  Skipping asset cleanup - cleanup_assets is disabled");
        return;
    }
    AssetMappingTask::cleanupHtaccess();
})->desc('Clean up problematic .htaccess files');

/**
 * Verify font files exist and are accessible
 */
task('klytron:fonts:verify', function () {
    if (!get('verify_fonts', true)) {
        info("⏭️  Skipping font verification - verify_fonts is disabled");
        return;
    }
    AssetMappingTask::verifyFonts();
})->desc('Verify font files exist and are accessible');

/**
 * Debug font loading issues
 */
task('klytron:fonts:debug', function () {
    AssetMappingTask::debugFonts();
})->desc('Debug font loading issues');

/**
 * Generate sitemap
 */
task('klytron:sitemap:generate', function () {
    if (!get('supports_sitemap', false)) {
        info("⏭️  Skipping sitemap generation - supports_sitemap is false");
        return;
    }
    SitemapTask::generateSitemap();
})->desc('Generate sitemap for application');

/**
 * Verify sitemap was generated
 */
task('klytron:sitemap:verify', function () {
    if (!get('supports_sitemap', false)) {
        info("⏭️  Skipping sitemap verification - supports_sitemap is false");
        return;
    }
    SitemapTask::verifySitemap();
})->desc('Verify sitemap generation');

/**
 * Check sitemap accessibility
 */
task('klytron:sitemap:check', function () {
    if (!get('supports_sitemap', false)) {
        info("⏭️  Skipping sitemap check - supports_sitemap is false");
        return;
    }
    SitemapTask::checkSitemapAccessibility();
})->desc('Check sitemap accessibility via HTTP');

/**
 * Optimize images
 */
task('klytron:images:optimize', function () {
    if (!get('optimize_images', false)) {
        info("⏭️  Skipping image optimization - optimize_images is false");
        return;
    }
    ImageOptimizationTask::optimizeImages();
})->desc('Optimize uploaded images');

///////////////////////////////////////////////////////////////////////////////
// DEPLOYMENT METRICS TASKS
///////////////////////////////////////////////////////////////////////////////

/**
 * Display deployment metrics summary
 */
task('klytron:metrics:display', function () {
    info("⏭️  Skipping metrics display - task not available in current configuration");
})->desc('Display deployment metrics summary (disabled)');

/**
 * Export metrics to file for analysis
 */
task('klytron:metrics:export', function () {
    info("⏭️  Skipping metrics export - task not available in current configuration");
})->desc('Export deployment metrics to file (disabled)');

/**
 * Compare with previous deployment metrics
 */
task('klytron:metrics:compare', function () {
    info("⏭️  Skipping metrics comparison - task not available in current configuration");
})->desc('Compare with previous deployment metrics (disabled)');

/**
 * Start deployment metrics collection
 */
task('klytron:metrics:start', function () {
    info("⏭️  Skipping metrics collection - task not available in current configuration");
})->desc('Start deployment metrics collection (disabled)');

/**
 * End deployment metrics collection
 */
task('klytron:metrics:end', function () {
    info("⏭️  Skipping metrics collection - task not available in current configuration");
})->desc('End deployment metrics collection (disabled)');

///////////////////////////////////////////////////////////////////////////////
// ENVIRONMENT DECRYPTION TASKS
///////////////////////////////////////////////////////////////////////////////

/**
 * Decrypt production environment file
 */
task('klytron:env:decrypt', function () {
    info("⏭️  Skipping env decryption - task not available in current configuration");
})->desc('Decrypt environment files (disabled)');

/**
 * Decrypt specific environment file
 */
task('klytron:env:decrypt:production', function () {
    info("⏭️  Skipping env decryption - task not available in current configuration");
})->desc('Decrypt production environment file (disabled)');

/**
 * Validate encrypted environment files
 */
task('klytron:env:validate', function () {
    info("⏭️  Skipping env validation - task not available in current configuration");
})->desc('Validate encrypted environment files (disabled)');

/**
 * Setup environment decryption
 */
task('klytron:env:setup', function () {
    info("⏭️  Skipping env setup - task not available in current configuration");
})->desc('Setup environment decryption configuration (disabled)');
