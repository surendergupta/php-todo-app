<?php
// src/Database.php
namespace App;

use PDO;

class Database {
    /**
     * Get a PDO connection to the database.
     *
     * @return PDO
     */
    
    public static function getConnection(): PDO {
        $db_host = $_ENV['DB_HOST'] ?? 'localhost';
        $db_name = $_ENV['DB_NAME'] ?? 'todo_app';
        $db_user = $_ENV['DB_USER'] ?? 'root';
        $db_password = $_ENV['DB_PASS'] ?? '';
        // $db_port = $_ENV['DB_PORT'] ?? 3306;

        $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";

        return new PDO($dsn, $db_user, $db_password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
}
