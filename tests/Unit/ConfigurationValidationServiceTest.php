<?php

namespace Klytron\PhpDeploymentKit\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Klytron\PhpDeploymentKit\Services\ConfigurationValidationService;
use Klytron\PhpDeploymentKit\Exceptions\DeploymentException;

class ConfigurationValidationServiceTest extends TestCase
{
    public function testValidConfigPasses(): void
    {
        $config = [
            'application_name' => 'test-app',
            'repository_url'   => 'https://github.com/org/repo.git',
            'deploy_path'      => '/var/www/test-app',
            'domain'           => 'app.example.com',
            'php_version'      => 'php8.3',
            'http_user'        => 'www-data',
            'branch'           => 'main',
        ];

        $result = ConfigurationValidationService::validateDeploymentConfig($config);

        $this->assertTrue($result['valid'], 'Valid config should pass validation');
        $this->assertEmpty($result['errors']);
    }

    public function testMissingRequiredFieldsFails(): void
    {
        $config = [
            'application_name' => 'test-app',
        ];

        $result = ConfigurationValidationService::validateDeploymentConfig($config);

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);

        $errorFields = array_column($result['errors'], 'field');
        $this->assertContains('repository_url', $errorFields);
        $this->assertContains('deploy_path', $errorFields);
        $this->assertContains('domain', $errorFields);
    }

    public function testInvalidDomainFails(): void
    {
        $config = [
            'application_name' => 'test-app',
            'repository_url'   => 'git@github.com:org/repo.git',
            'deploy_path'      => '/var/www/test-app',
            'domain'           => 'invalid domain with spaces',
        ];

        $result = ConfigurationValidationService::validateDeploymentConfig($config);

        $this->assertFalse($result['valid']);
        $errorFields = array_column($result['errors'], 'field');
        $this->assertContains('domain', $errorFields);
    }

    public function testPathTraversalDetected(): void
    {
        $config = [
            'application_name' => 'test-app',
            'repository_url'   => 'git@github.com:org/repo.git',
            'deploy_path'      => '/var/www/../../etc/passwd',
            'domain'           => 'app.example.com',
        ];

        $result = ConfigurationValidationService::validateDeploymentConfig($config);

        $this->assertFalse($result['valid']);
        $errorFields = array_column($result['errors'], 'field');
        $this->assertContains('deploy_path', $errorFields);
    }

    public function testDeploymentExceptionInstantiation(): void
    {
        $e = new DeploymentException('Test message', ['foo' => 'bar'], 'Check something', 42);

        $this->assertSame('Test message', $e->getMessage());
        $this->assertSame(42, $e->getCode());
        $this->assertSame(['foo' => 'bar'], $e->getContext());
        $this->assertSame('Check something', $e->getSuggestion());
        $this->assertSame('deployment_error', $e->getErrorType());

        $array = $e->toArray();
        $this->assertSame('deployment_error', $array['error_type']);
        $this->assertSame('Test message', $array['message']);
    }
}
