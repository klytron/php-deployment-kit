<?php

namespace Klytron\PhpDeploymentKit\Validators;

use Exception;

class DeploymentValidator
{
    protected array $errors = [];
    protected array $warnings = [];

    /**
     * Validate deployment configuration
     */
    public function validate(array $config): array
    {
        $this->errors = [];
        $this->warnings = [];

        $this->validateRequiredFields($config);
        $this->validatePaths($config);
        $this->validateServerConfiguration($config);
        $this->validateProjectConfiguration($config);

        return [
            'valid' => empty($this->errors),
            'errors' => $this->errors,
            'warnings' => $this->warnings,
        ];
    }

    /**
     * Validate required configuration fields
     */
    protected function validateRequiredFields(array $config): void
    {
        $required = ['app_name', 'repository', 'deploy_path', 'domain'];
        
        foreach ($required as $field) {
            if (empty($config[$field])) {
                $this->errors[] = "Required field '{$field}' is missing or empty";
            }
        }
    }

    /**
     * Validate paths and directories
     */
    protected function validatePaths(array $config): void
    {
        if (!empty($config['deploy_path'])) {
            if (!preg_match('/^\/[a-zA-Z0-9\/_-]+$/', $config['deploy_path'])) {
                $this->errors[] = "Deploy path contains invalid characters";
            }
        }

        if (!empty($config['public_path'])) {
            if (!str_starts_with($config['public_path'], '/')) {
                $this->warnings[] = "Public path should be absolute";
            }
        }
    }

    /**
     * Validate server configuration
     */
    protected function validateServerConfiguration(array $config): void
    {
        if (!empty($config['hosts'])) {
            foreach ($config['hosts'] as $host => $hostConfig) {
                if (empty($hostConfig['remote_user'])) {
                    $this->warnings[] = "Remote user not specified for host: {$host}";
                }

                if (empty($hostConfig['branch'])) {
                    $this->warnings[] = "Branch not specified for host: {$host}, using 'main'";
                }

                if (!empty($hostConfig['port']) && !is_numeric($hostConfig['port'])) {
                    $this->errors[] = "SSH port must be numeric for host: {$host}";
                }
            }
        }
    }

    /**
     * Validate project-specific configuration
     */
    protected function validateProjectConfiguration(array $config): void
    {
        if (!empty($config['project_type'])) {
            $validTypes = ['laravel', 'yii2', 'php', 'api'];
            if (!in_array($config['project_type'], $validTypes)) {
                $this->errors[] = "Invalid project type: {$config['project_type']}";
            }
        }

        if (!empty($config['database'])) {
            $validDatabases = ['mysql', 'mariadb', 'postgresql', 'sqlite', 'none'];
            if (!in_array($config['database'], $validDatabases)) {
                $this->errors[] = "Invalid database type: {$config['database']}";
            }
        }

        if (!empty($config['php_version'])) {
            if (!preg_match('/^php[0-9]+\.[0-9]+$/', $config['php_version'])) {
                $this->errors[] = "Invalid PHP version format: {$config['php_version']}";
            }
        }
    }

    /**
     * Validate SSH connectivity
     */
    public function validateSshConnectivity(array $hosts): array
    {
        $results = [];
        
        foreach ($hosts as $host => $config) {
            $hostname = $config['hostname'] ?? $host;
            $port = $config['port'] ?? 22;
            $user = $config['remote_user'] ?? null;
            
            if (!$user) {
                $results[$host] = [
                    'connected' => false,
                    'error' => 'No remote user specified',
                ];
                continue;
            }

            // Test SSH connectivity
            $command = "ssh -o ConnectTimeout=5 -o BatchMode=yes -p {$port} {$user}@{$hostname} 'echo connection_test'";
            $output = [];
            $returnCode = 0;
            
            exec($command, $output, $returnCode);
            
            $results[$host] = [
                'connected' => $returnCode === 0,
                'output' => implode("\n", $output),
                'error' => $returnCode !== 0 ? 'SSH connection failed' : null,
            ];
        }
        
        return $results;
    }

    /**
     * Validate required binaries are available
     */
    public function validateBinaries(array $binaries = ['git', 'composer', 'php']): array
    {
        $results = [];
        
        foreach ($binaries as $binary) {
            $command = "which {$binary}";
            $output = [];
            $returnCode = 0;
            
            exec($command, $output, $returnCode);
            
            $results[$binary] = [
                'available' => $returnCode === 0,
                'path' => $returnCode === 0 ? trim($output[0] ?? '') : null,
            ];
        }
        
        return $results;
    }

    /**
     * Get validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get validation warnings
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }
}
