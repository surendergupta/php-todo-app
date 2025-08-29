<?php
// src/Services/UserService.php
declare(strict_types=1);
namespace App\Services;

use App\Repository\UserRepository;

class UserService
{
    public function __construct(private UserRepository $repo) {}

    /**
     * List all users from the database
     * 
     * @return array An array of user data, ordered by ID in descending order
     */
    public function listUsers(): array {
        return $this->repo->all();
    }
    /**
     * Find a user by their user ID
     * 
     * @param string $user_id The user ID to search for
     * 
     * @return array|false The user data if found, otherwise false
     */

    public function findUser(string $user_id): ?array {
        return $this->repo->find($user_id);
    }

    /**
     * Find a user by their email address
     * 
     * @param string $email_address  The email address to search for
     * 
     * @return array|false  The user data if found, false otherwise
     */
    public function findUserByEmail(string $email_address): array|false {
        return $this->repo->findByEmail($email_address);
    }

    /**
     * Add a new user to the database
     * 
     * @param string $user_id        The user ID
     * @param string $email_address  The email address
     * @param string $user_password  The user password
     * @param string $first_name     The first name
     * @param string $last_name      The last name
     * @param bool $is_admin         Whether the user is an admin
     * 
     * @return array The newly created user data, or an error response if the
     *               email address already exists
     */
    public function addUser(
        string $user_id, 
        string $email_address, 
        string $user_password, 
        string $first_name, 
        string $last_name, 
        bool $is_admin
    ): array {
        if ($this->repo->findByEmail($email_address)) {
            return ['error' => 'Email already exists', 'code' => 409];
        }
        $this->repo->create(
            $user_id, 
            $email_address, 
            $user_password, 
            $first_name, 
            $last_name, 
            $is_admin
        );
        return $this->repo->find($user_id);
    }

    /**
     * Update an existing user.
     * 
     * @param string $user_id The user ID to update
     * @param array $data An associative array of user data to update.
     *                      The following keys are required: email_address,
     *                      user_password, first_name, last_name.
     *                      The following key is optional: is_admin.
     * 
     * @return array An associative array with a 'success' key if the update
     *               was successful, otherwise an 'error' key and a 'code' key
     *               with the HTTP status code.
     */
    public function updateUser(string $user_id, array $data): array {
        // Check if the user exists
        $user = $this->repo->find($user_id);
        if (!$user) {
            return ['error' => 'User not found', 'code' => 404];
        }
        
        $allowed = ['first_name', 'last_name', 'token', 'is_admin'];
        $updateData = array_intersect_key($data, array_flip($allowed));
        // Update user details
        $updated = $this->repo->update($user_id, $updateData);
        
        if (!$updated) {
            return ['error' => 'Update failed', 'code' => 500];
        }
        return $this->repo->find($user_id);
    }

    /**
     * Delete a user by ID.
     * 
     * @param string $user_id The user ID to delete.
     * 
     * @return array An associative array with a 'success' key if the deletion
     *               was successful, otherwise an 'error' key and a 'code' key
     *               with the HTTP status code.
     */
    public function removeUser(string $user_id): array {
        $user = $this->repo->find($user_id);
        if (!$user) {
            return ['error' => 'User not found', 'code' => 404];
        }

        $deleted = $this->repo->softDelete($user_id);              

        return $deleted ? ['success' => true] : ['error' => 'User not found', 'code' => 404];
    }

    public function generateToken(
        string $user_id, 
        bool $is_admin,
        $jwt
    ): string {
        $claims  = [
            'user_id' => $user_id,
            'role' => $is_admin ? 'admin' : 'user'
        ];        
        $token = $jwt->issue($claims);
        return $token;
    }

    public function validateToken(string $token, $jwt): array {
        try {
            $claims = $jwt->verify($token);
            return $claims;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage(), 'code' => 401];
        }
    }
}
