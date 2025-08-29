<?php
namespace Tests;

use App\Core\Request;

class UserApiTest extends BaseApiTestCase
{
    private string $testNewUserId;
    private string $testNewEmail;

    public function setUp(): void
    {
        parent::setUp();

        $this->testNewUserId = 'user_' . bin2hex(random_bytes(4));
        $this->testNewEmail  = "{$this->testNewUserId}@test.com";
    }    
    
    public function test_can_list_users(): void
    {
        $request = new Request();
        $request->setUri('/api/v1/users/');
        $request->setMethod('GET');
        $data = self::dispatchAndGetJsonStatic($request);
        $this->assertIsArray($data);
    }
   
    public function test_can_register_user(): void
    {
        $request = new Request();
        $request->setUri('/api/v1/users/register/');
        $request->setMethod('POST');
        $request->setBody([
            'user_id'=> $this->testNewUserId,
            'email_address' => $this->testNewEmail,
            'user_password' => self::$password,
            'first_name' => 'Test',
            'last_name' => 'user',
            'is_admin' => false
        ]);
        $data = self::dispatchAndGetJsonStatic($request);
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('user', $data);
        $this->assertArrayHasKey('id', $data['user']);
        $this->assertArrayHasKey('user_id', $data['user']);
        $this->assertSame($this->testNewUserId, $data['user']['user_id']);
        $this->assertSame('User created successfully', $data['message']);
        $this->assertArrayNotHasKey('user_password', $data['user']);
        
    }
   
    public function test_register_user_validation_fails(): void
    {
        $request = new Request();
        $request->setUri('/api/v1/users/register');
        $request->setMethod('POST');
        $request->setBody([
            'user_id'=> $this->testNewUserId,
            'email_address' => $this->testNewEmail,            
            'first_name' => 'Test',
            'last_name' => 'user',
            'is_admin' => false
        ]);
        
        $data = self::dispatchAndGetJsonStatic($request); 
        $this->assertArrayHasKey('errors', $data);
        $this->assertContains('user_password is required', $data['errors']['user_password']);
    }
     
    
    public function test_can_show_user(): void
    {
        $showReq = new Request();
        $showReq->setUri("/api/v1/users/{$this->testUserId}");
        $showReq->setMethod('GET');
        $shown = self::dispatchAndGetJsonStatic($showReq);
        // var_dump($shown);
        $this->assertArrayHasKey('user_id', $shown);
        // $this->assertSame(self::$testUserId, $shown[0]['user_id']);
    }
    
    public function test_show_user_not_found(): void
    {
        $request = new Request();
        $request->setUri('/api/v1/users/99999'); // assuming this ID does not exist
        $request->setMethod('GET');
        $data = self::dispatchAndGetJsonStatic($request);
        
        $this->assertArrayHasKey('error', $data);
        $this->assertSame('User not found', $data['error']);
    }

    public function test_can_login_user(): void
    {
        // First register a user to ensure one exists
        $registerReq = new Request();
        $registerReq->setUri('/api/v1/users/register');
        $registerReq->setMethod('POST');
        $registerReq->setBody([
            'user_id'=> $this->testNewUserId,
            'email_address' => $this->testNewEmail,
            'user_password' => self::$password,
            'first_name' => 'Test',
            'last_name' => 'user',
            'is_admin' => false
        ]);
        $registered = self::dispatchAndGetJsonStatic($registerReq);
        
        $this->assertArrayHasKey('id', $registered['user']);
        $userId = $registered['user']['user_id'];        
        // Now login that user
        $loginReq = new Request();
        $loginReq->setUri("/api/v1/auth/login");
        $loginReq->setMethod('POST');
        $loginReq->setBody([
            'user_id' => $userId,
            'user_password' => self::$password,
        ]);
        $logged = self::dispatchAndGetJsonStatic($loginReq);
        
        $this->assertArrayHasKey('message', $logged);
        $this->assertArrayHasKey('user', $logged);
        $this->assertArrayHasKey('user_id', $logged['user']);
        $this->assertArrayHasKey('token', $logged['user']);
        $this->assertSame($userId, $logged['user']['user_id']);
        $this->assertSame('User logged in successfully', $logged['message']);
        
    }

    public function test_login_user_validation_fails(): void
    {
        $request = new Request(); // missing password
        $request->setUri('/api/v1/auth/login');
        $request->setMethod('POST');
        $request->setBody([
            'user_id' => 'loginuser1',
        ]);        
        $data = self::dispatchAndGetJsonStatic($request);       
        // var_dump($data);
        $this->assertArrayHasKey('errors', $data);
        $this->assertContains('user_password is required', $data['errors']['user_password']);
    }   

    public function test_login_user_invalid_credentials(): void
    {
        $request = new Request();
        $request->setUri('/api/v1/auth/login');
        $request->setMethod('POST');
        $request->setBody([
            'user_id' => 'nonexistentuser',
            'user_password' => 'WrongPass'
        ]);
        $data = self::dispatchAndGetJsonStatic($request);
        
        $this->assertArrayHasKey('error', $data);
        $this->assertSame('Invalid credentials', $data['error']);
    }
    
    public function test_can_update_user(): void
    {
        // Now update that user
        $firstname = bin2hex(random_bytes(8));
        $lastname = bin2hex(random_bytes(4));
        $updateReq = new Request();
        $updateReq->setUri("/api/v1/users/{$this->testUserId}");
        $updateReq->setMethod('PUT');
        $this->withAuth($updateReq);
        $updateReq->setBody([
            'first_name' => $firstname,
            'last_name' => $lastname,
            'is_admin' => false
        ]);
        $updated = self::dispatchAndGetJsonStatic($updateReq);
        // var_dump($updated);
        if(isset($updated['error'])) {
            $this->fail($updated['error']);
            $this->assertSame('Unauthorized', $updated['error']);
            // $this->assertSame('Route not found', $updated['error']);
        }
        $this->assertArrayHasKey('message', $updated);
        $this->assertSame('User updated successfully', $updated['message']);
        
        $this->assertArrayHasKey('user', $updated);
        $this->assertSame($this->testUserId, $updated['user']['user_id']);
        $this->assertSame($firstname, $updated['user']['first_name']);
        $this->assertSame($lastname, $updated['user']['last_name']);
    }

    public function test_delete_user_not_found(): void
    {
        $request = new Request();
        $request->setUri('/api/v1/users/99999'); // assuming this ID does not exist
        $request->setMethod('DELETE');
        $this->withAuth($request);
        $data = self::dispatchAndGetJsonStatic($request);
        
        $this->assertArrayHasKey('error', $data);
        $this->assertSame('Unauthorized access', $data['error']);
        // $this->assertSame('Route not found', $data['error']);
    }
   
    public function test_can_delete_user(): void
    {
        // Now delete that user
        $deleteReq = new Request();
        $deleteReq->setUri("/api/v1/users/{$this->testUserId}");
        $deleteReq->setMethod('DELETE');
        $this->withAuth($deleteReq);
        $deleted = self::dispatchAndGetJsonStatic($deleteReq);
        // var_dump($deleted);
        $this->assertArrayHasKey('message', $deleted);
        $this->assertSame('User deleted successfully', $deleted['message']);
    }
}