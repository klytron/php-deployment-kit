<?php

namespace Klytron\PhpDeploymentKit\Services;
use function Deployer\info;
use function Deployer\warning;
use function Deployer\error;
use function Deployer\run;
use function Deployer\test;
use function Deployer\get;
use function Deployer\set;

/**
 * Deployment Metrics Service
 */
class DeploymentMetricsService
{
    private static array $metrics = [];
    private static float $startTime = 0;
    private static array $timers = [];

    public static function startDeployment(): void
    {
        self::$startTime = microtime(true);
        self::$metrics['deployment_start_time'] = date('Y-m-d H:i:s');
        self::recordMetric('deployment_started', true);
    }

    public static function endDeployment(): void
    {
        $endTime = microtime(true);
        $duration = $endTime - self::$startTime;
        
        self::$metrics['deployment_end_time'] = date('Y-m-d H:i:s');
        self::$metrics['deployment_duration'] = round($duration, 2);
        self::$metrics['deployment_duration_formatted'] = self::formatDuration($duration);
        
        self::recordMetric('deployment_completed', true);
        self::saveMetrics();
    }

    public static function startTimer(string $name): void
    {
        self::$timers[$name] = microtime(true);
    }

    public static function endTimer(string $name): float
    {
        if (!isset(self::$timers[$name])) {
            return 0;
        }

        $duration = microtime(true) - self::$timers[$name];
        unset(self::$timers[$name]);
        
        self::recordMetric("timer_{$name}", round($duration, 3));
        return $duration;
    }

    public static function recordMetric(string $key, mixed $value): void
    {
        self::$metrics[$key] = $value;
    }

    public static function recordTaskResult(string $task, bool $success, float $duration = 0, string $error = ''): void
    {
        $taskKey = "task_{$task}";
        self::$metrics[$taskKey] = [
            'success' => $success,
            'duration' => $duration,
            'error' => $error,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    public static function recordAssetMapping(array $mapping): void
    {
        self::$metrics['asset_mapping'] = [
            'total_assets' => count($mapping),
            'mapped_at' => date('Y-m-d H:i:s'),
            'details' => $mapping
        ];
    }

    public static function recordSitemapGeneration(string $path, int $size): void
    {
        self::$metrics['sitemap'] = [
            'generated' => true,
            'path' => $path,
            'size' => $size,
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }

    public static function recordImageOptimization(int $originalSize, int $optimizedSize): void
    {
        $savings = $originalSize - $optimizedSize;
        $savingsPercent = round(($savings / $originalSize) * 100, 2);
        
        self::$metrics['image_optimization'] = [
            'original_size' => $originalSize,
            'optimized_size' => $optimizedSize,
            'savings_bytes' => $savings,
            'savings_percent' => $savingsPercent,
            'optimized_at' => date('Y-m-d H:i:s')
        ];
    }

    public static function getMetrics(): array
    {
        return self::$metrics;
    }

    public static function getSummary(): string
    {
        $duration = self::$metrics['deployment_duration'] ?? 0;
        $tasks = array_filter(self::$metrics, fn($k) => str_starts_with($k, 'task_'), ARRAY_FILTER_USE_KEY);
        $successfulTasks = count(array_filter($tasks, fn($t) => $t['success'] ?? false));
        $totalTasks = count($tasks);

        $summary = "📊 Deployment Metrics:\n";
        $summary .= "⏱️ Duration: " . self::formatDuration($duration) . "\n";
        $summary .= "✅ Tasks: {$successfulTasks}/{$totalTasks} successful\n";

        if (isset(self::$metrics['asset_mapping'])) {
            $summary .= "🔗 Assets: " . self::$metrics['asset_mapping']['total_assets'] . " mapped\n";
        }

        if (isset(self::$metrics['sitemap'])) {
            $summary .= "🗺️ Sitemap: Generated (" . self::formatBytes(self::$metrics['sitemap']['size']) . ")\n";
        }

        if (isset(self::$metrics['image_optimization'])) {
            $summary .= "🖼️ Images: " . self::$metrics['image_optimization']['savings_percent'] . "% savings\n";
        }

        return $summary;
    }

    private static function formatDuration(float $seconds): string
    {
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        
        if ($minutes > 0) {
            return "{$minutes}m {$remainingSeconds}s";
        }
        
        return "{$remainingSeconds}s";
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

    private static function saveMetrics(): void
    {
        if (!function_exists('Deployer\get')) {
            return;
        }

        $deployPath = get('deploy_path', '/tmp');
        $metricsFile = $deployPath . '/deployment_metrics.json';
        
        $json = json_encode(self::$metrics, JSON_PRETTY_PRINT);
        
        // Save metrics file
        if (function_exists('Deployer\run')) {
            run("echo '{$json}' > {$metricsFile}");
        }
    }

    public static function loadMetrics(): array
    {
        if (!function_exists('Deployer\get')) {
            return [];
        }

        $deployPath = get('deploy_path', '/tmp');
        $metricsFile = $deployPath . '/deployment_metrics.json';
        
        if (!function_exists('Deployer\test')) {
            return [];
        }

        if (!test("[ -f {$metricsFile} ]")) {
            return [];
        }

        if (!function_exists('Deployer\run')) {
            return [];
        }

        $json = run("cat {$metricsFile}", true);
        return json_decode($json, true) ?: [];
    }
}
