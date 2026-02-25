<?php

namespace Klytron\PhpDeploymentKit\Services;
use function Deployer\info;
use function Deployer\warning;
use function Deployer\error;
use function Deployer\run;
use function Deployer\test;
use function Deployer\get;
use function Deployer\set;

/**
 * Configuration Validation Service
 */
class ConfigurationValidationService
{
    private static array $errors = [];
    private static array $warnings = [];

    public static function validateDeploymentConfig(array $config): array
    {
        self::$errors = [];
        self::$warnings = [];

        // Validate required fields
        self::validateRequired($config, 'application_name', 'Application name is required');
        self::validateRequired($config, 'repository_url', 'Repository URL is required');
        self::validateRequired($config, 'deploy_path', 'Deploy path is required');
        self::validateRequired($config, 'domain', 'Domain is required');

        // Validate URLs
        if (isset($config['repository_url'])) {
            self::validateUrl($config['repository_url'], 'repository_url');
        }
        if (isset($config['domain'])) {
            self::validateDomain($config['domain']);
        }

        // Validate paths
        if (isset($config['deploy_path'])) {
            self::validatePath($config['deploy_path'], 'deploy_path');
        }

        // Validate optional fields
        if (isset($config['php_version'])) {
            self::validatePhpVersion($config['php_version']);
        }

        if (isset($config['http_user'])) {
            self::validateUser($config['http_user'], 'http_user');
        }

        if (isset($config['branch'])) {
            self::validateBranch($config['branch']);
        }

        return [
            'valid' => empty(self::$errors),
            'errors' => self::$errors,
            'warnings' => self::$warnings,
            'summary' => self::getValidationSummary()
        ];
    }

    public static function validateLaravelConfig(array $config): array
    {
        self::$errors = [];
        self::$warnings = [];

        // Run basic validation
        $basicValidation = self::validateDeploymentConfig($config);
        self::$errors = array_merge(self::$errors, $basicValidation['errors']);
        self::$warnings = array_merge(self::$warnings, $basicValidation['warnings']);

        // Laravel-specific validations
        if (isset($config['database'])) {
            self::validateDatabaseConfig($config['database']);
        }

        if (isset($config['supports_vite']) && $config['supports_vite']) {
            self::validateViteConfig($config);
        }

        if (isset($config['supports_storage_link']) && $config['supports_storage_link']) {
            self::validateStorageConfig($config);
        }

        return [
            'valid' => empty(self::$errors),
            'errors' => self::$errors,
            'warnings' => self::$warnings,
            'summary' => self::getValidationSummary()
        ];
    }

    private static function validateRequired(array $config, string $field, string $message): void
    {
        if (empty($config[$field])) {
            self::$errors[] = [
                'field' => $field,
                'message' => $message,
                'type' => 'required'
            ];
        }
    }

    private static function validateUrl(string $url, string $field): void
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            self::$errors[] = [
                'field' => $field,
                'message' => "Invalid URL format: {$url}",
                'type' => 'format',
                'suggestion' => 'Use valid HTTP/HTTPS URL'
            ];
        }

        if (!str_starts_with($url, 'http')) {
            self::$warnings[] = [
                'field' => $field,
                'message' => "URL should use HTTPS for security: {$url}",
                'type' => 'security',
                'suggestion' => 'Use HTTPS URL for better security'
            ];
        }
    }

    private static function validateDomain(string $domain): void
    {
        if (!self::isValidDomain($domain)) {
            self::$errors[] = [
                'field' => 'domain',
                'message' => "Invalid domain format: {$domain}",
                'type' => 'format',
                'suggestion' => 'Use valid domain name (e.g., example.com)'
            ];
        }
    }

    private static function validatePath(string $path, string $field): void
    {
        if (str_contains($path, '..')) {
            self::$errors[] = [
                'field' => $field,
                'message' => "Path contains directory traversal: {$path}",
                'type' => 'security',
                'suggestion' => 'Use absolute path without directory traversal'
            ];
        }

        if (str_starts_with($path, '/')) {
            self::$warnings[] = [
                'field' => $field,
                'message' => "Path should be relative to deploy_path: {$path}",
                'type' => 'best_practice',
                'suggestion' => 'Use relative path from deploy_path'
            ];
        }
    }

    private static function validatePhpVersion(string $version): void
    {
        if (!preg_match('/^\d+\.\d+(\.\d+)?$/', $version)) {
            self::$errors[] = [
                'field' => 'php_version',
                'message' => "Invalid PHP version format: {$version}",
                'type' => 'format',
                'suggestion' => 'Use format like 8.1, 8.2, or 8.1.0'
            ];
        }

        $supportedVersions = ['8.1', '8.2', '8.3'];
        $majorVersion = explode('.', $version)[0] . '.' . explode('.', $version)[1];
        
        if (!in_array($majorVersion, $supportedVersions)) {
            self::$warnings[] = [
                'field' => 'php_version',
                'message' => "PHP version {$version} may not be fully supported",
                'type' => 'compatibility',
                'suggestion' => 'Consider using PHP 8.1+ for best compatibility'
            ];
        }
    }

    private static function validateUser(string $user, string $field): void
    {
        if (empty($user)) {
            return;
        }

        if (in_array(strtolower($user), ['root', 'admin'])) {
            self::$warnings[] = [
                'field' => $field,
                'message' => "Using privileged user '{$user}' may be insecure",
                'type' => 'security',
                'suggestion' => 'Consider using dedicated deployment user'
            ];
        }

        if (!preg_match('/^[a-z_][a-z0-9]*$/', $user)) {
            self::$errors[] = [
                'field' => $field,
                'message' => "Invalid user format: {$user}",
                'type' => 'format',
                'suggestion' => 'Use lowercase alphanumeric with underscores only'
            ];
        }
    }

    private static function validateBranch(string $branch): void
    {
        if (empty($branch)) {
            self::$warnings[] = [
                'field' => 'branch',
                'message' => 'Empty branch name, will use default',
                'type' => 'configuration',
                'suggestion' => 'Specify branch name explicitly'
            ];
        }

        if (!preg_match('/^[a-zA-Z0-9_\/-]+$/', $branch)) {
            self::$errors[] = [
                'field' => 'branch',
                'message' => "Invalid branch name: {$branch}",
                'type' => 'format',
                'suggestion' => 'Use valid git branch name format'
            ];
        }
    }

    private static function validateDatabaseConfig(array $dbConfig): void
    {
        $supportedTypes = ['mysql', 'mariadb', 'postgresql', 'sqlite'];
        
        if (!in_array(strtolower($dbConfig['type'] ?? ''), $supportedTypes)) {
            self::$errors[] = [
                'field' => 'database.type',
                'message' => "Unsupported database type: " . ($dbConfig['type'] ?? ''),
                'type' => 'configuration',
                'suggestion' => 'Use one of: ' . implode(', ', $supportedTypes)
            ];
        }

        if (isset($dbConfig['host']) && empty($dbConfig['host'])) {
            self::$errors[] = [
                'field' => 'database.host',
                'message' => 'Database host cannot be empty',
                'type' => 'required'
            ];
        }
    }

    private static function validateViteConfig(array $config): void
    {
        if (isset($config['vite_config'])) {
            $viteConfig = $config['vite_config'];
            
            if (!is_array($viteConfig)) {
                self::$errors[] = [
                    'field' => 'vite_config',
                    'message' => 'Vite configuration must be an array',
                    'type' => 'format'
                ];
            }
        }
    }

    private static function validateStorageConfig(array $config): void
    {
        if (isset($config['shared_dirs'])) {
            $sharedDirs = $config['shared_dirs'];
            
            if (!is_array($sharedDirs)) {
                self::$errors[] = [
                    'field' => 'shared_dirs',
                    'message' => 'Shared directories must be an array',
                    'type' => 'format'
                ];
            }
        }
    }

    private static function isValidDomain(string $domain): bool
    {
        return (bool)preg_match('/^(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/', $domain);
    }

    private static function getValidationSummary(): string
    {
        $errorCount = count(self::$errors);
        $warningCount = count(self::$warnings);

        if ($errorCount === 0 && $warningCount === 0) {
            return "✅ Configuration validation passed";
        }

        $summary = "Configuration validation results:\n";
        
        if ($errorCount > 0) {
            $summary .= "❌ {$errorCount} error(s) found\n";
            foreach (self::$errors as $error) {
                $summary .= "  - {$error['field']}: {$error['message']}\n";
                if ($error['suggestion']) {
                    $summary .= "    💡 {$error['suggestion']}\n";
                }
            }
        }

        if ($warningCount > 0) {
            $summary .= "⚠️ {$warningCount} warning(s) found\n";
            foreach (self::$warnings as $warning) {
                $summary .= "  - {$warning['field']}: {$warning['message']}\n";
                if ($warning['suggestion']) {
                    $summary .= "    💡 {$warning['suggestion']}\n";
                }
            }
        }

        if ($errorCount === 0 && $warningCount > 0) {
            $summary .= "✅ Configuration is valid with {$warningCount} warning(s)\n";
        }

        return $summary;
    }
}
