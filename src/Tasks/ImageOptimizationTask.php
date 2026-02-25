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

use Klytron\PhpDeploymentKit\Services\RetryService;
use Klytron\PhpDeploymentKit\Services\DeploymentMetricsService;

/**
 * Image Optimization Task
 */
class ImageOptimizationTask
{
    public static function optimizeImages(): void
    {
        if (!function_exists('Deployer\info')) {
            return;
        }

        DeploymentMetricsService::startTimer('image_optimization');
        info("🖼️ Optimizing images...");

        try {
            // Check if image optimization command exists with retry
            $commandExists = RetryService::execute(function() {
                return self::commandExists('images:optimize');
            });

            if ($commandExists) {
                // Get image stats before optimization
                $beforeStats = RetryService::execute(function() {
                    if (!function_exists('Deployer\run')) {
                        return ['total_size' => 0, 'file_count' => 0];
                    }
                    
                    $totalSize = run("find {{release_path}}/public -type f \( -name '*.jpg' -o -name '*.jpeg' -o -name '*.png' -o -name '*.gif' -o -name '*.webp' \) -exec du -c {} + 2>/dev/null | awk '{sum+=$1} END {print sum}'");
                    $fileCount = run("find {{release_path}}/public -type f \( -name '*.jpg' -o -name '*.jpeg' -o -name '*.png' -o -name '*.gif' -o -name '*.webp' \) | wc -l");
                    
                    return [
                        'total_size' => (int)$totalSize,
                        'file_count' => (int)trim($fileCount)
                    ];
                });

                // Run optimization with retry
                $success = RetryService::execute(function() {
                    if (!function_exists('Deployer\run')) {
                        return false;
                    }
                    run('{{bin/php}} {{release_path}}/artisan images:optimize');
                    return true;
                });

                // Get image stats after optimization
                $afterStats = RetryService::execute(function() use ($beforeStats) {
                    if (!function_exists('Deployer\run')) {
                        return ['total_size' => $beforeStats['total_size'], 'file_count' => $beforeStats['file_count']];
                    }
                    
                    $totalSize = run("find {{release_path}}/public -type f \( -name '*.jpg' -o -name '*.jpeg' -o -name '*.png' -o -name '*.gif' -o -name '*.webp' \) -exec du -c {} + 2>/dev/null | awk '{sum+=$1} END {print sum}'");
                    $fileCount = run("find {{release_path}}/public -type f \( -name '*.jpg' -o -name '*.jpeg' -o -name '*.png' -o -name '*.gif' -o -name '*.webp' \) | wc -l");
                    
                    return [
                        'total_size' => (int)$totalSize,
                        'file_count' => (int)trim($fileCount)
                    ];
                });

                // Record optimization metrics
                DeploymentMetricsService::recordImageOptimization($beforeStats['total_size'], $afterStats['total_size']);
                
                $savings = $beforeStats['total_size'] - $afterStats['total_size'];
                $savingsPercent = round(($savings / $beforeStats['total_size']) * 100, 2);
                
                DeploymentMetricsService::recordTaskResult('image_optimization', true, DeploymentMetricsService::endTimer('image_optimization'));
                info("✅ Images optimized successfully");
                info("📊 Before: " . self::formatBytes($beforeStats['total_size']) . " ({$beforeStats['file_count']} files)");
                info("📊 After: " . self::formatBytes($afterStats['total_size']) . " ({$afterStats['file_count']} files)");
                info("💾 Savings: " . self::formatBytes($savings) . " ({$savingsPercent}%)");

            } else {
                info("ℹ️ Image optimization command not found");
                info("💡 Consider adding image optimization to your project");
                
                DeploymentMetricsService::recordTaskResult('image_optimization', false, DeploymentMetricsService::endTimer('image_optimization'), 'Image optimization command not available');
            }

        } catch (Exception $e) {
            DeploymentMetricsService::recordTaskResult('image_optimization', false, DeploymentMetricsService::endTimer('image_optimization'), $e->getMessage());
            error("❌ Image optimization failed: " . $e->getMessage());
            throw new \Exception(
                "Image optimization failed: " . $e->getMessage(),
                ['original_error' => $e->getMessage(), 'trace' => $e->getTraceAsString()],
                "Check image optimization command and permissions"
            );
        }
    }

    private static function commandExists(string $command): bool
    {
        if (!function_exists('Deployer\run')) {
            return false;
        }

        $result = run('{{bin/php}} {{release_path}}/artisan list | grep -c "' . $command . '" || echo "0"');
        return trim($result) === '1';
    }

    private static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
