<?php

namespace Klytron\PhpDeploymentKit\Tests\Unit;

use PHPUnit\Framework\TestCase;

class DeployEnvLoaderTest extends TestCase
{
    private string $tempDir;

    /** Every env key touched by these tests (cleaned up in tearDown). */
    private array $touchedKeys = [
        'KLYTRON_TEST_HOST_A',
        'KLYTRON_TEST_HOST_B',
        'KLYTRON_TEST_USER',
        'KLYTRON_TEST_PORT',
        'KLYTRON_TEST_INTERP',
        'KLYTRON_TEST_BASE',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // Same Deployer stand-in as CoreFunctionsTest: the loader only needs
        // set()/get(), and must stay runnable without a Deployer master.
        if (!function_exists('Deployer\set')) {
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
                }
            ');
        }

        require_once __DIR__ . '/../../deployment-kit-core.php';

        $this->tempDir = sys_get_temp_dir() . '/klytron_deploy_env_' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        foreach ($this->touchedKeys as $key) {
            putenv($key);
            unset($_SERVER[$key], $_ENV[$key]);
        }
        if (function_exists('Deployer\set')) {
            \Deployer\set('deploy_env_files', null);
            \Deployer\set('deploy_env_keys', null);
        }

        foreach (glob($this->tempDir . '/*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        @rmdir($this->tempDir);

        parent::tearDown();
    }

    private function writeFile(string $name, string $content): string
    {
        $path = $this->tempDir . '/' . $name;
        file_put_contents($path, $content);
        return $path;
    }

    public function test_parses_quotes_export_prefix_and_comments(): void
    {
        $path = $this->writeFile('.env.deploy', implode("\n", [
            '# a comment',
            '',
            'export KLYTRON_TEST_HOST_A="quoted-host.example.com"',
            "KLYTRON_TEST_USER='single-user'",
            'KLYTRON_TEST_PORT=2222 # trailing comment',
            'KLYTRON_TEST_BASE=pass#word',
            'NOT-A-KEY=oops',
            '9BAD=start-with-digit',
            'LINE_WITHOUT_EQUALS',
        ]));

        $parsed = \Deployer\klytron_parseDeployEnvFile($path);

        $this->assertSame('quoted-host.example.com', $parsed['KLYTRON_TEST_HOST_A']);
        $this->assertSame('single-user', $parsed['KLYTRON_TEST_USER']);
        $this->assertSame('2222', $parsed['KLYTRON_TEST_PORT']);
        $this->assertSame('pass#word', $parsed['KLYTRON_TEST_BASE']);
        $this->assertArrayNotHasKey('NOT-A-KEY', $parsed);
        $this->assertArrayNotHasKey('9BAD', $parsed);
    }

    public function test_interpolates_from_environment_then_file(): void
    {
        putenv('KLYTRON_TEST_BASE=env-base');
        $path = $this->writeFile('.env.deploy', implode("\n", [
            'KLYTRON_TEST_HOST_A=first',
            'KLYTRON_TEST_HOST_B=${KLYTRON_TEST_HOST_A}-suffix',
            'KLYTRON_TEST_INTERP=${KLYTRON_TEST_BASE}-suffix',
            'KLYTRON_TEST_USER=${MISSING_KEY_STAYS}-literal',
        ]));

        $parsed = \Deployer\klytron_parseDeployEnvFile($path);

        $this->assertSame('first-suffix', $parsed['KLYTRON_TEST_HOST_B']);
        $this->assertSame('env-base-suffix', $parsed['KLYTRON_TEST_INTERP']);
        $this->assertSame('${MISSING_KEY_STAYS}-literal', $parsed['KLYTRON_TEST_USER']);

        putenv('KLYTRON_TEST_BASE');
    }

    public function test_first_file_wins_and_shell_wins_over_files(): void
    {
        $first = $this->writeFile('first.env', "KLYTRON_TEST_HOST_A=file-one.example.com\n");
        $second = $this->writeFile('second.env', "KLYTRON_TEST_HOST_A=file-two.example.com\nKLYTRON_TEST_USER=file-user\n");

        $resolved = \Deployer\klytron_loadDeployEnv(
            [$first, $second],
            ['KLYTRON_TEST_HOST_A', 'KLYTRON_TEST_USER']
        );

        $this->assertSame('file-one.example.com', $resolved['KLYTRON_TEST_HOST_A']['value']);
        $this->assertSame($first, $resolved['KLYTRON_TEST_HOST_A']['source']);
        $this->assertSame('file-user', $resolved['KLYTRON_TEST_USER']['value']);

        // Shell export beats every file.
        putenv('KLYTRON_TEST_HOST_A=shell.example.com');
        $resolved = \Deployer\klytron_loadDeployEnv(
            [$first, $second],
            ['KLYTRON_TEST_HOST_A']
        );
        $this->assertSame('shell.example.com', $resolved['KLYTRON_TEST_HOST_A']['value']);
        $this->assertSame('environment', $resolved['KLYTRON_TEST_HOST_A']['source']);
        putenv('KLYTRON_TEST_HOST_A');
    }

    public function test_missing_and_unreadable_files_are_skipped(): void
    {
        $resolved = \Deployer\klytron_loadDeployEnv(
            [$this->tempDir . '/does-not-exist.env'],
            ['KLYTRON_TEST_HOST_A']
        );

        $this->assertSame([], $resolved);
        $this->assertFalse(getenv('KLYTRON_TEST_HOST_A'));
    }

    public function test_disable_flag_returns_empty_and_injects_nothing(): void
    {
        $file = $this->writeFile('.env.deploy', "KLYTRON_TEST_HOST_A=file.example.com\n");
        putenv('KLYTRON_DEPLOY_ENV_DISABLE=true');

        try {
            $resolved = \Deployer\klytron_loadDeployEnv([$file], ['KLYTRON_TEST_HOST_A']);
            $this->assertSame([], $resolved);
            $this->assertFalse(getenv('KLYTRON_TEST_HOST_A'));
        } finally {
            putenv('KLYTRON_DEPLOY_ENV_DISABLE');
        }
    }

    public function test_default_search_order_prefers_dot_env_deploy(): void
    {
        file_put_contents($this->tempDir . '/.env.deploy', "KLYTRON_TEST_HOST_A=deploy-file.example.com\n");
        file_put_contents($this->tempDir . '/.env.production', "KLYTRON_TEST_HOST_A=prod-file.example.com\n");

        $previousCwd = getcwd();
        chdir($this->tempDir);
        try {
            $resolved = \Deployer\klytron_loadDeployEnv([], ['KLYTRON_TEST_HOST_A']);
            $this->assertSame('deploy-file.example.com', $resolved['KLYTRON_TEST_HOST_A']['value']);

            // Without .env.deploy, .env.production is the fallback.
            unlink($this->tempDir . '/.env.deploy');
            putenv('KLYTRON_TEST_HOST_A');
            unset($_SERVER['KLYTRON_TEST_HOST_A'], $_ENV['KLYTRON_TEST_HOST_A']);
            $resolved = \Deployer\klytron_loadDeployEnv([], ['KLYTRON_TEST_HOST_A']);
            $this->assertSame('prod-file.example.com', $resolved['KLYTRON_TEST_HOST_A']['value']);
        } finally {
            chdir($previousCwd);
        }
    }

    public function test_deploy_env_files_setting_overrides_defaults(): void
    {
        $custom = $this->writeFile('custom.env', "KLYTRON_TEST_HOST_A=custom.example.com\n");
        \Deployer\set('deploy_env_files', [$custom]);

        $previousCwd = getcwd();
        chdir($this->tempDir);
        try {
            // Cwd has no .env.deploy here (removed or never created in this
            // test's dir beyond custom.env), so only the override can resolve.
            $resolved = \Deployer\klytron_loadDeployEnv([], ['KLYTRON_TEST_HOST_A']);
            $this->assertSame('custom.example.com', $resolved['KLYTRON_TEST_HOST_A']['value']);
        } finally {
            chdir($previousCwd);
        }
    }
}
