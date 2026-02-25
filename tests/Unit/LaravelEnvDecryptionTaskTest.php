<?php

namespace Klytron\PhpDeploymentKit\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Tests for Laravel Environment Decryption Task
 * 
 * These tests verify that the local env file decryption task
 * (klytron:laravel:local:env:ensure_decrypted) is properly configured
 * and handles various scenarios correctly.
 */
class LaravelEnvDecryptionTaskTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock Laravel environment
        if (!defined('LARAVEL_START')) {
            define('LARAVEL_START', true);
        }
    }

    /**
     * Test that the env decryption task exists in the Laravel recipe
     */
    public function testEnvDecryptionTaskExists(): void
    {
        $recipePath = __DIR__ . '/../../recipes/klytron-laravel-recipe.php';
        $this->assertFileExists($recipePath);
        
        $content = file_get_contents($recipePath);
        $this->assertStringContainsString(
            'klytron:laravel:local:env:ensure_decrypted',
            $content,
            'Task klytron:laravel:local:env:ensure_decrypted should exist'
        );
    }

    /**
     * Test that the task has proper description
     */
    public function testEnvDecryptionTaskHasDescription(): void
    {
        $recipePath = __DIR__ . '/../../recipes/klytron-laravel-recipe.php';
        $content = file_get_contents($recipePath);
        
        $this->assertStringContainsString(
            "Ensure plaintext env files exist locally by decrypting from encrypted versions before deployment",
            $content,
            'Task should have descriptive documentation'
        );
    }

    /**
     * Test that the deployment hook is properly registered
     */
    public function testEnvDecryptionHookIsRegistered(): void
    {
        $recipePath = __DIR__ . '/../../recipes/klytron-laravel-recipe.php';
        $content = file_get_contents($recipePath);
        
        // Verify the hook is registered to run before env deployment
        $this->assertStringContainsString(
            "before('klytron:deploy:env', 'klytron:laravel:local:env:ensure_decrypted')",
            $content,
            'Hook should be registered to run before klytron:deploy:env'
        );
    }

    /**
     * Test that the task checks for LARAVEL_ENV_ENCRYPTION_KEY
     */
    public function testTaskChecksForEncryptionKey(): void
    {
        $recipePath = __DIR__ . '/../../recipes/klytron-laravel-recipe.php';
        $content = file_get_contents($recipePath);
        
        $this->assertStringContainsString(
            "LARAVEL_ENV_ENCRYPTION_KEY",
            $content,
            'Task should reference LARAVEL_ENV_ENCRYPTION_KEY environment variable'
        );
        
        $this->assertStringContainsString(
            "getenv('LARAVEL_ENV_ENCRYPTION_KEY')",
            $content,
            'Task should use getenv to retrieve the encryption key'
        );
    }

    /**
     * Test that the task handles both .env and .env.production
     */
    public function testTaskHandlesMultipleEnvFiles(): void
    {
        $recipePath = __DIR__ . '/../../recipes/klytron-laravel-recipe.php';
        $content = file_get_contents($recipePath);
        
        $this->assertStringContainsString(
            '.env.production',
            $content,
            'Task should handle .env.production file'
        );
        
        $this->assertStringContainsString(
            '.env.production.encrypted',
            $content,
            'Task should handle .env.production.encrypted file'
        );
        
        $this->assertStringContainsString(
            '.env.encrypted',
            $content,
            'Task should handle .env.encrypted file'
        );
    }

    /**
     * Test that the task checks for plaintext file existence
     */
    public function testTaskChecksPlaintextFileExists(): void
    {
        $recipePath = __DIR__ . '/../../recipes/klytron-laravel-recipe.php';
        $content = file_get_contents($recipePath);
        
        $this->assertStringContainsString(
            "file_exists(\$plainFile)",
            $content,
            'Task should check if plaintext file exists to avoid unnecessary decryption'
        );
    }

    /**
     * Test that the task validates encrypted file existence
     */
    public function testTaskValidatesEncryptedFile(): void
    {
        $recipePath = __DIR__ . '/../../recipes/klytron-laravel-recipe.php';
        $content = file_get_contents($recipePath);
        
        $this->assertStringContainsString(
            "file_exists(\$encryptedFile)",
            $content,
            'Task should check if encrypted file exists'
        );
        
        $this->assertStringContainsString(
            "Missing required env file",
            $content,
            'Task should throw error if encrypted file is missing'
        );
    }

    /**
     * Test that the task uses proper artisan decrypt command
     */
    public function testTaskUsesArtisanDecryptCommand(): void
    {
        $recipePath = __DIR__ . '/../../recipes/klytron-laravel-recipe.php';
        $content = file_get_contents($recipePath);
        
        $this->assertStringContainsString(
            "artisan env:decrypt",
            $content,
            'Task should use Laravel\'s env:decrypt artisan command'
        );
    }

    /**
     * Test that the task supports production environment flag
     */
    public function testTaskSupportsProductionEnvironment(): void
    {
        $recipePath = __DIR__ . '/../../recipes/klytron-laravel-recipe.php';
        $content = file_get_contents($recipePath);
        
        $this->assertStringContainsString(
            "--env=production",
            $content,
            'Task should support --env=production flag for production files'
        );
    }

    /**
     * Test that the task uses force flag for overwriting
     */
    public function testTaskUsesForceFlag(): void
    {
        $recipePath = __DIR__ . '/../../recipes/klytron-laravel-recipe.php';
        $content = file_get_contents($recipePath);
        
        $this->assertStringContainsString(
            "--force",
            $content,
            'Task should use --force flag to allow overwriting existing files'
        );
    }

    /**
     * Test that the task escapes shell arguments properly
     */
    public function testTaskEscapesShellArguments(): void
    {
        $recipePath = __DIR__ . '/../../recipes/klytron-laravel-recipe.php';
        $content = file_get_contents($recipePath);
        
        $this->assertStringContainsString(
            "escapeshellarg",
            $content,
            'Task should use escapeshellarg for security when passing key to shell'
        );
    }

    /**
     * Test that the task checks exit code and verifies decryption success
     */
    public function testTaskVerifiesDecryptionSuccess(): void
    {
        $recipePath = __DIR__ . '/../../recipes/klytron-laravel-recipe.php';
        $content = file_get_contents($recipePath);
        
        $this->assertStringContainsString(
            "\$exitCode !== 0",
            $content,
            'Task should check exit code from decryption command'
        );
        
        $this->assertStringContainsString(
            "Failed to decrypt",
            $content,
            'Task should provide meaningful error on decryption failure'
        );
    }

    /**
     * Test that the task supports ddev php runner by default
     */
    public function testTaskSupportsDdevRunner(): void
    {
        $recipePath = __DIR__ . '/../../recipes/klytron-laravel-recipe.php';
        $content = file_get_contents($recipePath);
        
        $this->assertStringContainsString(
            "ddev php",
            $content,
            'Task should reference ddev php as default runner'
        );
        
        $this->assertStringContainsString(
            "local_artisan_runner",
            $content,
            'Task should use configurable local_artisan_runner setting'
        );
    }

    /**
     * Test that the task respects env_encryption_environments configuration
     */
    public function testTaskRespectsEnvEncryptionConfig(): void
    {
        $recipePath = __DIR__ . '/../../recipes/klytron-laravel-recipe.php';
        $content = file_get_contents($recipePath);
        
        $this->assertStringContainsString(
            "env_encryption_environments",
            $content,
            'Task should reference env_encryption_environments configuration'
        );
        
        $this->assertStringContainsString(
            "get('env_encryption_environments', [])",
            $content,
            'Task should retrieve environments from deployment configuration'
        );
    }
}
