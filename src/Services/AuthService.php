<?php
namespace App\Services;

use App\Repository\UserRepository;
use App\Security\Jwt;

class AuthService
{
    public function __construct(
        private UserRepository $userRepository,
        private Jwt $jwt
    ) {}
    
    /**
     * Validate token and return payload.
     */
    public function validateToken(string $token): array
    {
        try {
            $claims = $this->jwt->verify($token);
            return $claims;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage(), 'code' => 401];
        }
    }

    public function generateToken(
        string $user_id, 
        bool $is_admin
    ): string {
        $claims  = [
            'user_id' => $user_id,
            'role' => $is_admin ? 'admin' : 'user'
        ];        
        return $this->jwt->issue($claims);
    }

    public function findUser(string $user_id): ?array {
        return $this->userRepository->find($user_id);
    }

    public function updateUser(string $user_id, array $data): array {
        // Check if the user exists
        $user = $this->userRepository->find($user_id);
        if (!$user) {
            return ['error' => 'User not found', 'code' => 404];
        }
        
        $allowed = ['first_name', 'last_name', 'token', 'is_admin'];
        $updateData = array_intersect_key($data, array_flip($allowed));
        // Update user details
        $updated = $this->userRepository->update($user_id, $updateData);
        
        if (!$updated) {
            return ['error' => 'Update failed', 'code' => 500];
        }
        return $this->userRepository->find($user_id);
    }
}
