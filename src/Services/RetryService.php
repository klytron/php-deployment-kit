<?php

namespace Klytron\PhpDeploymentKit\Services;
use function Deployer\info;
use function Deployer\warning;
use function Deployer\error;
use function Deployer\run;
use function Deployer\test;
use function Deployer\get;
use function Deployer\set;

use Klytron\PhpDeploymentKit\Exceptions\NetworkException;
use Exception;

/**
 * Retry Service for Network Operations
 */
class RetryService
{
    public static function execute(callable $operation, array $options = []): mixed
    {
        $maxAttempts = $options['max_attempts'] ?? 3;
        $baseDelay = $options['base_delay'] ?? 1000; // milliseconds
        $maxDelay = $options['max_delay'] ?? 10000; // milliseconds
        $backoffMultiplier = $options['backoff_multiplier'] ?? 2;
        $retryOn = $options['retry_on'] ?? [Exception::class];
        
        $attempt = 1;
        $lastException = null;

        while ($attempt <= $maxAttempts) {
            try {
                return $operation($attempt);
            } catch (Exception $e) {
                $lastException = $e;
                
                if (!self::shouldRetry($e, $retryOn)) {
                    throw $e;
                }

                if ($attempt === $maxAttempts) {
                    throw new NetworkException(
                        "Operation failed after {$maxAttempts} attempts: " . $e->getMessage(),
                        [
                            'attempts' => $attempt,
                            'last_error' => $e->getMessage(),
                            'operation' => self::getOperationName($operation)
                        ],
                        "Check network connectivity and try again later"
                    );
                }

                $delay = min($baseDelay * pow($backoffMultiplier, $attempt - 1), $maxDelay);
                
                if (function_exists('Deployer\warning')) {
                    warning("⚠️ Attempt {$attempt} failed: {$e->getMessage()}. Retrying in " . ($delay / 1000) . "s...");
                }
                
                usleep($delay * 1000); // Convert to microseconds
                $attempt++;
            }
        }

        throw $lastException;
    }

    public static function executeWithBackoff(callable $operation, array $options = []): mixed
    {
        return self::execute($operation, array_merge([
            'max_attempts' => 5,
            'base_delay' => 2000,
            'max_delay' => 30000,
            'backoff_multiplier' => 2
        ], $options));
    }

    public static function executeHttp(callable $operation, array $options = []): mixed
    {
        return self::execute($operation, array_merge([
            'max_attempts' => 3,
            'base_delay' => 1000,
            'max_delay' => 10000,
            'backoff_multiplier' => 2,
            'retry_on' => [NetworkException::class, \RuntimeException::class]
        ], $options));
    }

    private static function shouldRetry(Exception $exception, array $retryOn): bool
    {
        foreach ($retryOn as $retryClass) {
            if ($exception instanceof $retryClass) {
                return true;
            }
        }
        
        // Also retry on network-related error codes
        $message = strtolower($exception->getMessage());
        $networkErrors = [
            'connection refused',
            'connection timed out',
            'connection reset',
            'name resolution failed',
            'network is unreachable',
            'timeout',
            'curl error',
            'http error'
        ];
        
        foreach ($networkErrors as $error) {
            if (str_contains($message, $error)) {
                return true;
            }
        }
        
        return false;
    }

    private static function getOperationName(callable $operation): string
    {
        try {
            $reflection = new \ReflectionFunction($operation);
            return $reflection->getName();
        } catch (\Exception $e) {
            return 'anonymous_operation';
        }
    }

    public static function executeCurlOperation(callable $curlOperation, array $options = []): mixed
    {
        return self::execute($curlOperation, array_merge([
            'max_attempts' => 3,
            'base_delay' => 500,
            'max_delay' => 5000,
            'backoff_multiplier' => 1.5,
            'retry_on' => [NetworkException::class]
        ], $options));
    }
}
