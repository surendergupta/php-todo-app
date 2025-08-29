<?php
namespace App\Controllers;

use App\Services\AuthService;
use App\Core\Response;
use App\Core\Request;

class AuthController
{
    public function __construct(private AuthService $authService) {}

    private function validateLoginData(array $data): ?array {
        $errors = [];
        if (empty($data['user_id'])) $errors[] = 'User ID is required';
        if (empty($data['user_password'])) $errors[] = 'Password is required';
        return $errors ?: null;
    }

    /**
     * Handle login request.
     */
    public function login(Request $request): Response
    {
        // Get login data from request body
        $data = $request->getBody();

        // Validate login data
        $errors = $this->validateLoginData($data);
        if ($errors) {
            return Response::json(['errors' => $errors], 400);
        }

        // Find user by user_id
        $user = $this->authService->findUser($data['user_id']);
        if (!$user || !password_verify($data['user_password'], $user['user_password'])) {
            return Response::json(['error' => 'Invalid credentials'], 401);
        }

        // Generate JWT token
        $token = $this->authService->generateToken(
            $user['user_id'],
            (bool) $user['is_admin']
        );

        // Handle service error
        if (isset($token['error'])) {
            return Response::json(['error' => $token['error']], $token['code'] ?? 409);
        }

        $user['token'] = $token;
        $user['is_admin'] = (bool) $user['is_admin'];

        // Update user token
        $userUpdated = $this->authService->updateUser($user['user_id'], $user);
        if (isset($userUpdated['error'])) {
            return Response::json(['error' => $userUpdated['error']], $userUpdated['code'] ?? 404);
        }

        unset($user['user_password']);
        return Response::json(['message' => 'User logged in successfully', 'user' => $userUpdated], 200);
    }

    /**
     * Handle logout request.
     */
    public function logout(Request $request): Response
    {
        // Get token from Authorization header
        $token = $request->getBearerToken();
        if (!$token) {
            return Response::json(['error' => 'Authorization token is required'], 400);
        }
        
        $decoded = $this->authService->validateToken($token);
        // var_dump($decoded);
        // Handle service error
        if (isset($decoded['error'])) {
            return Response::json(['error' => $decoded['error']], $decoded['code'] ?? 401);
        }

        // var_dump($user_id);
        $user = $this->authService->findUser($decoded['user_id']);
        if (!$user) {
            return Response::json(['error' => 'User not found'], 404);
        }


        $user['token'] = null;
        $userUpdated = $this->authService->updateUser($decoded['user_id'], $user);
        if (isset($userUpdated['error'])) {
            return Response::json(['error' => $userUpdated['error']], $userUpdated['code'] ?? 404);
        }

        // For stateless JWT, just inform the client to delete the token
        return Response::json(['message' => 'User logged out successfully'], 200);
    }

    /**
     * Validate JWT token request.
     */
    public function validateToken(Request $request): Response
    {
        // Get token from Authorization header
        $token = $request->getBearerToken();
        if (!$token) {
            return Response::json(['error' => 'Authorization token is required'], 400);
        }

        $decoded = $this->authService->validateToken($token);
        // var_dump($decoded);
        if (isset($decoded['error'])) {
            return Response::json(['error' => $decoded['error']], $decoded['code'] ?? 401);
        }
        // Return user details
        return Response::json(['message' => 'Token is valid', 'user' => $decoded], 200);
    }
}
