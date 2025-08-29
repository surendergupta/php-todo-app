<?php
// src/Repository/TodoRepository.php
declare(strict_types=1);

namespace App\Repository;

class TodoRepository extends BaseRepository {
    private string $table = 'todos';

    /**
     * Get all todos.
     *
     * @return array|false An array of todo items, or false on error
     */
    public function all(): ?array {
        return $this->safeExecute(
            fn() => $this->qb()->reset()
                ->table($this->table)
                ->select(['id', 'title', 'user_id', 'deleted_at'])
                ->whereNull('deleted_at')
                ->orderBy('id', 'DESC')
                ->get()
        );
    }

    /**
     * Get a single todo by ID.
     *
     * @param int $id
     * @return array|bool
     */
    public function find(int $id): ?array {
        return $this->safeExecute(
            fn() => $this->qb()->reset()
                ->table($this->table)
                ->select(['id', 'title', 'user_id', 'deleted_at'])
                ->where('id', '=', $id)
                ->whereNull('deleted_at')
                ->first()
        );
    }

    /**
     * Create a new todo item.
     *
     * @param array $data
     * @return int ID of the newly created todo item
     */
    public function create(array $data): int {
        return (int) $this->safeExecute(
            fn() => $this->qb()->reset()
                ->table($this->table)
                ->insert(['title' => $data['title'], 'user_id' => $data['user_id']])
                ? $this->qb()->getLastInsertId() 
                : null
        );
    }

    /**
     * Update a todo item.
     *
     * @param int $id The ID of the todo item to update
     * @param string $title The new title of the todo item
     *
     * @return bool Whether the update was successful
     */
    public function update(int $id, string $title): bool {
        return $this->safeExecute(
            fn() => $this->qb()->reset()
                ->table($this->table)
                ->where('id', '=', $id)
                ->update(['title' => $title])
        );
    }

    /**
     * Delete a todo item by ID.
     *
     * @param int $id The ID of the todo item to delete
     *
     * @return bool Whether the deletion was successful
     */
    public function delete(int $id): bool {
        return $this->safeExecute(
            fn() => $this->qb()->reset()
                ->table($this->table)
                ->where('id', '=', $id)
                ->delete()
        );
    }

    /**
     * Soft delete a todo item by ID.
     *
     * @param int $id The ID of the todo item to soft delete
     *
     * @return bool Whether the soft deletion was successful
     */
    public function softDelete(int $id): bool {
        return $this->safeExecute(
            fn() => $this->qb()->reset()
                ->table($this->table)
                ->where('id', '=', $id)
                ->softDelete()
        );
    }
}
