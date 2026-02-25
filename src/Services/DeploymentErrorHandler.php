<?php

namespace Klytron\PhpDeploymentKit\Services;
use function Deployer\info;
use function Deployer\warning;
use function Deployer\error;
use function Deployer\run;
use function Deployer\test;
use function Deployer\get;
use function Deployer\set;

use Exception;
use Throwable;

class DeploymentErrorHandler
{
    protected array $errors = [];
    protected array $warnings = [];
    protected string $logFile;

    public function __construct(string $logFile = null)
    {
        $this->logFile = $logFile ?? sys_get_temp_dir() . '/deployment_errors.log';
    }

    /**
     * Handle deployment error with context
     */
    public function handleError(string $message, array $context = [], string $severity = 'error'): void
    {
        $errorEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'severity' => $severity,
            'message' => $message,
            'context' => $context,
            'stack_trace' => $severity === 'error' ? $this->getStackTrace() : null,
        ];

        if ($severity === 'error') {
            $this->errors[] = $errorEntry;
        } else {
            $this->warnings[] = $errorEntry;
        }

        $this->logError($errorEntry);
    }

    /**
     * Handle exception with detailed context
     */
    public function handleException(Throwable $exception, array $context = []): void
    {
        $errorEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'severity' => 'error',
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'context' => $context,
            'stack_trace' => $exception->getTraceAsString(),
            'exception_class' => get_class($exception),
        ];

        $this->errors[] = $errorEntry;
        $this->logError($errorEntry);
    }

    /**
     * Add warning message
     */
    public function addWarning(string $message, array $context = []): void
    {
        $this->handleError($message, $context, 'warning');
    }

    /**
     * Add info message
     */
    public function addInfo(string $message, array $context = []): void
    {
        $this->handleError($message, $context, 'info');
    }

    /**
     * Check if there are any errors
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Check if there are any warnings
     */
    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }

    /**
     * Get all errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get all warnings
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Get all messages (errors + warnings)
     */
    public function getAllMessages(): array
    {
        return array_merge($this->errors, $this->warnings);
    }

    /**
     * Generate human-readable error report
     */
    public function generateReport(): string
    {
        $report = "=== Deployment Error Report ===\n";
        $report .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";

        if (!empty($this->errors)) {
            $report .= "ERRORS (" . count($this->errors) . "):\n";
            $report .= str_repeat("=", 50) . "\n";
            
            foreach ($this->errors as $i => $error) {
                $report .= sprintf(
                    "[%d] %s - %s\n",
                    $i + 1,
                    $error['timestamp'],
                    $error['message']
                );
                
                if (isset($error['file'])) {
                    $report .= sprintf("    File: %s:%d\n", $error['file'], $error['line']);
                }
                
                if (isset($error['exception_class'])) {
                    $report .= sprintf("    Exception: %s\n", $error['exception_class']);
                }
                
                if (!empty($error['context'])) {
                    $report .= "    Context: " . json_encode($error['context'], JSON_PRETTY_PRINT) . "\n";
                }
                
                $report .= "\n";
            }
        }

        if (!empty($this->warnings)) {
            $report .= "\nWARNINGS (" . count($this->warnings) . "):\n";
            $report .= str_repeat("-", 50) . "\n";
            
            foreach ($this->warnings as $i => $warning) {
                $report .= sprintf(
                    "[%d] %s - %s\n",
                    $i + 1,
                    $warning['timestamp'],
                    $warning['message']
                );
                
                if (!empty($warning['context'])) {
                    $report .= "    Context: " . json_encode($warning['context'], JSON_PRETTY_PRINT) . "\n";
                }
                
                $report .= "\n";
            }
        }

        return $report;
    }

    /**
     * Clear all errors and warnings
     */
    public function clear(): void
    {
        $this->errors = [];
        $this->warnings = [];
    }

    /**
     * Get error summary for quick overview
     */
    public function getSummary(): array
    {
        return [
            'error_count' => count($this->errors),
            'warning_count' => count($this->warnings),
            'has_errors' => $this->hasErrors(),
            'has_warnings' => $this->hasWarnings(),
            'latest_error' => $this->errors[0] ?? null,
            'latest_warning' => $this->warnings[0] ?? null,
        ];
    }

    /**
     * Log error to file
     */
    protected function logError(array $errorEntry): void
    {
        $logLine = json_encode($errorEntry) . "\n";
        file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
    }

    /**
     * Get current stack trace
     */
    protected function getStackTrace(): string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $formatted = [];
        
        foreach ($trace as $entry) {
            $formatted[] = sprintf(
                "%s:%d %s%s",
                $entry['file'] ?? 'unknown',
                $entry['line'] ?? 0,
                $entry['function'] ?? 'unknown',
                isset($entry['class']) ? '::' : ''
            );
        }
        
        return implode("\n", $formatted);
    }

    /**
     * Set custom log file path
     */
    public function setLogFile(string $logFile): void
    {
        $this->logFile = $logFile;
    }

    /**
     * Get current log file path
     */
    public function getLogFile(): string
    {
        return $this->logFile;
    }
}
