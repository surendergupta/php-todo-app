<?php
// src/Repository/UserRepository.php
declare(strict_types=1);

namespace App\Repository;


class UserRepository extends BaseRepository 
{
    
    private string $table = 'users';

    /**
     * Fetch all users from the database
     * 
     * @return array|null An array of user data, or null on error
     */
    public function all(): ?array {
        return $this->safeExecute(
            fn() => $this->qb()->reset()
                ->table($this->table)
                ->select(['user_id', 'email_address', 'first_name', 'last_name', 'is_admin'])
                ->whereNull('deleted_at')
                ->orderBy('id', 'DESC')
                ->get()
        );
    }

    /**
     * Find a user by their user ID
     * 
     * @param string $user_id The user ID to search for
     * 
     * @return array|null The user data if found, otherwise null
     */
    public function find(string $user_id): ?array {
        return $this->safeExecute(
            fn() => $this->qb()->reset()
                ->table($this->table)
                ->where('user_id', '=', $user_id)
                ->whereNull('deleted_at')
                ->first()
        );
    }

    /**
     * Find a user by their email address
     * 
     * @param string $email_address  The email address to search for
     * 
     * @return array|null The user data if found, otherwise null
     */
    public function findByEmail(string $email_address): ?array {
        return $this->safeExecute(
            fn() => $this->qb()->reset()
                ->table($this->table)
                ->where('email_address', '=', $email_address)
                ->whereNull('deleted_at')
                ->first()
        );
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
     * @return int|null The ID of the newly created user, or null on failure
     */
    public function create(
        string $user_id, 
        string $email_address, 
        string $user_password, 
        string $first_name, 
        string $last_name, 
        bool $is_admin
    ): ?int {
        return $this->safeExecute(
            fn() => $this->qb()->reset()
                ->table($this->table)
                ->insert([
                    'user_id' => $user_id,
                    'email_address' => $email_address,
                    'user_password' => $user_password,
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'is_admin' => $is_admin
                ])
                ? (int) $this->qb()->getLastInsertId() 
                : null
        );
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
    public function update(string $user_id, array $data): bool {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->safeExecute(
            fn() => $this->qb()->reset()
                ->table($this->table)
                ->where('user_id', '=', $user_id)
                ->whereNull('deleted_at')
                ->update($data)
        );
    }

    /**
     * Delete a user by ID
     * 
     * @param string $user_id The user ID
     * 
     * @return bool Whether the deletion was successful
     */
    public function delete(string $user_id): bool {
        return $this->safeExecute(
            fn() => $this->qb()->reset()
                ->table($this->table)
                ->where('user_id', '=', $user_id)
                ->delete()
        );
    }

    /**
     * Soft delete a user by ID
     * 
     * @return bool Whether the soft deletion was successful
     */
    public function softDelete(string $user_id): bool {
        return $this->safeExecute(
            fn() => $this->qb()->reset()
                ->table($this->table)
                ->where('user_id', '=', $user_id)
                ->softDelete()
        );
    }

    public function getTokenFromDB(?string $user_id): ?string {
        if (!$user_id) {
            return null;
        }

        return $this->safeExecute(
            fn() => $this->qb()->reset()
                ->table($this->table)
                ->select(['token'])
                ->where('user_id', '=', $user_id)
                ->first()['token']
        );
    }
}