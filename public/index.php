<?php
// src/public/index.php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', 1);

use App\Core\Request;
use App\Core\Response;
use Dotenv\Dotenv;


require __DIR__ . '/../vendor/autoload.php';

// Load .env
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Load routes
$router = require __DIR__ . '/../routes/web.php';

$request = new Request();

if ($request->getMethod() === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    exit;
}

try {
    // Dispatch using Request object
    $result = $router->dispatch(
        $request->getMethod(),
        $request->getUri(),
        $request
    );
    
    // If the controller returned a Response object, send it
    if ($result instanceof Response) {
        $result->send();
    }
} catch (\Throwable $e) {
    // Global error handler -> return JSON instead of white screen
    Response::json([
        'error'   => 'Internal Server Error',
        'message' => $e->getMessage()
    ], 500)->send();
}