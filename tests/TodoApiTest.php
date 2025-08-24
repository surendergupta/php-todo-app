<?php
namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Core\Router;
use App\Core\Request;

class TodoApiTest extends TestCase
{
    private Router $router;
    private int $todoId;

    protected function setUp(): void
    {
        // Boot router from routes file
        $this->router = require __DIR__ . '/../routes/web.php';
    }

    private function dispatchAndGetJson(Request $request): array
    {
        ob_start();
        $this->router->dispatch($request->getMethod(), $request->getUri(), $request);
        $output = ob_get_clean();
        $this->assertNotEmpty($output, 'Router returned no output');
        $this->assertJson($output, 'Router did not return valid JSON');
        return json_decode($output, true, 512, JSON_THROW_ON_ERROR);
    }

    public function test_can_list_todos(): void
    {
        $request = new Request();
        $request->setUri('/todos');
        $request->setMethod('GET');
        $data = $this->dispatchAndGetJson($request);
        $this->assertIsArray($data);
    }

    public function test_can_create_todo(): void
    {
        $request = new Request();
        $request->setUri('/todos');
        $request->setMethod('POST');
        $request->setBody(['title' => 'My First Todo']);
        $data = $this->dispatchAndGetJson($request);

        $this->assertArrayHasKey('id', $data);
        $this->assertSame('My First Todo', $data['title']);

        $this->todoId = $data['id']; // save for next tests
    }

    public function test_create_todo_validation_fails(): void
    {
        $request = new Request(); // missing title
        $request->setUri('/todos');
        $request->setMethod('POST');
        $request->setBody(['title' => '']);
        
        $data = $this->dispatchAndGetJson($request);

        $this->assertArrayHasKey('errors', $data);
        $this->assertContains("Missing or empty field: title", $data['errors']);
    }

    public function test_can_update_todo(): void
    {
        // First create a todo
        $createReq = new Request();
        $createReq->setUri('/todos');
        $createReq->setMethod('POST');
        $createReq->setBody(['title' => 'Todo upadte me']);
        $created = $this->dispatchAndGetJson($createReq);

        $this->assertArrayHasKey('id', $created);
        $this->assertArrayHasKey('title', $created);

        $id = $created['id'];

        // Update it
        $updateReq = new Request();
        $updateReq->setUri("/todos/$id");
        $updateReq->setMethod('PUT');
        $updateReq->setBody(['title' => 'Updated title']);
        $updated = $this->dispatchAndGetJson($updateReq);

        $this->assertArrayHasKey('id', $updated);
        $this->assertArrayHasKey('title', $updated);
        $this->assertSame($id, $updated['id']);
        $this->assertSame('Updated title', $updated['title']);
    }

    public function test_can_delete_todo(): void
    {
        // Create a todo to delete
        $createReq = new Request();
        $createReq->setUri('/todos');
        $createReq->setMethod('POST');
        $createReq->setBody(['title' => 'Todo to delete']);
        $created = $this->dispatchAndGetJson($createReq);

        $this->assertArrayHasKey('id', $created);
        $this->assertArrayHasKey('title', $created);
        $this->assertSame('Todo to delete', $created['title']);

        $id = $created['id'];

        // Delete it
        $deleteReq = new Request();
        $deleteReq->setUri("/todos");
        $deleteReq->setMethod('DELETE');
        $deleteReq->setBody(['id' => $id]);
        $deleted = $this->dispatchAndGetJson($deleteReq);

        $this->assertArrayHasKey('message', $deleted);
        $this->assertSame('Todo item deleted successfully', $deleted['message']);
    }
}
