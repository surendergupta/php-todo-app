<?php
// src/Repository/UserRepository.php
declare(strict_types=1);

namespace App\Repository;

use PDO;
use PDOException;
use RuntimeException;

class UserRepository {
    public function __construct(private PDO $db) {}

    /**
     * Fetch all users from the database
     * 
     * @return array An array of user data, ordered by ID in descending order
     */
    public function all(): array {
        $stmt = $this->db->query(
            "SELECT id, user_id, email_address, user_password, first_name, last_name, is_admin, token, created_at, updated_at 
            FROM users 
            ORDER BY id DESC"
        );
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($users as &$user) {
            $user['is_admin'] = (bool) $user['is_admin'];
        }

        return $users;        
    }

    /**
     * Find a user by their user ID
     * 
     * @param string $user_id The user ID to search for
     * @return array|false The user data if found, otherwise false
     */
    public function find(string $user_id): array|false {
        $stmt = $this->db->prepare(
            "SELECT id, user_id, email_address, user_password, first_name, last_name, is_admin, token, created_at, updated_at 
            FROM users 
            WHERE user_id = :user_id"
        );
        $stmt->execute(['user_id' => $user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $user['is_admin'] = (bool) $user['is_admin'];
        }

        return $user ?: false;
    }

    /**
     * Find a user by their email address
     * 
     * @param string $email_address  The email address to search for
     * 
     * @return array|false  The user data if found, false otherwise
     */
    public function findByEmail(string $email_address): array|false {
        $stmt = $this->db->prepare(
            "SELECT id, user_id, email_address, user_password, first_name, last_name, is_admin, token, created_at, updated_at 
            FROM users 
            WHERE email_address = :email_address"
        );
        $stmt->execute(['email_address' => $email_address]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $user['is_admin'] = (bool) $user['is_admin'];
        }

        return $user ?: false;
    }

    /**
     * Create a new user
     * 
     * @param string $user_id        The user ID
     * @param string $email_address  The email address
     * @param string $user_password  The user password
     * @param string $first_name     The first name
     * @param string $last_name      The last name
     * @param bool $is_admin         Whether the user is an admin
     * 
     * @return int The ID of the newly created user
     */
    public function create(
        string $user_id, 
        string $email_address, 
        string $user_password, 
        string $first_name, 
        string $last_name, 
        bool $is_admin
    ): int {
        $stmt = $this->db->prepare(
            "INSERT INTO users 
                (user_id, email_address, user_password, first_name, last_name, is_admin) 
                VALUES (:user_id, :email_address, :user_password, :first_name, :last_name, :is_admin)
            ");
        if(!$stmt->execute([
            'user_id' => $user_id,
            'email_address' => $email_address,
            'user_password' => $user_password,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'is_admin' => $is_admin
        ])) {
            throw new RuntimeException("Failed to create user");
        }
        return (int) $this->db->lastInsertId();
    }

    /**
     * Update an existing user
     * 
     * @param string $user_id        The user ID
     * @param string $email_address  The email address
     * @param string $user_password  The user password
     * @param string $first_name     The first name
     * @param string $last_name      The last name
     * @param bool $is_admin         Whether the user is an admin
     * 
     * @return bool Whether the update was successful
     */
    public function update(
        string $user_id, 
        string $email_address, 
        string $user_password, 
        string $first_name, 
        string $last_name, 
        bool $is_admin, 
        ?string $token
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE users 
                SET email_address = :email_address, 
                    user_password = :user_password, 
                    first_name = :first_name, 
                    last_name = :last_name, 
                    is_admin = :is_admin, 
                    token = :token, 
                    updated_at = CURRENT_TIMESTAMP
                WHERE user_id = :user_id
            ");
        return $stmt->execute([
            'user_id' => $user_id,
            'email_address' => $email_address,
            'user_password' => $user_password,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'is_admin' => $is_admin,
            'token' => $token
        ]);
    }

    /**
     * Delete a user by ID
     * 
     * @param string $user_id The user ID
     * 
     * @return bool Whether the deletion was successful
     */
    public function delete(string $user_id): bool {
        $stmt = $this->db->prepare("DELETE FROM users WHERE user_id = :user_id");
        return $stmt->execute(['user_id' => $user_id]);
    }
}