<?php

namespace Klytron\PhpDeploymentKit\Tasks;
use Exception;
use function Deployer\info;
use function Deployer\warning;
use function Deployer\error;
use function Deployer\run;
use function Deployer\test;
use function Deployer\get;
use function Deployer\set;

use Deployer\Exception\RuntimeException;
use Deployer\Task\TaskParameters;

/**
 * Environment Decryption Task
 * 
 * Handles decryption of Laravel encrypted environment files during deployment.
 * This task ensures that sensitive environment variables are securely deployed
 * without exposing them in version control.
 */
class EnvironmentDecryptTask
{
    /**
     * Decrypt environment file using Laravel's env:decrypt command
     * 
     * @param string $envFile The environment file to decrypt (e.g., 'production')
     * @param string|null $key Optional decryption key
     * @param bool $force Whether to overwrite existing files
     */
    public static function decrypt(string $envFile = 'production', ?string $key = null, bool $force = false): void
    {
        if (!function_exists('Deployer\info')) {
            return;
        }

        $encryptedFile = ".env.{$envFile}.encrypted";
        $targetFile = ".env.{$envFile}";

        info("🔓 Decrypting environment file: {$envFile}");

        try {
            // Check if encrypted file exists
            if (!test("[ -f '{{release_path}}/{$encryptedFile}' ]")) {
                throw new RuntimeException("Encrypted environment file not found: {$encryptedFile}");
            }

            // Build decrypt command
            $command = "cd {{release_path}} && php artisan env:decrypt --env={$envFile}";
            
            if ($force) {
                $command .= " --force";
            }

            if ($key) {
                $command .= " --key={$key}";
            }

            // Set decryption key as environment variable if not provided as parameter
            if (!$key && getenv('LARAVEL_ENV_ENCRYPTION_KEY')) {
                $command = "LARAVEL_ENV_ENCRYPTION_KEY=" . getenv('LARAVEL_ENV_ENCRYPTION_KEY') . " " . $command;
            }

            // Run decryption
            $result = run($command, ['timeout' => 300]);

            if (!$result) {
                throw new RuntimeException("Environment decryption failed for {$envFile}");
            }

            // Verify decrypted file exists
            if (!test("[ -f '{{release_path}}/{$targetFile}' ]")) {
                throw new RuntimeException("Decrypted environment file not created: {$targetFile}");
            }

            info("✅ Environment file decrypted successfully: {$envFile}");

            // Set proper permissions
            run("chmod 600 {{release_path}}/{$targetFile}");
            run("chown {{http_user}}:{{http_group}} {{release_path}}/{$targetFile}");

        } catch (RuntimeException $e) {
            error("❌ Environment decryption failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Decrypt multiple environment files
     * 
     * @param array $environments Array of environment names to decrypt
     * @param string|null $key Optional decryption key
     * @param bool $force Whether to overwrite existing files
     */
    public static function decryptMultiple(array $environments, ?string $key = null, bool $force = false): void
    {
        if (!function_exists('Deployer\info')) {
            return;
        }

        info("🔓 Decrypting multiple environment files...");

        foreach ($environments as $env) {
            self::decrypt($env, $key, $force);
        }

        info("✅ All environment files decrypted successfully");
    }

    /**
     * Validate encrypted environment file
     * 
     * @param string $envFile The environment file to validate
     * @return bool True if valid, false otherwise
     */
    public static function validateEncryptedFile(string $envFile = 'production'): bool
    {
        if (!function_exists('Deployer\test')) {
            return false;
        }

        $encryptedFile = ".env.{$envFile}.encrypted";

        // Check if file exists and is readable
        if (!test("[ -f '{{release_path}}/{$encryptedFile}' ]")) {
            return false;
        }

        // Check if file has content
        $size = run("wc -c < {{release_path}}/{$encryptedFile}");
        if (empty($size) || (int)$size === 0) {
            return false;
        }

        // Check if file has expected format (contains encrypted variables)
        $content = run("head -5 {{release_path}}/{$encryptedFile}");
        if (empty($content) || strpos($content, '=') === false) {
            return false;
        }

        return true;
    }

    /**
     * Get decryption key from environment or prompt
     * 
     * @return string|null The decryption key or null if not found
     */
    public static function getDecryptionKey(): ?string
    {
        // Try environment variable first
        $key = getenv('LARAVEL_ENV_ENCRYPTION_KEY');
        if ($key) {
            return $key;
        }

        // Try Deployer configuration
        $key = get('env_encryption_key', null);
        if ($key) {
            return $key;
        }

        return null;
    }

    /**
     * Setup environment decryption configuration
     * 
     * @param string $key The decryption key
     * @param array $environments Array of environments to decrypt
     */
    public static function setup(string $key, array $environments = ['production']): void
    {
        // Store key in configuration
        set('env_encryption_key', $key);
        set('env_encryption_environments', $environments);
    }
}
