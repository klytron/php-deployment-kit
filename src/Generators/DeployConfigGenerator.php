<?php

namespace Klytron\PhpDeploymentKit\Generators;

/**
 * Generator for canonical deploy.php configuration files.
 *
 * Pre-wires env-driven hosts, path placeholders, and best practices.
 */
class DeployConfigGenerator
{
    /**
     * Map of supported template aliases to template file basenames.
     */
    protected const TEMPLATE_MAP = [
        'laravel' => 'laravel-deploy.php.template',
        'yii2'    => 'deploy.php.template',
        'simple'  => 'simple-php.php.template',
        'php'     => 'simple-php.php.template',
        'generic' => 'deploy.php.template',
    ];

    /**
     * Get the directory where templates are stored.
     */
    public static function getTemplatesDir(): string
    {
        return dirname(__DIR__, 2) . '/templates';
    }

    /**
     * Generate deploy.php configuration content.
     *
     * @param string $appName Application name (e.g. 'my-app')
     * @param string $template Template name ('laravel', 'yii2', 'simple', 'generic')
     * @param array $options Additional options: repo, domain, host
     * @return string
     * @throws \InvalidArgumentException If template is unknown or file not found
     */
    public function generate(string $appName, string $template = 'laravel', array $options = []): string
    {
        $normalizedTemplate = strtolower(trim($template));
        if (!isset(self::TEMPLATE_MAP[$normalizedTemplate])) {
            $supported = implode(', ', array_keys(self::TEMPLATE_MAP));
            throw new \InvalidArgumentException("Unknown template '{$template}'. Supported templates: {$supported}");
        }

        $templateFilename = self::TEMPLATE_MAP[$normalizedTemplate];
        $templatePath = self::getTemplatesDir() . '/' . $templateFilename;

        if (!file_exists($templatePath)) {
            throw new \RuntimeException("Template file not found at: {$templatePath}");
        }

        $content = file_get_contents($templatePath);
        if ($content === false) {
            throw new \RuntimeException("Failed to read template file: {$templatePath}");
        }

        $repo = $options['repo'] ?? "git@github.com:your-org/{$appName}.git";
        $domain = $options['domain'] ?? "{$appName}.com";
        $host = $options['host'] ?? "{$appName}.com";

        $replacements = [
            "'your-project-name'" => "'{$appName}'",
            "'git@github.com:your-org/your-project.git'" => "'{$repo}'",
            "'your-domain.com'" => "'{$domain}'",
            "'your-server.com'" => "'{$host}'",
        ];

        return strtr($content, $replacements);
    }

    /**
     * Generate content for a .env.deploy.example template.
     *
     * @param array $options
     * @return string
     */
    public function generateEnvExample(array $options = []): string
    {
        $host = $options['host'] ?? 'your-server.com';
        $user = $options['user'] ?? 'deploy';
        $branch = $options['branch'] ?? 'main';
        $port = $options['port'] ?? 22;

        return <<<ENV
# Deployment Configuration Environment Variables
# Copy to .env.deploy or export in your CI/CD environment.

DEPLOY_HOST={$host}
DEPLOY_USER={$user}
DEPLOY_BRANCH={$branch}
DEPLOY_PORT={$port}
DEPLOY_HTTP_USER=www-data
DEPLOY_HTTP_GROUP=www-data
ENV;
    }

    /**
     * Write generated deploy.php and optional helper files to target directory.
     *
     * @param string $targetDirectory
     * @param string $appName
     * @param string $template
     * @param array $options
     * @param bool $force
     * @return array
     * @throws \RuntimeException If file exists and force is false, or write fails
     */
    public function write(
        string $targetDirectory,
        string $appName,
        string $template = 'laravel',
        array $options = [],
        bool $force = false
    ): array {
        $targetDirectory = rtrim($targetDirectory, '/');
        if (!is_dir($targetDirectory)) {
            throw new \RuntimeException("Target directory does not exist: {$targetDirectory}");
        }

        $deployFile = "{$targetDirectory}/deploy.php";
        if (file_exists($deployFile) && !$force) {
            throw new \RuntimeException("Target file already exists: {$deployFile}. Use --force to overwrite.");
        }

        $content = $this->generate($appName, $template, $options);
        if (file_put_contents($deployFile, $content) === false) {
            throw new \RuntimeException("Failed to write {$deployFile}");
        }

        $writtenFiles = [$deployFile];

        $envFile = "{$targetDirectory}/.env.deploy.example";
        if (!file_exists($envFile) || $force) {
            $envContent = $this->generateEnvExample($options);
            if (file_put_contents($envFile, $envContent) !== false) {
                $writtenFiles[] = $envFile;
            }
        }

        return [
            'success' => true,
            'appName' => $appName,
            'template' => $template,
            'files' => $writtenFiles,
        ];
    }
}
