<?php
// routes/web.php
use App\Core\Router;
use App\Database;
use App\Database\QueryBuilder;
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
if ($jwtSecret === 'fallback_secret' && ($_ENV['APP_ENV'] ?? 'prod') === 'prod') {
    error_log('Warning: Using fallback JWT secret. Set JWT_SECRET in environment for better security.');
    throw new \RuntimeException('Missing JWT_SECRET in environment.');
}

$jwt = new Jwt($jwtSecret);
$todoController = new TodoController(
    new TodoService(
        new TodoRepository(new QueryBuilder(Database::getConnection()))
    )
);

$userController = new UserController(
    new UserService(
        new UserRepository(new QueryBuilder(Database::getConnection()))
    )
);
$router->group('/api/v1', function($router) use ($todoController, $userController, $jwt) {
    $router->group(
        '/todos', 
        function($router) use ($jwt, $todoController)  {
            // Define routes within the /todos group
            // List all todos
            $router->add('GET', '/', fn() => $todoController->index());
            // Show single todo
            $router->add('GET', '/{id}', fn($req) => $todoController->show($req->getParams()));
            // Create todo
            $router->add('POST', '/', fn($req) => $todoController->store($req), [ new ValidationMiddleware(['title' => 'required|string|min:3|max:100', 'user_id' => 'required|string|min:3|max:25'])]);
            // Update todo
            $router->add('PUT', '/{id}', fn($req) => $todoController->update($req, $req->getParams()['id']), [ new ValidationMiddleware(['title' => 'required|string|min:3|max:100', 'user_id' => 'required|string|min:3|max:25'])]);
            // Delete todo
            $router->add('DELETE', '/{id}', fn($req) => $todoController->destroy($req, $jwt, $req->getParams()['id']));        
        },
        [
            new CorsMiddleware(), 
            new LoggingMiddleware(),
            new RateLimiterMiddleware(),
            new AuthMiddleware($jwt)
        ]
    );

    $router->group(
        '/users', 
        function($router) use ($jwt, $userController) 
        {
            // Define routes within the /users group
            // List all users
            $router->add('GET', '/', fn($req) => $userController->index($req));
            // Show single user
            $router->add('GET', '/{user_id}', fn($req) => $userController->show($req, $req->getParams()));
            // Register new user
            $router->add('POST', '/register', fn($req) => $userController->store($req), [ new ValidationMiddleware([
                'user_id' => 'required|string|min:3|max:25',
                'email_address' => 'required|string|email|max:100',
                'user_password' => 'required|string|min:8|max:100',
                'first_name' => 'required|string|min:3|max:50',
                'last_name' => 'required|string|min:3|max:50',
                'is_admin' => 'sometimes|boolean'              
                ])]);
            // Update user details
            $router->add('PUT', '/{user_id}', fn($req) => $userController->update($req, $req->getParams()), [ new AuthMiddleware($jwt) ]);
            // Delete user
            $router->add('DELETE', '/{user_id}', fn($req) => $userController->destroy($req, $jwt,$req->getParams()), [ new AuthMiddleware($jwt) ]);
        },
        [
            new CorsMiddleware(), 
            new LoggingMiddleware(), 
            new RateLimiterMiddleware()
        ]
    );

    $router->group(
        '/auth', 
        function($router) use ($jwt, $userController) 
        {
            // Define routes within the /auth group
            // Login user
            $router->add('POST', '/login', fn($req) => $userController->login($req, $jwt), [ new ValidationMiddleware(['user_id' => 'required|string|min:3|max:25', 'user_password' => 'required|string|min:8|max:100' ])]);
            // Logout user
            $router->add('POST', '/logout', fn($req) => $userController->logout($req, $jwt), [ new AuthMiddleware($jwt) ]);
            // Validate token
            $router->add('GET', '/validate', fn($req) => $userController->validateToken($req, $jwt));
        },
        [
            new CorsMiddleware(), 
            new LoggingMiddleware(), 
            new RateLimiterMiddleware()
        ]
    );
});

return $router;