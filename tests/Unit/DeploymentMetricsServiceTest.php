<?php

namespace Klytron\PhpDeploymentKit\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Klytron\PhpDeploymentKit\Services\DeploymentMetricsService;

class DeploymentMetricsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        // Clear metrics before each test
        DeploymentMetricsService::recordMetric('test', 'clear');
    }

    public function testStartDeployment(): void
    {
        DeploymentMetricsService::startDeployment();
        
        $metrics = DeploymentMetricsService::getMetrics();
        $this->assertArrayHasKey('deployment_start_time', $metrics);
        $this->assertNotEmpty($metrics['deployment_start_time']);
    }

    public function testEndDeployment(): void
    {
        DeploymentMetricsService::startDeployment();
        sleep(1); // Simulate some time passing
        DeploymentMetricsService::endDeployment();
        
        $metrics = DeploymentMetricsService::getMetrics();
        $this->assertArrayHasKey('deployment_duration', $metrics);
        $this->assertArrayHasKey('deployment_end_time', $metrics);
        $this->assertGreaterThan(0, $metrics['deployment_duration']);
    }

    public function testRecordMetric(): void
    {
        DeploymentMetricsService::recordMetric('test_key', 'test_value');
        
        $metrics = DeploymentMetricsService::getMetrics();
        $this->assertEquals('test_value', $metrics['test_key']);
    }

    public function testRecordTaskResult(): void
    {
        DeploymentMetricsService::recordTaskResult('test_task', true, 1.5);
        
        $metrics = DeploymentMetricsService::getMetrics();
        $task = $metrics['task_test_task'];
        
        $this->assertTrue($task['success']);
        $this->assertEquals(1.5, $task['duration']);
        $this->assertArrayHasKey('timestamp', $task);
    }

    public function testRecordAssetMapping(): void
    {
        $mapping = [
            ['original' => 'app.js', 'mapped' => 'app.abc123.js'],
            ['original' => 'style.css', 'mapped' => 'style.def456.css']
        ];
        
        DeploymentMetricsService::recordAssetMapping($mapping);
        
        $metrics = DeploymentMetricsService::getMetrics();
        $assetMapping = $metrics['asset_mapping'];
        
        $this->assertEquals(2, $assetMapping['total_assets']);
        $this->assertArrayHasKey('mapped_at', $assetMapping);
        $this->assertArrayHasKey('details', $assetMapping);
        $this->assertCount(2, $assetMapping['details']);
    }

    public function testRecordSitemapGeneration(): void
    {
        DeploymentMetricsService::recordSitemapGeneration('/test/sitemap.xml', 1024);
        
        $metrics = DeploymentMetricsService::getMetrics();
        $sitemap = $metrics['sitemap'];
        
        $this->assertTrue($sitemap['generated']);
        $this->assertEquals('/test/sitemap.xml', $sitemap['path']);
        $this->assertEquals(1024, $sitemap['size']);
        $this->assertArrayHasKey('generated_at', $sitemap);
    }

    public function testRecordImageOptimization(): void
    {
        DeploymentMetricsService::recordImageOptimization(2048, 1024);
        
        $metrics = DeploymentMetricsService::getMetrics();
        $imageOptimization = $metrics['image_optimization'];
        
        $this->assertEquals(2048, $imageOptimization['original_size']);
        $this->assertEquals(1024, $imageOptimization['optimized_size']);
        $this->assertEquals(1024, $imageOptimization['savings_bytes']);
        $this->assertEquals(50.0, $imageOptimization['savings_percent']);
        $this->assertArrayHasKey('optimized_at', $imageOptimization);
    }

    public function testGetSummary(): void
    {
        DeploymentMetricsService::startDeployment();
        DeploymentMetricsService::recordTaskResult('test_task_1', true, 1.0);
        DeploymentMetricsService::recordTaskResult('test_task_2', false, 2.0);
        DeploymentMetricsService::endDeployment();
        
        $summary = DeploymentMetricsService::getSummary();
        
        $this->assertStringContainsString('Deployment Metrics:', $summary);
        $this->assertStringContainsString('Tasks:', $summary);
        $this->assertStringContainsString('1/2', $summary);
    }

    public function testFormatDuration(): void
    {
        $this->assertEquals('30s', DeploymentMetricsService::formatDuration(30));
        $this->assertEquals('1m 30s', DeploymentMetricsService::formatDuration(90));
        $this->assertEquals('2m 0s', DeploymentMetricsService::formatDuration(120));
    }

    public function testFormatBytes(): void
    {
        $this->assertEquals('0 B', DeploymentMetricsService::formatBytes(0));
        $this->assertEquals('1024 B', DeploymentMetricsService::formatBytes(1024));
        $this->assertEquals('1.00 KB', DeploymentMetricsService::formatBytes(1024));
        $this->assertEquals('1.00 MB', DeploymentMetricsService::formatBytes(1024 * 1024));
        $this->assertEquals('1.00 GB', DeploymentMetricsService::formatBytes(1024 * 1024 * 1024));
    }
}
