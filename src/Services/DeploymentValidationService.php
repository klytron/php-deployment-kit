<?php

namespace Klytron\PhpDeploymentKit\Services;
use function Deployer\info;
use function Deployer\warning;
use function Deployer\error;
use function Deployer\run;
use function Deployer\test;
use function Deployer\get;
use function Deployer\set;

use Klytron\PhpDeploymentKit\Validators\DeploymentValidator;
use Exception;

class DeploymentValidationService
{
    protected DeploymentValidator $validator;

    public function __construct()
    {
        $this->validator = new DeploymentValidator();
    }

    /**
     * Perform comprehensive deployment validation
     */
    public function validateDeployment(array $config): array
    {
        $results = [
            'configuration' => $this->validator->validate($config),
            'connectivity' => [],
            'binaries' => [],
            'overall_valid' => false,
        ];

        // Only test connectivity and binaries if basic config is valid
        if ($results['configuration']['valid']) {
            $results['connectivity'] = $this->testConnectivity($config);
            $results['binaries'] = $this->testBinaries();
        }

        $results['overall_valid'] = $this->isOverallValid($results);

        return $results;
    }

    /**
     * Test SSH connectivity to configured hosts
     */
    protected function testConnectivity(array $config): array
    {
        $hosts = $config['hosts'] ?? [];
        
        if (empty($hosts)) {
            return [
                'tested' => false,
                'message' => 'No hosts configured for connectivity testing',
                'results' => [],
            ];
        }

        $results = $this->validator->validateSshConnectivity($hosts);
        $allConnected = empty(array_filter($results, fn($r) => !$r['connected']));

        return [
            'tested' => true,
            'all_connected' => $allConnected,
            'results' => $results,
        ];
    }

    /**
     * Test availability of required binaries
     */
    protected function testBinaries(): array
    {
        $requiredBinaries = ['git', 'composer'];
        
        // Add PHP if specified in config
        // $requiredBinaries[] = 'php';
        
        $results = $this->validator->validateBinaries($requiredBinaries);
        $allAvailable = empty(array_filter($results, fn($r) => !$r['available']));

        return [
            'tested' => true,
            'all_available' => $allAvailable,
            'results' => $results,
        ];
    }

    /**
     * Determine if overall deployment is valid
     */
    protected function isOverallValid(array $results): bool
    {
        // Configuration must be valid
        if (!$results['configuration']['valid']) {
            return false;
        }

        // If connectivity was tested, all hosts must be connected
        if (isset($results['connectivity']['tested']) && $results['connectivity']['tested']) {
            if (!$results['connectivity']['all_connected']) {
                return false;
            }
        }

        // If binaries were tested, all must be available
        if (isset($results['binaries']['tested']) && $results['binaries']['tested']) {
            if (!$results['binaries']['all_available']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Generate validation report
     */
    public function generateReport(array $validationResults): string
    {
        $report = "=== Deployment Validation Report ===\n\n";

        // Configuration section
        $config = $validationResults['configuration'];
        $report .= "Configuration: " . ($config['valid'] ? "✅ VALID" : "❌ INVALID") . "\n";
        
        if (!empty($config['errors'])) {
            $report .= "\nErrors:\n";
            foreach ($config['errors'] as $error) {
                $report .= "  ❌ {$error}\n";
            }
        }

        if (!empty($config['warnings'])) {
            $report .= "\nWarnings:\n";
            foreach ($config['warnings'] as $warning) {
                $report .= "  ⚠️  {$warning}\n";
            }
        }

        // Connectivity section
        if (isset($validationResults['connectivity']['tested']) && $validationResults['connectivity']['tested']) {
            $connectivity = $validationResults['connectivity'];
            $report .= "\nSSH Connectivity: " . ($connectivity['all_connected'] ? "✅ ALL CONNECTED" : "❌ SOME FAILED") . "\n";
            
            foreach ($connectivity['results'] as $host => $result) {
                $status = $result['connected'] ? "✅" : "❌";
                $report .= "  {$status} {$host}\n";
                if (!$result['connected'] && $result['error']) {
                    $report .= "    Error: {$result['error']}\n";
                }
            }
        }

        // Binaries section
        if (isset($validationResults['binaries']['tested']) && $validationResults['binaries']['tested']) {
            $binaries = $validationResults['binaries'];
            $report .= "\nRequired Binaries: " . ($binaries['all_available'] ? "✅ ALL AVAILABLE" : "❌ SOME MISSING") . "\n";
            
            foreach ($binaries['results'] as $binary => $result) {
                $status = $result['available'] ? "✅" : "❌";
                $path = $result['path'] ? " ({$result['path']})" : "";
                $report .= "  {$status} {$binary}{$path}\n";
            }
        }

        // Overall status
        $overall = $validationResults['overall_valid'];
        $report .= "\nOverall Status: " . ($overall ? "✅ READY FOR DEPLOYMENT" : "❌ FIX ISSUES BEFORE DEPLOYMENT") . "\n";

        return $report;
    }
}
