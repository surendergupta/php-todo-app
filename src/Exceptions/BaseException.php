<?php
declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class BaseException extends Exception
{
    protected array $context;

    public function __construct(
        string $message = "Application Error",
        int $code = 500,
        array $context = []
    ) {
        parent::__construct($message, $code);
        $this->context = $context;

        $this->logError();
    }

    /**
     * Get additional context
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Log the exception into storage/logs/app.log
     */
    protected function logError(): void
    {
        $logDir = __DIR__ . '/../../storage/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        $logFile = $logDir . '/app.log';
        $time = date('Y-m-d H:i:s');

        $logMessage = "[{$time}] {$this->getCode()} {$this->getMessage()} in {$this->getFile()}:{$this->getLine()}\n";
        if (!empty($this->context)) {
            $logMessage .= "Context: " . json_encode($this->context, JSON_UNESCAPED_SLASHES) . "\n";
        }
        $logMessage .= "Trace: " . $this->getTraceAsString() . "\n\n";

        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
}
