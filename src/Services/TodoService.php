<?php
// src/Services/TodoService.php
namespace App\Services;

use App\Repository\TodoRepository;

class TodoService {
    public function __construct(private TodoRepository $repo) {}

    /**
     * Get all todos.
     *
     * @return array An array of associative arrays with the data of all todos.
     */
    public function listTodos(): array {
        return $this->repo->all();
    }

    /**
     * Get a single todo by ID.
     *
     * @param int $id The ID of the todo item to retrieve.
     *
     * @return array|false If the todo item is found, returns an array with its data.
     *                     If the todo item is not found, returns a boolean false.
     */
    public function getTodoById(int $id): array|false {
        return $this->repo->find($id);
    }

    /**
     * Add a new todo item.
     *
     * @param string $title The title of the new todo item.
     *
     * @return array An array containing the ID of the new todo item and its title.
     */
    public function addTodo(string $title): array {
        $id = $this->repo->create($title);
        return ['id' => $id, 'title' => $title];
    }
    
    /**
     * Update a todo item.
     *
     * @param int $id The ID of the todo item to update
     * @param array $data The data to update the todo item with.
     *                      The array should contain a 'title' key with the new title.
     *
     * @return array|bool If the update was successful, returns an array with the updated
     *                    todo item. If the update was not successful, returns a boolean
     *                    false.
     * @throws \InvalidArgumentException If the 'title' key is not present in the data.
     */
    public function updateTodo(int $id, array $data): array|bool {
        $title = $data['title'];
        if (empty($title)) return ['error' => 'Title is required', 422];        
        $this->repo->update($id, $title);  
        return $this->repo->find($id);
    }

    /**
     * Delete a todo item.
     *
     * @param int $id The ID of the todo item to delete
     *
     * @return bool Whether the deletion was successful
     */
    public function removeTodo(int $id): bool {
        return $this->repo->delete($id);
    }
}
