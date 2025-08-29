<?php
declare(strict_types=1);
namespace App\Repository;

use App\Database;
use App\Database\QueryBuilder;
use App\Exceptions\RepositoryException;
use PDO;

abstract class BaseRepository {
    protected PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Always get a fresh QueryBuilder instance
     */
    protected function qb(): QueryBuilder {
        return new QueryBuilder($this->db);
    }

    protected function safeExecute(callable $callback, mixed $default = null): mixed {
        try {
            // return $callback();
             $result = $callback();
            // Debug
            // Log SQL query if QueryBuilder is set
            if ($result instanceof QueryBuilder) {
                $sql = $result->toSql();
                $bindings = $result->getBindings() ?? [];
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
        $logDir = __DIR__ . '/../../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        file_put_contents(
            "$logDir/$file",
            json_encode($data, JSON_PRETTY_PRINT) . PHP_EOL,
            FILE_APPEND
        );
    }
}