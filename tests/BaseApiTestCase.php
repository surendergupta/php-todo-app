<?php
namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Core\Router;
use App\Core\Request;

abstract class BaseApiTestCase extends TestCase
{
    protected static Router $router;
    protected static string $password = 'TestPass123';
    
    /** Per-test dynamic values */
    protected string $testUserId;
    protected string $testEmail;
    protected string $token;

    public static function setUpBeforeClass(): void
    {
        // Ensure router loaded once
        self::$router = require __DIR__ . '/../routes/api.php';
    }

    public function setUp(): void
    {
        // set env secret
        putenv('JWT_SECRET=secret@123');
        $_ENV['JWT_SECRET'] = 'secret@123';

        // create unique user
        $this->testUserId = 'user_' . bin2hex(random_bytes(4));
        $this->testEmail  = $this->testUserId . '@test.com';
        $this->dispatchAndGetJsonStatic(
            $this->makeRequest('POST', '/api/v1/users/register', [
                'user_id'       => $this->testUserId,
                'email_address' => $this->testEmail,
                'user_password' => self::$password,
                'first_name'    => 'Test',
                'last_name'     => 'User',
                'is_admin'      => false
            ])
        );

        // login and get token
        $login = $this->dispatchAndGetJsonStatic(
            $this->makeRequest('POST', '/api/v1/auth/login', [
                'user_id'       => $this->testUserId,
                'user_password' => self::$password
            ])
        );

        $this->token = $login['user']['token'] ?? '';
        $this->assertNotEmpty($this->token, 'Login did not return a valid token');
    }

    protected function withAuth(Request $request): void
    {
        $authHeader = "Bearer {$this->token}";
        $request->setHeader('Authorization', $authHeader);
        $_SERVER['HTTP_AUTHORIZATION'] = $authHeader;
    }

    protected static function dispatchAndGetJsonStatic(Request $request): array
    {
        ob_start();
        self::$router->dispatch($request->getMethod(), $request->getUri(), $request);
        $output = ob_get_clean(); 
        
        self::assertNotEmpty($output, 'Router returned no output');
        self::assertJson($output, 'Router did not return valid JSON');

        return json_decode($output, true, 512, JSON_THROW_ON_ERROR);
    }

    protected function makeRequest(string $method, string $uri, array $body = []): Request
    {
        $req = new Request();
        $req->setUri($uri);
        $req->setMethod($method);
        $req->setBody($body);
        return $req;
    }
}