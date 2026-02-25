<?php

namespace Klytron\PhpDeploymentKit\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Klytron\PhpDeploymentKit\Services\RetryService;
use Klytron\PhpDeploymentKit\Exceptions\NetworkException;

class RetryServiceTest extends TestCase
{
    public function testExecuteSuccessfulOperation(): void
    {
        $attempts = 0;
        $operation = function() use (&$attempts) {
            $attempts++;
            return 'success';
        };

        $result = RetryService::execute($operation, ['max_attempts' => 3]);
        
        $this->assertEquals('success', $result);
        $this->assertEquals(1, $attempts);
    }

    public function testExecuteWithRetries(): void
    {
        $attempts = 0;
        $operation = function() use (&$attempts) {
            $attempts++;
            if ($attempts < 3) {
                throw new \RuntimeException('Attempt failed');
            }
            return 'success';
        };

        $result = RetryService::execute($operation, ['max_attempts' => 3]);
        
        $this->assertEquals('success', $result);
        $this->assertEquals(3, $attempts);
    }

    public function testExecuteMaxAttemptsReached(): void
    {
        $attempts = 0;
        $operation = function() use (&$attempts) {
            $attempts++;
            throw new \RuntimeException('Always fails');
        };

        $this->expectException(\RuntimeException::class);
        RetryService::execute($operation, ['max_attempts' => 2]);
    }

    public function testExecuteWithBackoff(): void
    {
        $delays = [];
        $operation = function() use (&$delays) {
            $delay = microtime(true);
            $delays[] = $delay;
            if (count($delays) < 3) {
                throw new \RuntimeException('Retry needed');
            }
            return 'success';
        };

        $startTime = microtime(true);
        $result = RetryService::executeWithBackoff($operation);
        $endTime = microtime(true);
        
        $this->assertEquals('success', $result);
        $this->assertEquals(3, count($delays));
        
        // Verify exponential backoff (delays should increase)
        for ($i = 1; $i < count($delays); $i++) {
            $this->assertGreaterThan($delays[$i-1], $delays[$i]);
        }
    }

    public function testExecuteHttpOperation(): void
    {
        $calls = 0;
        $operation = function() use (&$calls) {
            $calls++;
            if ($calls === 1) {
                return 'success';
            }
            throw new NetworkException('Connection failed', 'test://example.com');
        };

        $result = RetryService::executeHttp($operation, ['max_attempts' => 2]);
        
        $this->assertEquals('success', $result);
        $this->assertEquals(2, $calls);
    }

    public function testShouldRetryOnNetworkException(): void
    {
        $networkException = new NetworkException('Test error');
        $runtimeException = new \RuntimeException('Runtime error');
        
        $this->assertTrue(RetryService::shouldRetry($networkException, [NetworkException::class]));
        $this->assertTrue(RetryService::shouldRetry($runtimeException, [NetworkException::class]));
        $this->assertFalse(RetryService::shouldRetry($networkException, [\RuntimeException::class]));
    }

    public function testShouldRetryOnNetworkErrors(): void
    {
        $networkError = new \RuntimeException('Connection timeout occurred');
        $otherError = new \RuntimeException('Some other error');
        
        $this->assertTrue(RetryService::shouldRetry($networkError, [NetworkException::class]));
        $this->assertTrue(RetryService::shouldRetry($otherError, [NetworkException::class, \RuntimeException::class]));
        $this->assertFalse(RetryService::shouldRetry($networkError, [\RuntimeException::class]));
    }

    public function testExecuteCurlOperation(): void
    {
        $attempts = 0;
        $operation = function() use (&$attempts) {
            $attempts++;
            if ($attempts === 1) {
                throw new \RuntimeException('CURL failed');
            }
            return 'curl_success';
        };

        $result = RetryService::executeCurlOperation($operation, ['max_attempts' => 2]);
        
        $this->assertEquals('curl_success', $result);
        $this->assertEquals(2, $attempts);
    }
}
