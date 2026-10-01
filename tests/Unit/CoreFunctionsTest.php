<?php

namespace Klytron\PhpDeploymentKit\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Deployer;

class CoreFunctionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock Deployer functions if needed
        if (!function_exists('Deployer\set')) {
            // Create mock functions for testing
            eval('
                namespace Deployer {
                    function set($key, $value = null) {
                        static $config = [];
                        if (func_num_args() === 1) {
                            return $config[$key] ?? null;
                        }
                        $config[$key] = $value;
                        return $value;
                    }
                    
                    function get($key, $default = null) {
                        $val = set($key);
                        return $val !== null ? $val : $default;
                    }
                    
                    function has($key) {
                        return set($key) !== null;
                    }
                    
                    function run($command) {
                        return "mock_output";
                    }
                    
                    function test($command) {
                        return true;
                    }
                }
            ');
        }

        Deployer\set('ssh_key_paths', [
            'windows' => [
                '%USERPROFILE%\\.ssh\\id_rsa',
                '%USERPROFILE%\\.ssh\\id_ed25519',
            ],
            'unix' => [
                '~/.ssh/id_rsa',
                '~/.ssh/id_ed25519'
            ]
        ]);
        Deployer\set('php_binary_path', '/usr/bin/php8.3');
    }

    public function testSshKeyDetection()
    {
        // Test SSH key path configuration
        $expectedPaths = [
            'windows' => [
                '%USERPROFILE%\\.ssh\\id_rsa',
                '%USERPROFILE%\\.ssh\\id_ed25519',
            ],
            'unix' => [
                '~/.ssh/id_rsa',
                '~/.ssh/id_ed25519'
            ]
        ];

        Deployer\set('ssh_key_paths', $expectedPaths);
        $actualPaths = Deployer\get('ssh_key_paths');

        $this->assertEquals($expectedPaths, $actualPaths);
    }

    public function testPhpBinaryPathConfiguration()
    {
        $expectedPath = '/usr/bin/php8.3';
        Deployer\set('php_binary_path', $expectedPath);
        
        $actualPath = Deployer\get('php_binary_path');
        $this->assertEquals($expectedPath, $actualPath);
    }

    public function testDefaultConfigurationValues()
    {
        // Test that default values are set correctly
        $sshKeyPaths = Deployer\get('ssh_key_paths');
        $this->assertIsArray($sshKeyPaths);
        $this->assertArrayHasKey('windows', $sshKeyPaths);
        $this->assertArrayHasKey('unix', $sshKeyPaths);

        $phpPath = Deployer\get('php_binary_path');
        $this->assertEquals('/usr/bin/php8.3', $phpPath);
    }

    public function testConfigurationOverride()
    {
        // Test that configuration can be overridden
        Deployer\set('php_binary_path', '/custom/php/path');
        $this->assertEquals('/custom/php/path', Deployer\get('php_binary_path'));

        Deployer\set('ssh_key_paths', ['custom' => ['custom/path']]);
        $this->assertEquals(['custom' => ['custom/path']], Deployer\get('ssh_key_paths'));
    }

    public function testFunctionExists()
    {
        // Test that required functions exist
        $this->assertTrue(function_exists('Deployer\set'));
        $this->assertTrue(function_exists('Deployer\get'));
        $this->assertTrue(function_exists('Deployer\has'));
    }

    public function testMaskSecrets(): void
    {
        if (!function_exists('Deployer\klytron_mask_secrets')) {
            require_once __DIR__ . '/../../deployment-kit-core.php';
        }

        $input = <<<ENV
APP_NAME=MyApplication
APP_KEY=base64:AbCdEf123456789=
DB_PASSWORD=SuperSecretPass123!
API_SECRET_TOKEN=xyz_secret_token_999
APP_URL=https://example.com
DEPLOY_TOKEN=my_deploy_token_abc
ENV;

        $masked = \Deployer\klytron_mask_secrets($input);

        $this->assertStringContainsString('APP_NAME=MyApplication', $masked);
        $this->assertStringContainsString('APP_URL=https://example.com', $masked);
        $this->assertStringNotContainsString('AbCdEf123456789', $masked);
        $this->assertStringNotContainsString('SuperSecretPass123!', $masked);
        $this->assertStringNotContainsString('xyz_secret_token_999', $masked);
        $this->assertStringNotContainsString('my_deploy_token_abc', $masked);
        $this->assertStringContainsString('APP_KEY=********', $masked);
        $this->assertStringContainsString('DB_PASSWORD=********', $masked);
        $this->assertStringContainsString('API_SECRET_TOKEN=********', $masked);
        $this->assertStringContainsString('DEPLOY_TOKEN=********', $masked);
    }
}

