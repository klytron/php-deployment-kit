<?php

namespace Klytron\PhpDeploymentKit\Exceptions;

/**
 * Network Exception
 */
class NetworkException extends DeploymentException
{
    protected string $errorType = 'network_error';

    public static function connectionFailed(string $url, ?string $reason = null): self
    {
        return new self(
            "Failed to connect to: {$url}",
            ['url' => $url, 'reason' => $reason],
            "Check network connectivity and URL accessibility"
        );
    }

    public static function timeout(string $operation, int $timeout): self
    {
        return new self(
            "Operation '{$operation}' timed out after {$timeout} seconds",
            ['operation' => $operation, 'timeout' => $timeout],
            "Increase timeout or check network performance"
        );
    }

    public static function httpError(string $url, int $statusCode, string $response): self
    {
        return new self(
            "HTTP error {$statusCode} for URL: {$url}",
            ['url' => $url, 'status_code' => $statusCode, 'response' => $response],
            "Check URL validity and server response"
        );
    }
}
