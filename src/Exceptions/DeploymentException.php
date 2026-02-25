<?php

namespace Klytron\PhpDeploymentKit\Exceptions;

use Exception;

/**
 * Base Deployment Exception
 */
class DeploymentException extends Exception
{
    protected array $context = [];
    protected string $errorType = 'deployment_error';
    protected ?string $suggestion = null;

    public function __construct(string $message, array $context = [], ?string $suggestion = null, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
        $this->suggestion = $suggestion;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getErrorType(): string
    {
        return $this->errorType;
    }

    public function getSuggestion(): ?string
    {
        return $this->suggestion;
    }

    public function toArray(): array
    {
        return [
            'error_type' => $this->errorType,
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'context' => $this->context,
            'suggestion' => $this->suggestion,
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'trace' => $this->getTraceAsString()
        ];
    }
}
