<?php

/**
 * Klytron PHP Deployment Kit - Core Library
 * 
 * A comprehensive, reusable deployment library for PHP applications with framework-specific recipes
 * Extracted from battle-tested deployment configurations
 * 
 * @package Klytron\PhpDeploymentKit
 * @version 1.0
 * @author Michael K. Laweh (klytron) (https://www.klytron.com)
 */

namespace Deployer;

///////////////////////////////////////////////////////////////////////////////
// CHECK IF DEPLOYER FUNCTIONS ARE AVAILABLE
///////////////////////////////////////////////////////////////////////////////

// Deployer CLI loads functions automatically from its phar
// If not available (e.g., being included outside Deployer context), exit early
if (!function_exists('Deployer\set')) {
    return;
}

///////////////////////////////////////////////////////////////////////////////
// KLYTRON DEPLOYER INITIALIZATION

// RECIPE LOADING CONFIGURATION - Add your recipe loading logic here
///////////////////////////////////////////////////////////////////////////////

// Only initialize if not already loaded
if (!get('klytron_deployer_loaded', false)) {
    set('klytron_deployer_loaded', true);

    ///////////////////////////////////////////////////////////////////////////
    // LOAD STANDARD DEPLOYER RECIPES
    ///////////////////////////////////////////////////////////////////////////

    // Load standard deployer recipes automatically
    // This ensures all projects have access to standard deployment tasks
    require 'recipe/common.php';
    
    // Load server configuration recipe
    $serverRecipePath = __DIR__ . '/recipes/klytron-server-recipe.php';
    if (file_exists($serverRecipePath)) {
        require $serverRecipePath;
    }
    
    ///////////////////////////////////////////////////////////////////////////
    // DEFAULT CONFIGURATION
    ///////////////////////////////////////////////////////////////////////////
    
    // Default SSH key paths configuration
    set('ssh_key_paths', [
        'windows' => [
            '%USERPROFILE%\\.ssh\\id_rsa',
            '%USERPROFILE%\\.ssh\\id_ed25519',
        ],
        'unix' => [
            '~/.ssh/id_rsa',
            '~/.ssh/id_ed25519'
        ]
    ]);
    
    // Default PHP binary path
    set('php_binary_path', '/usr/bin/php8.3');
    
    // PHP Configuration - Manual version for dev/prod compatibility
    set('bin/php', function () {
        // Manual PHP version setting for consistent dev/prod compatibility
        // Projects can override this by calling klytron_set_php_version('php8.2')
        $phpPath = get('php_binary_path', '/usr/bin/php8.3');
        return run('which ' . escapeshellarg($phpPath));
    });
    
    // SSH Key Configuration with improved detection
    set('sshKey', function () {
        $customPaths = get('ssh_key_paths', []);
        $sshKeyPaths = [];
        
        // Use custom paths if provided, otherwise use defaults
        if (!empty($customPaths['windows']) || !empty($customPaths['unix'])) {
            $sshKeyPaths = $customPaths;
        } else {
            // Fall back to default paths if custom paths not provided
            $sshKeyPaths = [
                'windows' => [
                    '%USERPROFILE%\.ssh\id_rsa',
                    '%USERPROFILE%\.ssh\id_ed25519',
                ],
                'unix' => [
                    '~/.ssh/id_rsa',
                    '~/.ssh/id_ed25519'
                ]
            ];
        }
        
        // Get the appropriate paths for the current OS
        $osPaths = str_starts_with(strtoupper(PHP_OS), 'WIN') 
            ? ($sshKeyPaths['windows'] ?? []) 
            : ($sshKeyPaths['unix'] ?? []);
        
        foreach ($osPaths as $path) {
            try {
                $expandedPath = runLocally("echo $path");
                if (file_exists($expandedPath) && is_readable($expandedPath)) {
                    // SSH Key found - only show in verbose mode if available
                    return $expandedPath;
                }
            } catch (\Exception $e) {
                // Continue to next path
            }
        }
        
        // Check for SSH agent
        try {
            $agentOutput = runLocally('ssh-add -l 2>/dev/null || echo "no-agent"');
            if ($agentOutput !== 'no-agent' && !str_contains($agentOutput, 'no identities')) {
                // SSH Agent detected with loaded keys
                return 'ssh-agent';
            }
        } catch (\Exception $e) {
            // SSH agent not available
        }
        
        warning("No SSH key found. Please configure SSH key access.");
        return 'no-ssh-key';
    });
    
    // Default deployment settings
    set('keep_releases', 3);
    set('default_stage', 'production');
    set('default_timeout', 1800);
    set('git_tty', true);
    set('ssh_multiplexing', !str_starts_with(strtoupper(PHP_OS), 'WIN'));
    
    // Default deployment configuration variables
    set('shouldRunMigration', false);
    set('shouldResetDatabase', false);
    set('shouldIMportDbFile', false);
    set('shouldRunSeeder', false);
    set('shouldBackupBeforeDeployment', false);
    set('shouldRunPassportInstall', false);
    set('shouldClearAllCaches', true);
    
    // Default paths - MUST be set in deploy.php to prevent deployment to wrong location
    set('deploy_path_parent', 'DEPLOY_PATH_PARENT_NOT_SET_MANDATORY_CONFIG');
    set('application_public_domain', '');
    set('application_public_url', '');
    set('application_public_html', '');
    
    ///////////////////////////////////////////////////////////////////////////
    // SHARED FILES AND DIRECTORIES CONFIGURATION
    ///////////////////////////////////////////////////////////////////////////
    
    // Shared files between releases (persistent across deployments)
    add('shared_files', [
        '.env',                    // Environment configuration
        'public/.htaccess',        // Web server configuration (if customized)
        'storage/oauth-private.key', // Laravel Passport private key
        'storage/oauth-public.key',  // Laravel Passport public key
    ]);
    
    // Shared directories between releases (persistent data)
    add('shared_dirs', [
        'storage',                 // Application storage (logs, cache, sessions)
        'public/uploads',          // User uploaded files
        'public/storage',          // Public storage symlink target
        'bootstrap/cache',         // Bootstrap cache (for performance)
    ]);
    
    // Writable directories (ensure proper permissions)
    add('writable_dirs', [
        'bootstrap/cache',         // Bootstrap cache
        'storage',                 // Storage directory and subdirectories
        'storage/app',             // Application files
        'storage/app/public',      // Public storage
        'storage/framework',       // Framework cache/sessions/views
        'storage/framework/cache', // Framework cache
        'storage/framework/sessions', // Sessions
        'storage/framework/views', // Compiled views
        'storage/logs',            // Application logs
        'public/uploads',          // User uploads
        'public/storage',          // Public storage symlink
    ]);
    
    ///////////////////////////////////////////////////////////////////////////
    // UTILITY FUNCTIONS
    ///////////////////////////////////////////////////////////////////////////
    
    /**
     * Enhanced function to get environment values from .env files
     */
    function klytron_getEnvValue($key, $envPath = '.env.production', $required = false) {
        static $envCache = [];
        
        // Cache key for this file
        $cacheKey = md5($envPath);
        
        // Load and cache file contents if not already cached
        if (!isset($envCache[$cacheKey])) {
            if (!file_exists($envPath)) {
                if ($required) {
                    error("❌ Critical error: .env file not found at: $envPath");
                    throw new \RuntimeException("Required .env file not found at: $envPath");
                }
                return null;
            }
            
            $envCache[$cacheKey] = [];
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach ($lines as $line) {
                $line = trim($line);
                
                // Skip comments and empty lines
                if (empty($line) || str_starts_with($line, '#')) {
                    continue;
                }

                // Check if line contains key=value
                if (str_contains($line, '=')) {
                    [$envKey, $envValue] = explode('=', $line, 2);
                    $envKey = trim($envKey);
                    $envValue = trim($envValue);
                    
                    // Remove quotes if present
                    if ((str_starts_with($envValue, '"') && str_ends_with($envValue, '"')) ||
                        (str_starts_with($envValue, "'") && str_ends_with($envValue, "'"))) {
                        $envValue = substr($envValue, 1, -1);
                    }
                    
                    $envCache[$cacheKey][$envKey] = $envValue;
                }
            }
        }
        
        // Get value from cache
        $value = $envCache[$cacheKey][$key] ?? null;
        
        if ($value !== null) {
            // Handle variable interpolation (e.g., ${APP_URL_DOMAIN})
            if (preg_match_all('/\$\{([^}]+)\}/', $value, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $interpolatedKey = $match[1];
                    $interpolatedValue = klytron_getEnvValue($interpolatedKey, $envPath, false);
                    if ($interpolatedValue !== null) {
                        $value = str_replace($match[0], $interpolatedValue, $value);
                    }
                }
            }
            
            return $value;
        }

        if ($required) {
            error("❌ Critical error: Required environment variable '$key' not found in $envPath");
            throw new \RuntimeException("Required environment variable '$key' not found in $envPath");
        }

        return $value;
    }
    
    /**
     * Validate that required environment variables are set
     */
    function validateRequiredEnvVars(array $requiredVars, $envPath = '.env.production') {
        $missing = [];
        
        foreach ($requiredVars as $var) {
            if (klytron_getEnvValue($var, $envPath, false) === null) {
                $missing[] = $var;
            }
        }
        
        if (!empty($missing)) {
            error("❌ Missing required environment variables: " . implode(', ', $missing));
            throw new \RuntimeException("Missing required environment variables");
        }
    }

    /**
     * Validate that deploy_path_parent is properly configured
     * This prevents deployments to incorrect locations
     */
    function klytron_validate_deploy_path_parent(): void {
        $deployPathParent = get('deploy_path_parent');
        
        // Check if deploy_path_parent is set to the default failure value
        if ($deployPathParent === 'DEPLOY_PATH_PARENT_NOT_SET_MANDATORY_CONFIG' || 
            empty($deployPathParent) || 
            $deployPathParent === '') {
            
            error("❌ CRITICAL ERROR: deploy_path_parent is not properly configured!");
            error("📋 This is a mandatory configuration that must be set in your deploy.php file.");
            error("💡 Example configuration:");
            error("   klytron_configure_app('my-app', 'git@github.com:user/repo.git', [");
            error("       'deploy_path_parent' => '/var/www/my-apps',");
            error("   ]);");
            error("");
            error("🔧 Or using klytron_set_paths():");
            error("   klytron_set_paths('/var/www/my-apps', '/var/www/html');");
            error("");
            error("🚫 Deployment cannot continue without proper deploy_path_parent configuration.");
            error("   This prevents accidental deployments to wrong locations.");
            
            throw new \RuntimeException("deploy_path_parent is not properly configured - deployment blocked for safety");
        }
        
        // Additional validation: check if the path looks reasonable
        if (strlen($deployPathParent) < 3 || !str_starts_with($deployPathParent, '/')) {
            error("❌ WARNING: deploy_path_parent appears to be invalid: '$deployPathParent'");
            error("💡 deploy_path_parent should be an absolute path starting with '/'");
            error("   Example: '/var/www/apps' or '/home/user/web-apps'");
            
            throw new \RuntimeException("deploy_path_parent appears to be invalid - deployment blocked for safety");
        }
        
        // Log the validated path (only if context is available)
        try {
            currentHost();
            info("✅ deploy_path_parent validated: $deployPathParent");
        } catch (\Throwable $e) {
            // Context not available, skip logging
        }
    }
    
    ///////////////////////////////////////////////////////////////////////////
    // CONFIGURATION HELPER FUNCTIONS
    ///////////////////////////////////////////////////////////////////////////

    /**
     * Configure application settings
     */
    function klytron_configure_app(string $name, string $repository, array $config = []): void {
        set('application', $name);
        set('repository', $repository);

        $defaults = [
            'keep_releases' => 3,
            'default_stage' => 'production',
            'default_timeout' => 1800,
            'git_tty' => true,
        ];

        $config = array_merge($defaults, $config);

        foreach ($config as $key => $value) {
            set($key, $value);
        }
    }

    /**
     * Configure a standard Klytron host
     */
    function klytron_host(string $hostname, array $config = []): \Deployer\Host\Host {
        $defaults = [
            'remote_user' => 'root',
            'forward_agent' => true,
            'branch' => 'main',
            'http_user' => 'www-data',
            'http_group' => 'www-data',
            'writable_mode' => 'chmod',
            'writable_use_sudo' => false,
            'writable_chmod_mode' => '0755',
            'writable_chmod_recursive' => true,
            'labels' => ['stage' => 'production'],
        ];

        $config = array_merge($defaults, $config);

        $host = host($hostname)
            ->set('hostname', $hostname)
            ->set('identity_file', '{{sshKey}}');

        foreach ($config as $key => $value) {
            $host->set($key, $value);
        }

        return $host;
    }

    /**
     * Set PHP version for the deployment
     */
    function klytron_set_php_version(string $version): void {
        set('bin/php', function () use ($version) {
            return run("which /usr/bin/$version || which $version");
        });

        // Also set php_version for deployment info display and to prevent provision prompts
        $versionNumber = str_replace('php', '', $version);
        set('php_version', $versionNumber);
    }

    /**
     * Set domain configuration
     */
    function klytron_set_domain(string $domain): void {
        set('application_public_domain', $domain);
        set('application_public_url', "https://{$domain}");
        
        // Auto-resolve public HTML path if template exists
        $publicHtmlTemplate = get('application_public_html_template', '');
        if (!empty($publicHtmlTemplate) && strpos($publicHtmlTemplate, '${APP_URL_DOMAIN}') !== false) {
            $resolvedPath = str_replace('${APP_URL_DOMAIN}', $domain, $publicHtmlTemplate);
            set('application_public_html', $resolvedPath);
        }
    }

    /**
     * Get the resolved public HTML path (with domain interpolation if needed)
     */
    function klytron_get_public_html_path(): string {
        $publicHtmlPath = get('application_public_html', '');
        if (empty($publicHtmlPath)) {
            $template = get('application_public_html_template', '');
            $domain = get('application_public_domain', '');
            if (!empty($template) && !empty($domain)) {
                $publicHtmlPath = str_replace('${APP_URL_DOMAIN}', $domain, $template);
                set('application_public_html', $publicHtmlPath);
            }
        }
        return $publicHtmlPath;
    }

    /**
     * Configure deployment paths
     */
    function klytron_set_paths(string $parentDir, string $publicHtml = ''): void {
        set('deploy_path_parent', $parentDir);
        if (!empty($publicHtml)) {
            // Store the template path (may contain placeholders like ${APP_URL_DOMAIN})
            set('application_public_html_template', $publicHtml);
            
            // If no domain placeholder, set the actual path immediately
            if (strpos($publicHtml, '${APP_URL_DOMAIN}') === false) {
                set('application_public_html', $publicHtml);
            }
        }
    }

    /**
     * Add project-specific shared files
     */
    function klytron_add_shared_files(array $files): void {
        foreach ($files as $file) {
            add('shared_files', [$file]);
        }
    }

    /**
     * Add project-specific shared directories
     */
    function klytron_add_shared_dirs(array $dirs): void {
        foreach ($dirs as $dir) {
            add('shared_dirs', [$dir]);
        }
    }

    /**
     * Add project-specific writable directories
     */
    function klytron_add_writable_dirs(array $dirs): void {
        foreach ($dirs as $dir) {
            add('writable_dirs', [$dir]);
        }
    }

    /**
     * Configure shared files for the project (replaces default)
     */
    function klytron_configure_shared_files(array $files): void {
        set('shared_files', $files);
    }

    /**
     * Configure shared directories for the project (replaces default)
     */
    function klytron_configure_shared_dirs(array $dirs): void {
        set('shared_dirs', $dirs);
    }

    /**
     * Configure writable directories for the project (replaces default)
     */
    function klytron_configure_writable_dirs(array $dirs): void {
        set('writable_dirs', $dirs);
    }

    /**
     * Configure host with project-specific settings (alternative to klytron_host)
     */
    function klytron_configure_host(string $hostname, array $config = []): \Deployer\Host\Host {
        $defaults = [
            'remote_user' => 'root',
            'branch' => 'main',
            'http_user' => 'www-data',
            'http_group' => 'www-data',
            'writable_mode' => 'chmod',
            'writable_use_sudo' => false,
            'writable_chmod_mode' => '0755',
            'writable_chmod_recursive' => true,
            'forward_agent' => true,
            'labels' => ['stage' => 'production'],
        ];

        $hostConfig = array_merge($defaults, $config);

        $host = host($hostname);

        foreach ($hostConfig as $key => $value) {
            $host->set($key, $value);
        }

        // Set deploy path if not already set
        if (!isset($config['deploy_path'])) {
            $host->set('deploy_path', '{{deploy_path_parent}}/{{application}}');
        }

        // Set SSH key
        $host->set('identity_file', '{{sshKey}}');

        // Web server user will be handled by klytron:deploy:access_permissions task
        $httpGroup = $hostConfig['http_group'];
        $webServerUser = 'www-data'; // Default web server user

        // Set SSH options using the correct method for this Deployer version
        $host->set('ssh_arguments', ['-t']); // Force pseudo-terminal allocation for sudo

        return $host;
    }

    /**
     * Set the public directory path for web server symlink
     * This makes the symlink creation framework-agnostic
     */
    function klytron_set_public_dir(string $path): void {
        set('public_dir_path', $path);
    }

    /**
     * Set the shared directory path for shared files
     * This makes shared file operations framework-agnostic
     */
    function klytron_set_shared_dir(string $path): void {
        set('shared_dir_path', $path);
    }

    /**
     * Configure project type and capabilities
     */
    function klytron_configure_project(array $config): void {
        $defaults = [
            'type' => 'laravel',
            'database' => 'mysql',
            'env_file_local' => '.env.production',
            'env_file_remote' => '.env',
            'db_import_path' => 'database/live-db-exports',
            'public_dir_path' => null, // Will be set by framework recipes if not specified
            'shared_dir_path' => null, // Will be set by framework recipes if not specified
            'supports_passport' => false,
            'supports_vite' => true,
            'supports_storage_link' => true,
            'supports_sitemap' => false,
            'enable_encryption' => false, // Enable/disable env file encryption
        ];

        $config = array_merge($defaults, $config);

        set('project_type', $config['type']);
        set('database_type', $config['database']);
        set('env_file_local', $config['env_file_local']);
        set('env_file_remote', $config['env_file_remote']);
        set('db_import_path', $config['db_import_path']);

        // Set public_dir_path only if explicitly provided
        if ($config['public_dir_path'] !== null) {
            set('public_dir_path', $config['public_dir_path']);
        }

        // Set shared_dir_path only if explicitly provided
        if ($config['shared_dir_path'] !== null) {
            set('shared_dir_path', $config['shared_dir_path']);
        }

        set('supports_passport', $config['supports_passport']);
        set('supports_vite', $config['supports_vite']);
        set('supports_storage_link', $config['supports_storage_link']);
        set('supports_sitemap', $config['supports_sitemap']);
        
        // Encryption configuration
        if ($config['enable_encryption']) {
            set('env_encryption_environments', ['production']);
        } else {
            set('env_encryption_environments', []);
        }
    }

    /**
     * Configure database settings
     */
    function klytron_configure_database(string $type, array $config = []): void {
        set('database_type', $type);

        if ($type === 'none') {
            set('supports_passport', false);
            return;
        }

        $defaults = [
            'import_path' => 'database/live-db-exports',
            'supports_migrations' => true,
            'supports_seeders' => true,
        ];

        $config = array_merge($defaults, $config);

        foreach ($config as $key => $value) {
            set("database_{$key}", $value);
        }
    }

    /**
     * Install Laravel addon commands from the library
     *
     * @deprecated This function is deprecated. Commands are now automatically registered
     * via Laravel's service provider discovery. Use composer require klytron/deployer
     * and the commands will be available automatically.
     */
    function klytron_install_laravel_addons(): void {
        $projectType = get('project_type', 'laravel');

        if ($projectType !== 'laravel') {
            return;
        }

        info("📦 Klytron Deployer commands are now automatically registered via service provider.");
        info("🔧 Available commands:");
        info("   • klytron:file:decrypt    - Decrypt files using Laravel's Crypt facade");
        info("   • klytron:file:encrypt    - Encrypt files using Laravel's Crypt facade");
        info("   • klytron:db:search-replace - Search and replace URLs in database");
        info("   • klytron:storage:link-clean - Clean and recreate storage links");
        info("   • klytron:sqlite:setup    - Set up SQLite database files");
        info("✅ No manual installation required - commands are ready to use!");
    }

    ///////////////////////////////////////////////////////////////////////////
    // DEPLOYMENT FLOW FUNCTIONS
    ///////////////////////////////////////////////////////////////////////////

    /**
     * Get the framework-agnostic deployment flow
     * This is the base deployment flow that works with any framework
     */
    function klytron_deploy_flow(): array {
        return [
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
        ];
    }

    /**
     * Get a minimal deployment flow (for simple PHP projects)
     */
    function klytron_deploy_flow_minimal(): array {
        return [
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
        ];
    }

    /**
     * Get the PHP deployment flow (framework-agnostic)
     */
    function klytron_deploy_flow_php(): array {
        return [
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
        ];
    }

    ///////////////////////////////////////////////////////////////////////////
    // LOAD CORE TASKS
    ///////////////////////////////////////////////////////////////////////////

    // Load core tasks from separate file
    require_once __DIR__ . '/klytron-tasks.php';

    // Show loading message
    try {
        writeln("🚀 <info>Klytron Deployer v1.0 loaded</info>");
        writeln("📚 <comment>Use 'dep klytron:help' for available commands</comment>");
    } catch (\Exception $e) {
        // Silently ignore if output fails
    }
}