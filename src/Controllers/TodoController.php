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
        if (empty($data['user_id'])) {
            return Response::json(['error' => 'User ID is required'], 422);
        }
        $todo = $this->service->addTodo($data);
        return Response::json($todo, 201);
    }

    /**
     * Update a todo item.
     *
     * @param Request $request
     * @param string $id
     * @return Response
     */
    public function update(Request $request, int $id): Response {
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
    public function destroy(Request $request, $jwt, $id): Response {
        // Get token from Authorization header
        $token = $request->getHeader('Authorization');
        
        if (strpos($token, 'Bearer ') === 0) {
            $token = substr($token, 7);
        }
        if (empty($token)) {
            return Response::json(['error' => 'Authorization token is required'], 400);
        }
        $decoded = $jwt->verify($token, true);
        // var_dump($decoded);
        // Handle service error
        if (isset($decoded['error'])) {
            return Response::json(['error' => $decoded['error']], $decoded['code'] ?? 409);
        }

        $id = (int) ($id ?? 0);
        if (!$id) {
            return Response::json(['error' => 'Invalid ID'], 400);
        }

        $todo = $this->service->getTodoById($id);
        if (!$todo) {
            return Response::json(['error' => 'Todo not found or already deleted'], 404);
        }

        $token_user_id = $decoded['user_id'];
        if($token_user_id !== $todo['user_id']) {
            return Response::json(['error' => 'Unauthorized access'], 401);
        }

        $deleted = $this->service->removeTodo($id);
        if (!$deleted) {
            return Response::json(['error' => 'Todo not found'], 404);
        }
        return Response::json(['message' => 'Todo item deleted successfully'], 200);
    }
}
