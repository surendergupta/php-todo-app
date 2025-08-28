<?php
// src/public/index.php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';

use App\Core\Response;
use App\Core\Request;
use App\Exceptions\BaseException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\RouteNotFoundException;
use App\Exceptions\UnauthorizedException;
use App\Exceptions\ValidationException;
use Dotenv\Dotenv;

// Load .env
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
$appDebug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);
if ($appDebug) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}


// Load routes
$router = require __DIR__ . '/../routes/api.php';

$request = new Request();

if ($request->getMethod() === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    header('Referrer-Policy: no-referrer');
    header('Permissions-Policy: geolocation=(), microphone=()');
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
    // if ($result instanceof Response) {
    //     $result->send();
    // } elseif (is_array($result)) {
    //     Response::json($result)->send();
    // } elseif (is_string($result)) {
    //     (new Response($result))->send();
    // }

    $response = match (true) {
        $result instanceof Response => $result,
        is_array($result)           => Response::json($result),
        is_string($result)          => new Response($result),
        default                     => Response::json(['error' => 'Invalid response type'], 500),
    };
    $response->send();

} catch (RouteNotFoundException $e) {
    Response::json(['error' => 'Not Found'], 404)->send();
} catch (UnauthorizedException $e) {
    Response::json(['error' => 'Unauthorized'], 401)->send();
} catch (ForbiddenException $e) {
    Response::json(['error' => 'Forbidden'], 403)->send();
} catch (ValidationException $e) {
    Response::json(['error' => 'Validation Error', 'errors' => $e->getErrors()], 422)->send();
} catch (BaseException $e) {
    Response::json([
        'error'   => $e->getMessage(),
        'context' => $e->getContext()
    ], $e->getCode())->send();

} catch (\Throwable $e) {
    // Global error handler -> return JSON instead of white screen
    error_log(json_encode([
        'time'    => date('c'),
        'error'   => $e->getMessage(),
        'file'    => $e->getFile(),
        'line'    => $e->getLine(),
        'trace'   => $e->getTraceAsString(),
    ]));
    Response::json([
        'error'   => 'Internal Server Error',
        'message' => $e->getMessage()
    ], 500)->send();
}