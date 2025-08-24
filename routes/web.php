<?php
// routes/web.php
use App\Core\Router;
use App\Database;
use App\Middleware\RateLimiterMiddleware;
use App\Repository\TodoRepository;
use App\Services\TodoService;
use App\Controllers\TodoController;
use App\Middleware\{CorsMiddleware, LoggingMiddleware, AuthMiddleware, ValidationMiddleware};
use App\Security\Jwt;

use App\Repository\UserRepository;
use App\Services\UserService;
use App\Controllers\UserController;

$router = new Router();

$jwtSecret = $_ENV['JWT_SECRET'] ?? 'fallback_secret';
if ($jwtSecret === 'fallback_secret') {
    error_log('Warning: Using fallback JWT secret. Set JWT_SECRET in environment for better security.');
}

$jwt = new Jwt($jwtSecret);

$todoController = new TodoController(
    new TodoService(
        new TodoRepository(Database::getConnection())
    )
);

$userController = new UserController(
    new UserService(
        new UserRepository(Database::getConnection())
    )
);

$router->group(
    '/todos', 
    function($router) 
    use ($todoController) 
    {
        // Define routes within the /todos group
        $router->add(
            'GET',
            '/', 
            fn() => $todoController->index(),
            [
                new CorsMiddleware(), 
                new LoggingMiddleware(), 
                new RateLimiterMiddleware()
            ]
        );

        $router->add(
            'GET',
            '/{id}', 
            fn($req) => $todoController->show($req->getParams()),
            [
                new CorsMiddleware(), 
                new LoggingMiddleware(), 
                new RateLimiterMiddleware()
            ]
        );

        $router->add(
            'POST',
            '/', 
            fn($req) => $todoController->store($req),
            [
                new CorsMiddleware(), 
                new LoggingMiddleware(),
                new RateLimiterMiddleware(), 
                new ValidationMiddleware(['title'])
            ]
        );

        $router->add(
            'PUT', 
            '/{id}', 
            fn($req) => $todoController->update($req, $req->getParams()['id']),
            [
                new CorsMiddleware(), 
                new LoggingMiddleware(),
                new RateLimiterMiddleware(), 
                new ValidationMiddleware(['title'])
            ]
        );

        $router->add(
            'DELETE', 
            '/', 
            fn($req) => $todoController->destroy($req->getBody()),
            [
                new CorsMiddleware(), 
                new LoggingMiddleware(),
                new RateLimiterMiddleware()
            ]
        );
        
    }
);

$router->group(
    '/users', 
    function($router) use ($userController) 
    {
        // Define routes within the /users group

        // List all users
        $router->add(
            'GET',
            '/', 
            fn($req) => $userController->index($req),
            [
                new CorsMiddleware(), 
                new LoggingMiddleware(), 
                new RateLimiterMiddleware()
            ]
        );

        // Show single user
        $router->add(
            'GET',
            '/{user_id}', 
            fn($req) => $userController->show($req, $req->getParams()),
            [
                new CorsMiddleware(), 
                new LoggingMiddleware(), 
                new RateLimiterMiddleware()
            ]
        );

        // Register new user
        $router->add(
            'POST',
            '/register', 
            fn($req) => $userController->store($req),
            [
                new CorsMiddleware(), 
                new LoggingMiddleware(), 
                new RateLimiterMiddleware(),
                new ValidationMiddleware(['user_id','email_address','user_password','first_name','last_name','is_admin'])
            ]
        );

        // Update user details
        $router->add(
            'PUT', 
            '/{user_id}', 
            fn($req) => $userController->update($req, $req->getParams()),
            [
                new CorsMiddleware(), 
                new LoggingMiddleware(), 
                new RateLimiterMiddleware(),                
            ]
        );

        // Delete user
        $router->add(
            'DELETE', 
            '/{user_id}', 
            fn($req) => $userController->destroy($req->getParams()),
            [
                new CorsMiddleware(), 
                new LoggingMiddleware(), 
                new RateLimiterMiddleware()
            ]
        );
    }
);

$router->group(
    '/auth', 
    function($router) use ($jwt, $jwtSecret, $userController) 
    {
        // Define routes within the /auth group

        // Login user
        $router->add(
            'POST',
            '/login', 
            fn($req) => $userController->login($req, $jwt),
            [
                new CorsMiddleware(), 
                new LoggingMiddleware(), 
                new RateLimiterMiddleware(),
                new ValidationMiddleware(['user_id','user_password'])
            ]
        );

        // Validate token
        $router->add(
            'GET',
            '/validate', 
            fn($req) => $userController->validateToken($req, $jwt),
            [
                new CorsMiddleware(), 
                new LoggingMiddleware(), 
                new RateLimiterMiddleware(),
                // new AuthMiddleware($jwt)
            ]
        );
    }
);

return $router;