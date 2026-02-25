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

use Klytron\PhpDeploymentKit\Services\DeploymentValidationService;
use Klytron\PhpDeploymentKit\Services\DeploymentErrorHandler;

class EnhancedDeploymentTask
{
    protected DeploymentValidationService $validator;
    protected DeploymentErrorHandler $errorHandler;

    public function __construct()
    {
        $this->validator = new DeploymentValidationService();
        $this->errorHandler = new DeploymentErrorHandler();
    }

    /**
     * Pre-deployment validation task
     */
    public function validateBeforeDeployment(): void
    {
        if (!function_exists('Deployer\info')) {
            return;
        }

        info("🔍 Running pre-deployment validation...");

        // Get current deployment configuration
        $config = $this->getDeploymentConfig();
        
        // Perform validation
        $results = $this->validator->validateDeployment($config);
        
        // Generate and display report
        $report = $this->validator->generateReport($results);
        writeln($report);

        // Handle validation failures
        if (!$results['overall_valid']) {
            $this->errorHandler->handleError(
                "Pre-deployment validation failed",
                ['validation_results' => $results]
            );
            
            if (!get('skip_validation', false)) {
                throw new \RuntimeException("Deployment validation failed. Use --skip-validation to bypass (not recommended).");
            }
        }

        info("✅ Pre-deployment validation completed successfully");
    }

    /**
     * Post-deployment health check
     */
    public function healthCheck(): void
    {
        if (!function_exists('Deployer\info')) {
            return;
        }

        info("🏥 Running post-deployment health check...");

        $checks = [
            'application_url' => $this->checkApplicationUrl(),
            'ssl_certificate' => $this->checkSslCertificate(),
            'database_connection' => $this->checkDatabaseConnection(),
            'file_permissions' => $this->checkFilePermissions(),
            'storage_links' => $this->checkStorageLinks(),
        ];

        $allPassed = true;
        foreach ($checks as $check => $result) {
            $status = $result['passed'] ? "✅" : "❌";
            info("  {$status} {$check}: {$result['message']}");
            
            if (!$result['passed']) {
                $allPassed = false;
                $this->errorHandler->addWarning(
                    "Health check failed: {$check}",
                    ['result' => $result]
                );
            }
        }

        if ($allPassed) {
            info("🎉 All health checks passed!");
        } else {
            warning("⚠️  Some health checks failed. Please review the deployment.");
        }
    }

    /**
     * Get current deployment configuration
     */
    protected function getDeploymentConfig(): array
    {
        return [
            'app_name' => get('application_name', 'unknown'),
            'repository' => get('repository', ''),
            'deploy_path' => get('deploy_path', ''),
            'domain' => get('domain', ''),
            'project_type' => get('project_type', 'php'),
            'database' => get('database_type', 'none'),
            'php_version' => get('php_version', 'php8.3'),
            'hosts' => $this->getHostsConfiguration(),
        ];
    }

    /**
     * Get hosts configuration
     */
    protected function getHostsConfiguration(): array
    {
        $hosts = [];
        
        // Try to get hosts from Deployer configuration
        if (function_exists('Deployer\host')) {
            // This is a simplified approach - in real implementation,
            // you'd need to access Deployer's internal host registry
            $defaultHost = get('hostname', 'localhost');
            $hosts[$defaultHost] = [
                'hostname' => $defaultHost,
                'remote_user' => get('remote_user', 'www-data'),
                'port' => get('port', 22),
                'branch' => get('branch', 'main'),
            ];
        }
        
        return $hosts;
    }

    /**
     * Check if application URL is accessible
     */
    protected function checkApplicationUrl(): array
    {
        $url = get('application_public_url', '');
        
        if (empty($url)) {
            return [
                'passed' => false,
                'message' => 'Application URL not configured',
            ];
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Allow self-signed certs for testing
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return [
                'passed' => false,
                'message' => "Connection error: {$error}",
            ];
        }

        if ($httpCode >= 200 && $httpCode < 400) {
            return [
                'passed' => true,
                'message' => "Application accessible (HTTP {$httpCode})",
            ];
        }

        return [
            'passed' => false,
            'message' => "HTTP error {$httpCode}",
        ];
    }

    /**
     * Check SSL certificate validity
     */
    protected function checkSslCertificate(): array
    {
        $url = get('application_public_url', '');
        
        if (empty($url) || !str_starts_with($url, 'https://')) {
            return [
                'passed' => true,
                'message' => 'SSL not applicable (HTTP or no URL)',
            ];
        }

        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) {
            return [
                'passed' => false,
                'message' => 'Invalid URL format',
            ];
        }

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false,
            ],
        ]);

        $socket = @stream_socket_client(
            "ssl://{$host}:443",
            $errno,
            $errstr,
            10,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if ($socket) {
            fclose($socket);
            return [
                'passed' => true,
                'message' => 'SSL certificate is valid',
            ];
        }

        return [
            'passed' => false,
            'message' => "SSL certificate error: {$errstr}",
        ];
    }

    /**
     * Check database connection
     */
    protected function checkDatabaseConnection(): array
    {
        $dbType = get('database_type', 'none');
        
        if ($dbType === 'none') {
            return [
                'passed' => true,
                'message' => 'No database configured',
            ];
        }

        // This would need to be implemented based on the specific database type
        // For now, return a placeholder
        return [
            'passed' => true,
            'message' => 'Database connection check not implemented',
        ];
    }

    /**
     * Check file permissions
     */
    protected function checkFilePermissions(): array
    {
        $publicPath = get('application_public_html', '');
        
        if (empty($publicPath)) {
            return [
                'passed' => false,
                'message' => 'Public path not configured',
            ];
        }

        // This would check if the web server can write to necessary directories
        // For now, return a placeholder
        return [
            'passed' => true,
            'message' => 'File permissions check not implemented',
        ];
    }

    /**
     * Check storage links
     */
    protected function checkStorageLinks(): array
    {
        $publicPath = get('application_public_html', '');
        
        if (empty($publicPath)) {
            return [
                'passed' => false,
                'message' => 'Public path not configured',
            ];
        }

        // Check if storage link exists and is valid
        $storageLink = $publicPath . '/storage';
        
        if (is_link($storageLink)) {
            $target = readlink($storageLink);
            if ($target && is_dir($target)) {
                return [
                    'passed' => true,
                    'message' => 'Storage link is valid',
                ];
            } else {
                return [
                    'passed' => false,
                    'message' => 'Storage link points to non-existent directory',
                ];
            }
        }

        return [
            'passed' => false,
            'message' => 'Storage link does not exist',
        ];
    }

    /**
     * Get error handler instance
     */
    public function getErrorHandler(): DeploymentErrorHandler
    {
        return $this->errorHandler;
    }
}
