<?php
// src/Controllers/TodoController.php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\TodoService;

class TodoController {
    private TodoService $service;

    /**
     * Initialize the controller with a TodoService instance.
     *
     * @param TodoService $service
     */
    public function __construct(TodoService $service) {
        $this->service = $service;
    }

    /**
     * List all todos.
     *
     * @return Response
     */
    
    public function index(): Response {
        
        $todos = $this->service->listTodos();
        return Response::json($todos, 200);
    }

    public function show(array $args): Response {
        $id = (int) ($args['id'] ?? 0);
        if (!$id) {
            return Response::json(['error' => 'Invalid ID'], 400);
        }

        $todo = $this->service->getTodoById($id);
        if (!$todo) {
            return Response::json(['error' => 'Todo not found'], 404);
        }
        return Response::json($todo, 200);
    }

    /**
     * Create a new todo item.
     *
     * @param Request $request
     * @return Response
     */
    
    public function store(Request $request): Response {
        $data = $request->getBody();
        if (empty($data['title'])) {
            return Response::json(['error' => 'Title is required'], 422);
        }
        $todo = $this->service->addTodo($data['title']);
        return Response::json($todo, 201);
    }

    /**
     * Update a todo item.
     *
     * @param Request $request
     * @param string $id
     * @return Response
     */
    public function update(Request $request, string $id): Response {
        $data = $request->getBody();
        $response = $this->service->updateTodo((int)$id, $data); 
        return Response::json($response, 200);
    }

    /**
     * Delete a todo item.
     *
     * @param array $args [id] ID of the todo item to delete
     * @return Response
     */
    public function destroy(array $args): Response {
        $id = (int) ($args['id'] ?? 0);
        if (!$id) {
            return Response::json(['error' => 'Invalid ID'], 400);
        }
        
        $deleted = $this->service->removeTodo($id);
        if (!$deleted) {
            return Response::json(['error' => 'Todo not found'], 404);
        }
        return Response::json(['message' => 'Todo item deleted successfully'], 200);
    }
}
