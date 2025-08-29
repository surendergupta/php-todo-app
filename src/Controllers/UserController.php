<?php
// src/Controllers/UserController.php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\UserService;


class UserController 
{
    private UserService $service;
    protected const PASSWORD_BCRYPT_COST = 10;

    /**
     * Initialize the controller with a UserService instance.
     *
     * @param UserService $service
     */
    public function __construct(UserService $service) {
        $this->service = $service;
    }

    /**
     * Validate user data
     */
    private function validateUserData(array $data, bool $isUpdate = false): ?array
    {
        $errors = [];

        // Email validation
        if (empty($data['email_address']) && isset($data['email_address'])) {
            $errors[] = 'Email is required';
        } elseif (isset($data['email_address']) && !filter_var($data['email_address'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }

        // Password validation (skip for update)
        if (!$isUpdate && empty($data['user_password']) && isset($data['user_password'])) {
            $errors[] = 'Password is required';
        }

        // First name
        if (empty($data['first_name']) && isset($data['first_name'])) {
            $errors[] = 'First name is required';
        }

        // Last name
        if (empty($data['last_name']) && isset($data['last_name'])) {
            $errors[] = 'Last name is required';
        }

        // Handle is_admin separately (since it can be false)
        // if (!array_key_exists('is_admin', $data)) {
        //     $errors[] = "Missing field: is_admin";
        // }

        return $errors ?: null;
    }

    private function validateLoginData(array $data): ?array {
        $errors = [];
        if (empty($data['user_id'])) $errors[] = 'User ID is required';
        if (empty($data['user_password'])) $errors[] = 'Password is required';
        return $errors ?: null;
    }

    /**
     * List all users
     */
    public function index(Request $request): Response
    {
        $users = $this->service->listUsers();

        // Hide passwords
        foreach ($users as &$user) {
            unset($user['user_password']);
        }

        return Response::json($users, 200);
    }

    /**
     * Create a new user
     */
    public function store(Request $request): Response {
        // Get user data from request body
        $data = $request->getBody();
        // Validate user data here
        $errors = $this->validateUserData($data);
        if ($errors) {
            return Response::json(['errors' => $errors], 400);
        }
        // Create user logic here
        $data['is_admin'] = $data['is_admin'] ?? false;
        $data['user_password'] = password_hash($data['user_password'], PASSWORD_BCRYPT, ['cost' => self::PASSWORD_BCRYPT_COST]);        
        
        $user = $this->service->addUser(
            $data['user_id'], 
            $data['email_address'], 
            $data['user_password'], 
            $data['first_name'], 
            $data['last_name'], 
            $data['is_admin']
        );

        // Handle service error
        if (isset($user['error'])) {
            return Response::json(['error' => $user['error']], $user['code'] ?? 409);
        }

        unset($user['user_password']);
        
        // Return user details
        return Response::json(['message' => 'User created successfully', 'user' => $user], 201);
    }

    /**
     * Get a single user
     */
    public function show(Request $request, array $args): Response {
        $user_id = (string) ($args['user_id'] ?? '');
        if ($user_id === '') {
            return Response::json(['error' => 'Invalid USER_ID'], 400);
        }
        // Fetch user by ID logic here
        $user = $this->service->findUser($user_id);
        if (!$user) {
            return Response::json(['error' => 'User not found'], 404);
        }

        unset($user['user_password']);
        // Return user details
        return Response::json($user, 200);        
    }

    /**
     * Update an existing user
     */
    public function update(Request $request, array $args): Response {
        // Validate user ID
        $user_id = (string) ($args['user_id'] ?? '');
        if ($user_id === '') {
            return Response::json(['error' => 'Invalid USER_ID'], 400);
        }

        // Get user data from request body
        $data = $request->getBody();

        // Validate and update user logic here
        $errors = $this->validateUserData($data, true);

        if ($errors) {
            return Response::json(['errors' => $errors], 400);
        }

        if (!empty($data['user_password'])) {
            $data['user_password'] = password_hash($data['user_password'], PASSWORD_BCRYPT,['cost' => self::PASSWORD_BCRYPT_COST]);
        }

        // Update user logic here
        $data['is_admin'] = (bool) $data['is_admin'] ?? false;

        $userUpdated  = $this->service->updateUser($user_id, $data);
        if (isset($userUpdated['error'])) {
            return Response::json(['error' => $userUpdated['error']], $userUpdated['code'] ?? 404);
        }

        unset($userUpdated['user_password']);
        return Response::json(['message' => 'User updated successfully', 'user' => $userUpdated], 200);
    }

     /**
     * Delete a user
     */
    public function destroy(Request $request, $jwt, array $args): Response {
        // Get token from Authorization header
        $token = $request->getHeader('Authorization');

        if (strpos($token, 'Bearer ') === 0) {
            $token = substr($token, 7);
        }
        if (empty($token)) {
            return Response::json(['error' => 'Authorization token is required'], 400);
        }
        $decoded = $this->service->validateToken($token,  $jwt);
        // var_dump($decoded);
        // Handle service error
        if (isset($decoded['error'])) {
            return Response::json(['error' => $decoded['error']], $decoded['code'] ?? 409);
        }

        $token_user_id = $decoded['user_id'];
        $user_id = (string) ($args['user_id'] ?? '');

        if($token_user_id !== $user_id) {
            return Response::json(['error' => 'Unauthorized access'], 401);
        }

        // Validate user ID
        if ($user_id === '') {
            return Response::json(['error' => 'Invalid USER_ID'], 400);
        }

        // Find user by ID logic here
        $user = $this->service->findUser($user_id);
        if (!$user) {
            return Response::json(['error' => 'User not found or already deleted'], 404);
        }

        // Delete user logic here
        $deleted = $this->service->removeUser($user_id);
        if (isset($deleted['error'])) {
            return Response::json(['error' => $deleted['error']], $deleted['code'] ?? 404);
        }
        // Return success message
        return Response::json(['message' => 'User deleted successfully'], 200);
    }

    /**
     * Login user
     * 
     * @param Request $request The request object
     * @param string $jwtSecret The JWT secret
     * 
     * @return Response The response object
     */
    public function login(Request $request, $jwt): Response {
        // Get login data from request body
        $data = $request->getBody();

        // Validate login data
        $errors = $this->validateLoginData($data);
        if ($errors) {
            return Response::json(['errors' => $errors], 400);
        }

        // Find user by user_id
        $user = $this->service->findUser($data['user_id']);
        if (!$user || !password_verify($data['user_password'], $user['user_password'])) {
            return Response::json(['error' => 'Invalid credentials'], 401);
        }

        // Handle service error
        if (isset($user['error'])) {
            return Response::json(['error' => $user['error']], $user['code'] ?? 409);
        }

        if (!is_object($jwt) || !method_exists($jwt, 'issue')) {
            throw new \Exception('JWT service not initialized');
        }
        // Generate JWT token
        $token = $this->service->generateToken(
            $user['user_id'], 
            (bool) $user['is_admin'],
            $jwt
        );

        $user['token'] = $token;
        $user['is_admin'] = (bool) $user['is_admin'];
        // Update user token
        $userUpdated = $this->service->updateUser($user['user_id'], $user);
        if (isset($userUpdated['error'])) {
            return Response::json(['error' => $userUpdated['error']], $userUpdated['code'] ?? 404);
        }

        unset($user['user_password']);
        return Response::json(['message' => 'User logged in successfully', 'user' => $userUpdated], 200);
    }

    public function logout(Request $request, $jwt): Response {
        // Get token from Authorization header
        $token = $request->getHeader('Authorization');
        
        if (strpos($token, 'Bearer ') === 0) {
            $token = substr($token, 7);
        }
        if (empty($token)) {
            return Response::json(['error' => 'Authorization token is required'], 400);
        }
        $decoded = $this->service->validateToken($token,  $jwt);
        // var_dump($decoded);
        // Handle service error
        if (isset($decoded['error'])) {
            return Response::json(['error' => $decoded['error']], $decoded['code'] ?? 409);
        }

        $user_id = $decoded['user_id'];
        // var_dump($user_id);
        $user = $this->service->findUser($user_id);
        if (!$user) {
            return Response::json(['error' => 'User not found'], 404);
        }
        $user['token'] = null;
        $userUpdated = $this->service->updateUser($user_id, $user);
        if (isset($userUpdated['error'])) {
            return Response::json(['error' => $userUpdated['error']], $userUpdated['code'] ?? 404);
        }
        // Invalidate token logic here (if using a token blacklist or similar)

        // For stateless JWT, just inform the client to delete the token
        return Response::json(['message' => 'User logged out successfully'], 200);
    }

    /**
     * Validate JWT token
     * 
     * @param Request $request The request object
     * 
     * @return Response The response object
     */
    public function validateToken(Request $request, $jwt): Response {
        // Get token from Authorization header
        $token = $request->getHeader('Authorization');
        
        if (strpos($token, 'Bearer ') === 0) {
            $token = substr($token, 7);
        }
        if (empty($token)) {
            return Response::json(['error' => 'Authorization token is required'], 400);
        }
        $decoded = $this->service->validateToken($token,  $jwt);
        // var_dump($decoded);
        if (isset($decoded['error'])) {
            return Response::json(['error' => $decoded['error']], $decoded['code'] ?? 401);
        }
        // Return user details
        return Response::json(['message' => 'Token is valid', 'user' => $decoded], 200);
    }
}