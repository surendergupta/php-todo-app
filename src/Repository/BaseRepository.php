<?php
declare(strict_types=1);
namespace App\Repository;

use App\Exceptions\RepositoryException;

abstract class BaseRepository {
    protected function safeExecute(callable $callback, mixed $default = null): mixed {
        try {
            // return $callback();
             $result = $callback();
            // Debug
            // Log SQL query if QueryBuilder is set
            if (isset($this->qb) && method_exists($this->qb, 'toSql')) {
                $sql = $this->qb->toSql();
                $bindings = $this->qb->getBindings() ?? [];
                $this->logQuery([
                    'time' => date('c'),
                    'class' => static::class,
                    'sql' => $sql,
                    'bindings' => $bindings
                ]);
            }
            return $result;
        } catch (\Throwable $e) {
            error_log("DB Error in " . static::class . ": {$e->getMessage()} at {$e->getFile()}:{$e->getLine()}\n" . $e->getTraceAsString());
            $this->logError([
                'time' => date('c'),
                'class' => static::class,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'sql' => isset($this->qb) && method_exists($this->qb, 'toSql') ? $this->qb->toSql() : null,
                'bindings' => isset($this->qb) && method_exists($this->qb, 'getBindings') ? $this->qb->getBindings() : null
            ]);
            if ($default !== null) {
                return $default;
            }
            throw new RepositoryException("Database operation failed", 0, $e);
        }
    }

    private function logQuery(array $data): void {
        $this->writeLog('db_queries.log', $data);
    }

    private function logError(array $data): void {
        $this->writeLog('db_errors.log', $data);
    }

    private function writeLog(string $file, array $data): void {
        $logDir = __DIR__ . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        file_put_contents(
            "$logDir/$file",
            json_encode($data, JSON_PRETTY_PRINT) . PHP_EOL,
            FILE_APPEND
        );
    }

    // private function logError(array $data): void {
    //     $logDir = __DIR__ . '/logs';
    //     if (!is_dir($logDir)) {
    //         mkdir($logDir, 0755, true);
    //     }
    //     $logFile = $logDir . '/db_errors.log';
    //     if (file_exists($logFile) && filesize($logFile) > 5_000_000) { // 5 MB
    //         rename($logFile, $logDir . '/db_errors_' . time() . '.log');
    //     }
    //     file_put_contents(
    //         $logFile,
    //         json_encode($data, JSON_PRETTY_PRINT) . PHP_EOL,
    //         FILE_APPEND
    //     );
    // }
}