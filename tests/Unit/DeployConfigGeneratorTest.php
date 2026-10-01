<?php

namespace Klytron\PhpDeploymentKit\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Klytron\PhpDeploymentKit\Generators\DeployConfigGenerator;

class DeployConfigGeneratorTest extends TestCase
{
    private string $tempDir;
    private DeployConfigGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new DeployConfigGenerator();
        $this->tempDir = sys_get_temp_dir() . '/klytron_test_' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $files = glob($this->tempDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            @unlink($this->tempDir . '/.env.deploy.example');
            rmdir($this->tempDir);
        }
        parent::tearDown();
    }

    public function testGenerateLaravelConfig(): void
    {
        $content = $this->generator->generate('my-awesome-app', 'laravel', [
            'domain' => 'awesome.example.com',
            'repo' => 'git@github.com:my-org/my-awesome-app.git',
        ]);

        $this->assertStringContainsString("klytron_configure_app('my-awesome-app'", $content);
        $this->assertStringContainsString("git@github.com:my-org/my-awesome-app.git", $content);
        $this->assertStringContainsString("klytron_set_domain('awesome.example.com')", $content);
        $this->assertStringContainsString("klytron_configure_host_from_env('DEPLOY_HOST'", $content);
        $this->assertStringContainsString("'remote_user' => 'deploy'", $content);
        $this->assertStringContainsString("'ssh_multiplexing' => true", $content);
    }

    public function testGenerateSimplePhpConfig(): void
    {
        $content = $this->generator->generate('simple-site', 'simple');

        $this->assertStringContainsString("klytron_configure_app('simple-site'", $content);
        $this->assertStringContainsString("klytron_configure_host_from_env('DEPLOY_HOST'", $content);
    }

    public function testGenerateUnknownTemplateThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->generator->generate('app', 'nonexistent-template');
    }

    public function testWriteCreatesDeployPhpAndEnvExample(): void
    {
        $result = $this->generator->write($this->tempDir, 'test-app', 'laravel');

        $this->assertTrue($result['success']);
        $this->assertFileExists($this->tempDir . '/deploy.php');
        $this->assertFileExists($this->tempDir . '/.env.deploy.example');

        $content = file_get_contents($this->tempDir . '/deploy.php');
        $this->assertStringContainsString("klytron_configure_app('test-app'", $content);

        $envContent = file_get_contents($this->tempDir . '/.env.deploy.example');
        $this->assertStringContainsString('DEPLOY_HOST=', $envContent);
        $this->assertStringContainsString('DEPLOY_USER=deploy', $envContent);
    }

    public function testWriteWithoutForceThrowsWhenFileExists(): void
    {
        file_put_contents($this->tempDir . '/deploy.php', '<?php // existing');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('already exists');
        $this->generator->write($this->tempDir, 'test-app', 'laravel', [], false);
    }

    public function testWriteWithForceOverwritesExistingFile(): void
    {
        file_put_contents($this->tempDir . '/deploy.php', '<?php // existing');

        $result = $this->generator->write($this->tempDir, 'test-app', 'laravel', [], true);
        $this->assertTrue($result['success']);

        $content = file_get_contents($this->tempDir . '/deploy.php');
        $this->assertStringContainsString("klytron_configure_app('test-app'", $content);
    }
}
