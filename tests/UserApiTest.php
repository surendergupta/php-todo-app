<?php
namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Core\Router;
use App\Core\Request;

class UserApiTest extends TestCase
{
    private Router $router;

    public function setUp(): void
    {
        // Boot router from routes file
        $this->router = require __DIR__ . '/../routes/web.php';
    }

    private function dispatchAndGetJson(Request $request): array
    {
        ob_start();
        $this->router->dispatch($request->getMethod(), $request->getUri(), $request);
        $output = ob_get_clean(); // safely get and close

        $this->assertNotEmpty($output, 'Router returned no output');
        $this->assertJson($output, 'Router did not return valid JSON');

        return json_decode($output, true, 512, JSON_THROW_ON_ERROR);
    }

    public function test_can_list_users(): void
    {
        $request = new Request();
        $request->setUri('/users');
        $request->setMethod('GET');
        $data = $this->dispatchAndGetJson($request);
        $this->assertIsArray($data);
    }

    // public function test_can_register_user(): void
    // {
    //     $request = new Request();
    //     $request->setUri('/users/register');
    //     $request->setMethod('POST');
    //     $request->setBody([
    //         'user_id'=> 'testuser1',
    //         'email_address' => 'testuser1@doe.com',
    //         'user_password' => 'TestPass123',
    //         'first_name' => 'Test',
    //         'last_name' => 'user',
    //         'is_admin' => false
    //     ]);
    //     $data = $this->dispatchAndGetJson($request);
    //     $this->assertArrayHasKey('message', $data);
    //     $this->assertArrayHasKey('user', $data);
    //     $this->assertArrayHasKey('id', $data['user']);
    //     $this->assertArrayHasKey('user_id', $data['user']);
    //     $this->assertSame('testuser1', $data['user']['user_id']);
    //     $this->assertSame('User created successfully', $data['message']);
    //     $this->assertArrayNotHasKey('user_password', $data['user']);
        
    // }
    
    public function test_register_user_validation_fails(): void
    {
        $request = new Request();
        $request->setUri('/users/register');
        $request->setMethod('POST');
        $request->setBody([
            'user_id'=> 'testuser1',
            'email_address' => 'testuser1@doe.com',            
            'first_name' => 'Test',
            'last_name' => 'user',
            'is_admin' => false
        ]);
        
        $data = $this->dispatchAndGetJson($request);        
        $this->assertArrayHasKey('errors', $data);
        $this->assertContains('Missing or empty field: user_password', $data['errors']);
    }

    
    public function test_can_show_user(): void
    {
        // First register a user to ensure one exists
        // $registerReq = new Request();
        // $registerReq->setUri('/users/register');
        // $registerReq->setMethod('POST');
        // $registerReq->setBody([
        //     'user_id'=> 'testuser2',
        //     'email_address' => 'testuser2@doe.com',
        //     'user_password' => 'TestPass123',
        //     'first_name' => 'Test',
        //     'last_name' => 'user',
        //     'is_admin' => false
        // ]);
        // $registered = $this->dispatchAndGetJson($registerReq);
        // $this->assertArrayHasKey('id', $registered['user']);
        // $userId = $registered['user']['user_id'];
        $userId = 'testuser2';
        // Now show that user
        $showReq = new Request();
        $showReq->setUri("/users/$userId");
        $showReq->setMethod('GET');
        $shown = $this->dispatchAndGetJson($showReq);
        
        $this->assertArrayHasKey('id', $shown);
        $this->assertArrayHasKey('user_id', $shown);
        $this->assertSame('testuser2', $shown['user_id']);
    }
    
    public function test_show_user_not_found(): void
    {
        $request = new Request();
        $request->setUri('/users/99999'); // assuming this ID does not exist
        $request->setMethod('GET');
        $data = $this->dispatchAndGetJson($request);
        
        $this->assertArrayHasKey('error', $data);
        $this->assertSame('User not found', $data['error']);
    }
    
    public function test_can_update_user(): void
    {
        // First register a user to ensure one exists
        // $registerReq = new Request();
        // $registerReq->setUri('/users/register');
        // $registerReq->setMethod('POST');
        // $registerReq->setBody([
        //     'user_id'=> 'testuser3',
        //     'email_address' => 'testuser3@doe.com',
        //     'user_password' => 'TestPass123',
        //     'first_name' => 'Test',
        //     'last_name' => 'user',
        //     'is_admin' => false
        // ]);
        // $registered = $this->dispatchAndGetJson($registerReq);
        // $this->assertArrayHasKey('id', $registered['user']);
        // $userId = $registered['user']['id'];
        $userId = 'testuser3';
        // Now update that user
        $updateReq = new Request();
        $updateReq->setUri("/users/$userId");
        $updateReq->setMethod('PUT');
        $updateReq->setBody([
            'first_name' => 'Test3',
            'last_name' => 'user3',
            'is_admin' => false
        ]);
        $updated = $this->dispatchAndGetJson($updateReq);

        $this->assertArrayHasKey('message', $updated);
        $this->assertSame('User updated successfully', $updated['message']);
        
        $this->assertArrayHasKey('user', $updated);
        $this->assertSame('testuser3', $updated['user']['user_id']);
        $this->assertSame('Test3', $updated['user']['first_name']);
        $this->assertSame('user3', $updated['user']['last_name']);
    }

   
    // public function test_can_delete_user(): void
    // {
    //     // First register a user to ensure one exists
    //     $registerReq = new Request();
    //     $registerReq->setUri('/users/register');
    //     $registerReq->setMethod('POST');
    //     $registerReq->setBody([
    //         'user_id'=> 'deletetestuser3',
    //         'email_address' => 'deletetestuser3@doe.com',
    //         'user_password' => 'TestPass123',
    //         'first_name' => 'Test',
    //         'last_name' => 'user',
    //         'is_admin' => false
    //     ]);
    //     $registered = $this->dispatchAndGetJson($registerReq);
    //     $this->assertArrayHasKey('id', $registered['user']);
    //     $userId = $registered['user']['user_id'];
    //     // $userId = 'deletetestuser3';
    //     // Now delete that user
    //     $deleteReq = new Request();
    //     $deleteReq->setUri("/users/$userId");
    //     $deleteReq->setMethod('DELETE');
    //     $deleted = $this->dispatchAndGetJson($deleteReq);
        
    //     $this->assertArrayHasKey('message', $deleted);
    //     $this->assertSame('User deleted successfully', $deleted['message']);
    // }

   
    public function test_delete_user_not_found(): void
    {
        $request = new Request();
        $request->setUri('/users/99999'); // assuming this ID does not exist
        $request->setMethod('DELETE');
        $data = $this->dispatchAndGetJson($request);
        
        $this->assertArrayHasKey('error', $data);
        $this->assertSame('User not found', $data['error']);
    }

     
    public function test_can_login_user(): void
    {
        // First register a user to ensure one exists
        // $registerReq = new Request();
        // $registerReq->setUri('/users/register');
        // $registerReq->setMethod('POST');
        // $registerReq->setBody([
        //     'user_id'=> 'loginuser1',
        //     'email_address' => 'LoginPass1231@doe.com',
        //     'user_password' => 'LoginPass123',
        //     'first_name' => 'Test',
        //     'last_name' => 'user',
        //     'is_admin' => false
        // ]);
        // $registered = $this->dispatchAndGetJson($registerReq);
        
        // $this->assertArrayHasKey('id', $registered['user']);
        // $userId = $registered['user']['user_id'];
        // $userPassword = $registered['user']['user_password'];

        $userId = 'loginuser1';
        $userPassword = 'LoginPass123';
        // Now login that user
        $loginReq = new Request();
        $loginReq->setUri("/auth/login");
        $loginReq->setMethod('POST');
        $loginReq->setBody([
            'user_id' => $userId,
            'user_password' => $userPassword
        ]);
        $logged = $this->dispatchAndGetJson($loginReq);
        
        $this->assertArrayHasKey('message', $logged);
        $this->assertArrayHasKey('user', $logged);
        $this->assertArrayHasKey('user_id', $logged['user']);
        $this->assertArrayHasKey('token', $logged['user']);
        $this->assertSame($userId, $logged['user']['user_id']);
        $this->assertSame('User logged in successfully', $logged['message']);
    }

   

    public function test_login_user_invalid_credentials(): void
    {
        $request = new Request();
        $request->setUri('/auth/login');
        $request->setMethod('POST');
        $request->setBody([
            'user_id' => 'nonexistentuser',
            'user_password' => 'WrongPass'
        ]);
        $data = $this->dispatchAndGetJson($request);
        
        $this->assertArrayHasKey('error', $data);
        $this->assertSame('Invalid credentials', $data['error']);
    }
   
    
    public function test_login_user_validation_fails(): void
    {
        $request = new Request(); // missing password
        $request->setUri('/auth/login');
        $request->setMethod('POST');
        $request->setBody([
            'user_id' => 'loginuser1',
        ]);
        
        $data = $this->dispatchAndGetJson($request);       

        $this->assertArrayHasKey('errors', $data);
        $this->assertContains('Missing or empty field: user_password', $data['errors']);
    }

}