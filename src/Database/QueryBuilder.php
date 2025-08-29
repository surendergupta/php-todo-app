<?php
// src/Database/QueryBuilder.php
namespace App\Database;

use PDO;
use PDOException;

class QueryBuilder {
    private PDO $db;
    private string $table = '';
    private array $select = [];
    private array $wheres = [];
    private ?int $limit = null;
    private ?int $offset = null;
    private array $orderBy = [];
    private string $lastSql = '';
    private array $lastBindings = [];

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    // --- Fluent Methods ---

    public function table(string $table): self {
        $this->table = $table;
        return $this;
    }

    public function select(array $columns = ['*']): self {
        $this->select = $columns;
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self {
        $this->orderBy[] = "$column $direction";
        return $this;
    }

    public function limit(int $limit): self {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self {
        $this->offset = $offset;
        return $this;
    }

    // --- WHERE Methods ---

    public function where(string $column, string $operator, mixed $value, string $boolean = 'AND'): self {
        $this->wheres[] = ['type' => 'basic', 'column' => $column, 'operator' => $operator, 'value' => $value, 'boolean' => $boolean];
        return $this;
    }

    public function orWhere(string $column, string $operator, mixed $value): self {
        return $this->where($column, $operator, $value, 'OR');
    }

    public function whereNull(string $column, string $boolean = 'AND'): self {
        $this->wheres[] = ['type' => 'null', 'column' => $column, 'boolean' => $boolean];
        return $this;
    }

    public function whereNotNull(string $column, string $boolean = 'AND'): self {
        $this->wheres[] = ['type' => 'notnull', 'column' => $column, 'boolean' => $boolean];
        return $this;
    }

    public function whereIn(string $column, array $values, string $boolean = 'AND'): self {
        $this->wheres[] = ['type' => 'in', 'column' => $column, 'values' => $values, 'boolean' => $boolean];
        return $this;
    }

    public function whereNotIn(string $column, array $values, string $boolean = 'AND'): self {
        $this->wheres[] = ['type' => 'notin', 'column' => $column, 'values' => $values, 'boolean' => $boolean];
        return $this;
    }

    public function whereBetween(string $column, array $range, string $boolean = 'AND'): self {
        $this->wheres[] = ['type' => 'between', 'column' => $column, 'range' => $range, 'boolean' => $boolean];
        return $this;
    }

    public function whereNotBetween(string $column, array $range, string $boolean = 'AND'): self {
        $this->wheres[] = ['type' => 'notbetween', 'column' => $column, 'range' => $range, 'boolean' => $boolean];
        return $this;
    }    

    // --- CRUD Methods ---

    public function get(): array|false {
        try {
            $sql = $this->buildSelect();
            $stmt = $this->db->prepare($sql);

            $bindings = $this->prepareBindings();
            $stmt->execute($bindings);

            $this->lastSql = $sql;
            $this->lastBindings = $bindings;

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("QueryBuilder DB Error [GET]: " . $e->getMessage());
            return false;
        }
    }

    public function first(): ?array {
        $prevLimit = $this->limit;
        $this->limit = 1;
        $result = $this->get();
        $this->limit = $prevLimit;
        return $result[0] ?? null;
    }

    public function insert(array $data): ?bool {
        try {
            $columns = implode(", ", array_keys($data));
            $placeholders = implode(", ", array_map(fn($k) => ":$k", array_keys($data)));

            $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";
            $stmt = $this->db->prepare($sql);

            $this->lastSql = $sql;
            $this->lastBindings = $data;

            if ($stmt->execute($data)) {
                return (int)$this->db->lastInsertId();
            }
            return null;
            // return $stmt->execute($data);
        } catch (PDOException $e) {
            error_log("QueryBuilder DB Error [INSERT]: " . $e->getMessage());
            return false;
        }
    }

    public function insertBatch(array $rows): bool {
        if (empty($rows)) return false;
        $columns = implode(", ", array_keys($rows[0]));
        $placeholders = implode(", ", array_map(fn($k) => ":$k", array_keys($rows[0])));
        $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";
        $stmt = $this->db->prepare($sql);

        $this->beginTransaction();
        try {
            foreach ($rows as $row) {
                $stmt->execute($row);
            }
            $this->commit();
            $this->lastSql = $sql;
            $this->lastBindings = $rows;
            return true;
        } catch (PDOException $e) {
            $this->rollBack();
            error_log("QueryBuilder DB Error [BATCH INSERT]: " . $e->getMessage());
            return false;
        }
    }

    public function update(array $data): bool {
        try {
            $set = implode(", ", array_map(fn($k) => "$k = :$k", array_keys($data)));
            $sql = "UPDATE {$this->table} SET $set " . $this->buildWhere();
            
            $stmt = $this->db->prepare($sql);

            $bindings = $data + $this->prepareBindings();

            $this->lastSql = $sql;
            $this->lastBindings = $bindings;
            
            return $stmt->execute($bindings);
        } catch (PDOException $e) {
            error_log("QueryBuilder DB Error [UPDATE]: " . $e->getMessage());
            return false;
        }
    }

    public function delete(): bool {
        try {
            $sql = "DELETE FROM {$this->table} " . $this->buildWhere();            
            $stmt = $this->db->prepare($sql);
            
            $bindings = $this->prepareBindings();
            $this->lastSql = $sql;
            $this->lastBindings = $bindings;
            
            return $stmt->execute($bindings);
        } catch (PDOException $e) {
            error_log("QueryBuilder DB Error [DELETE]: " . $e->getMessage());
            return false;
        }
    }

    public function softDelete(): bool {
        try {
            $sql = "UPDATE {$this->table} SET deleted_at = :deleted_at " . $this->buildWhere();
            $stmt = $this->db->prepare($sql);
            $bindings = ['deleted_at' => date('Y-m-d H:i:s')] + $this->prepareBindings();
            $this->lastSql = $sql;
            $this->lastBindings = $bindings;
            return $stmt->execute($bindings);
        } catch (PDOException $e) {
            error_log("QueryBuilder DB Error [SOFTDELETE]: " . $e->getMessage());
            return false;
        }
    }
    // Transaction Methods
    public function beginTransaction(): void {
        $this->db->beginTransaction();
    }

    public function commit(): void {
        $this->db->commit();
    }

    public function rollBack(): void {
        $this->db->rollBack();
    }

    // --- Utility Methods ---
    public function pluck(string $column): array {
        $rows = $this->get() ?: [];
        return array_map(fn($row) => $row[$column] ?? null, $rows);
    }

    public function count(): int {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} " . $this->buildWhere();
        $stmt = $this->db->prepare($sql);
        $stmt->execute($this->prepareBindings());
        $this->lastSql = $sql;
        $this->lastBindings = $this->prepareBindings();
        return (int) $stmt->fetchColumn();
    }

    // --- Internal Helpers ---
    private function buildSelect(): string {
        $columns = implode(", ", $this->select ?: ['*']);
        $sql = "SELECT $columns FROM {$this->table} " . $this->buildWhere();

        if (!empty($this->orderBy)) {
            $sql .= " ORDER BY " . implode(", ", $this->orderBy);
        }
        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }
        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }

        return $sql;
    }

    private function buildWhere(): string {
        if (empty($this->wheres)) return '';

        $sql = '';
        foreach ($this->wheres as $index => $where) {
            $boolean = $index === 0 ? '' : " {$where['boolean']} ";
            switch ($where['type']) {
                case 'null': $sql .= "{$boolean}{$where['column']} IS NULL"; break;
                case 'notnull': $sql .= "{$boolean}{$where['column']} IS NOT NULL"; break;
                case 'in':
                    $placeholders = implode(", ", array_map(fn($i) => ":param{$index}_$i", array_keys($where['values'])));
                    $sql .= "{$boolean}{$where['column']} IN ($placeholders)"; break;
                case 'notin':
                    $placeholders = implode(", ", array_map(fn($i) => ":param{$index}_$i", array_keys($where['values'])));
                    $sql .= "{$boolean}{$where['column']} NOT IN ($placeholders)"; break;
                case 'between':
                    $sql .= "{$boolean}{$where['column']} BETWEEN :param{$index}_0 AND :param{$index}_1"; break;
                case 'notbetween':
                    $sql .= "{$boolean}{$where['column']} NOT BETWEEN :param{$index}_0 AND :param{$index}_1"; break;
                default:
                    $sql .= "{$boolean}{$where['column']} {$where['operator']} :param$index";
            }
        }
        return $sql ? "WHERE $sql" : '';
    }

    private function prepareBindings(): array {
        $bindings = [];
        foreach ($this->wheres as $index => $where) {
            switch ($where['type']) {
                case 'basic': $bindings[":param$index"] = $where['value']; break;
                case 'in':
                case 'notin':
                    foreach ($where['values'] as $i => $val) $bindings[":param{$index}_$i"] = $val; break;
                case 'between':
                case 'notbetween':
                    $bindings[":param{$index}_0"] = $where['range'][0];
                    $bindings[":param{$index}_1"] = $where['range'][1];
                    break;
            }
        }
        return $bindings;
    }

    public function reset(): self {
        $this->table = '';
        $this->select = [];
        $this->wheres = [];
        $this->limit = null;
        $this->offset = null;
        $this->orderBy = [];
        $this->lastSql = '';
        $this->lastBindings = [];
        return $this;
    }

    // Get last inserted ID
    public function getLastInsertId(): string {
        return $this->db->lastInsertId();
    }

    public function toSql(): string {
        return $this->lastSql;
    }

    public function getBindings(): array {
        return $this->lastBindings;
    }

    public function debug(): string {
        $sql = $this->toSql();
        $bindings = $this->getBindings();
        foreach ($bindings as $key => $value) {
            $replace = is_string($value) ? "'$value'" : $value;
            $sql = preg_replace("/\b$key\b/", $replace, $sql);
        }
        return $sql;
    }
}
