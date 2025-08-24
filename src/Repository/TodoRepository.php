<?php
// src/Repository/TodoRepository.php
namespace App\Repository;

use PDO;

class TodoRepository {
    public function __construct(private PDO $db) {}

    /**
     * Get all todos.
     *
     * @return array
     */
    public function all(): array {
        $stmt = $this->db->query("SELECT id, title FROM todos ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get a single todo by ID.
     *
     * @param int $id
     * @return array|bool
     */
    public function find(int $id): array|bool {
        $stmt = $this->db->prepare("SELECT id, title FROM todos WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            return false;
        }
        return $user;
    }

    /**
     * Create a new todo item.
     *
     * @param string $title
     * @return int ID of the newly created todo item
     */
    public function create(string $title): int {
        $stmt = $this->db->prepare("INSERT INTO todos (title) VALUES (:title)");
        $stmt->execute(['title' => $title]);
        return (int) $this->db->lastInsertId();
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
        $stmt = $this->db->prepare("UPDATE todos SET title = :title WHERE id = :id");
        return $stmt->execute([
            'title' => $title,
            'id'    => $id
        ]);        
    }

    /**
     * Delete a todo item by ID.
     *
     * @param int $id The ID of the todo item to delete
     *
     * @return bool Whether the deletion was successful
     */
    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM todos WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
