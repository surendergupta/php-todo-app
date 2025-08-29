<?php
namespace Tests;

use App\Core\Request;

class TodoApiTest extends BaseApiTestCase
{
    private int $todoId;
    
    public function setUp(): void
    {
        parent::setUp();
    }    
    
    public function test_can_list_todos(): void
    {
        $request = new Request();
        $request->setUri('/api/v1/todos');
        $request->setMethod('GET');
        $this->withAuth($request);

        $data = self::dispatchAndGetJsonStatic($request);
        $this->assertIsArray($data);
    }
    
    public function test_can_create_todo(): void
    {
        $request = new Request();
        $request->setUri('/api/v1/todos');
        $request->setMethod('POST');
        $this->withAuth($request);
        $request->setBody(['title' => 'My First Todo', 'user_id' => $this->testUserId]);

        $data = self::dispatchAndGetJsonStatic($request);
        // var_dump($data);
        $this->assertArrayHasKey('id', $data);
        $this->assertSame('My First Todo', $data['title']);        
    }
 
    public function test_create_todo_validation_fails(): void
    {
        $request = new Request(); // missing title
        $request->setUri('/api/v1/todos');
        $request->setMethod('POST');
        $this->withAuth($request);
        $request->setBody(['title' => '', 'user_id' => $this->testUserId]);
        
        $data = self::dispatchAndGetJsonStatic($request);
        // var_dump($data);
        $this->assertArrayHasKey('errors', $data);
        $this->assertContains("title is required", $data['errors']['title']);
        $this->assertContains("title must be at least 3 characters", $data['errors']['title']);
    }

    public function test_can_update_todo(): void
    {
        $createReq = new Request();
        $createReq->setUri('/api/v1/todos');
        $createReq->setMethod('POST');
        $this->withAuth($createReq);
        $createReq->setBody(['title' => 'My First update Todo','user_id' => $this->testUserId ]);
        $data = self::dispatchAndGetJsonStatic($createReq);
        
        $this->assertArrayHasKey('id', $data);
        $this->assertSame('My First update Todo', $data['title']);
        $this->assertNotEmpty($data['id'], 'Todo ID should not be empty after creation');
        $todoId = $data['id'];

        // Update it
        $updateReq = new Request();
        $updateReq->setUri("/api/v1/todos/{$todoId}");
        $updateReq->setMethod('PUT');
        $this->withAuth($updateReq);
        $updateReq->setBody(['title' => 'Updated title','user_id' => $this->testUserId]);
        $updated = self::dispatchAndGetJsonStatic($updateReq);
        
        $this->assertArrayHasKey('id', $updated);
        $this->assertArrayHasKey('title', $updated);
        $this->assertNotEmpty($updated['id'], 'Todo ID should not be update after creation');
        $this->assertSame($todoId, $updated['id']);
        $this->assertSame('Updated title', $updated['title']);
    }
    
    public function test_can_delete_todo(): void
    {
        // Create a todo to delete
        $createReq = new Request();
        $createReq->setUri('/api/v1/todos');
        $createReq->setMethod('POST');
        $this->withAuth($createReq);
        $createReq->setBody(['title' => 'Todo to delete', 'user_id' => $this->testUserId]);
        $created = self::dispatchAndGetJsonStatic($createReq);
        
        $this->assertArrayHasKey('id', $created);
        $this->assertArrayHasKey('title', $created);
        $this->assertSame('Todo to delete', $created['title']);

        $id = $created['id'];

        // Delete it
        $deleteReq = new Request();
        $deleteReq->setUri("/api/v1/todos/$id");
        $deleteReq->setMethod('DELETE');
        $this->withAuth($deleteReq);        
        $deleted = self::dispatchAndGetJsonStatic($deleteReq);

        $this->assertArrayHasKey('message', $deleted);
        $this->assertSame('Todo item deleted successfully', $deleted['message']);
    }
}
