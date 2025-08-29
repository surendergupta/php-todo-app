<?php
// routes/api.php
declare(strict_types=1);

use App\Core\Router;
use App\Security\Jwt;
use App\Middleware\{
    CorsMiddleware,
    LoggingMiddleware,
    AuthMiddleware,
    ValidationMiddleware,
    RateLimiterMiddleware
};
use App\Repository\{TodoRepository, UserRepository};
use App\Services\{TodoService, UserService, AuthService};
use App\Controllers\{TodoController, UserController, AuthController};

$router = new Router();

$jwtSecret = $_ENV['JWT_SECRET'] ?? 'fallback_secret';
if ($jwtSecret === 'fallback_secret' && ($_ENV['APP_ENV'] ?? 'prod') === 'prod') {
    // error_log('Warning: Using fallback JWT secret. Set JWT_SECRET in environment for better security.');
    throw new \RuntimeException('Missing JWT_SECRET in production.');
}

$jwt = new Jwt($jwtSecret);

// Initialize auth repositories and services
$authRepository = new UserRepository();
$authService    = new AuthService($authRepository, $jwt);
$authController = new AuthController($authService);

// Initialize todo repositories and services
$todoRepository = new TodoRepository();
$todoService = new TodoService($todoRepository);
$todoController = new TodoController($todoService);

// Initialize user repositories and services
$userRepository = new UserRepository();
$userService    = new UserService($userRepository);
$userController = new UserController($userService);



$router->group('/api/v1', function($router) use ($todoController, $userController, $jwt, $userRepository, $authController) {
    $router->group(
        '/todos', 
        function($router) use ($jwt, $todoController)  {
            // Define routes within the /todos group
            // List all todos
            $router->add('GET', '/', fn() => $todoController->index());
            // Show single todo
            $router->add('GET', '/{id}', fn($req) => $todoController->show($req->getParams()));
            // Create todo
            $router->add('POST', '/', fn($req) => $todoController->store($req), [ 
                new ValidationMiddleware([
                    'title' => 'required|string|min:3|max:100', 
                    'user_id' => 'required|string|min:3|max:25'
                ])
            ]);
            // Update todo
            $router->add('PUT', '/{id}', fn($req) => $todoController->update($req, (int) $req->getParams()['id']), [ 
                new ValidationMiddleware([
                    'title' => 'required|string|min:3|max:100', 
                    'user_id' => 'required|string|min:3|max:25'
                ])
            ]);
            // Delete todo
            $router->add('DELETE', '/{id}', fn($req) => $todoController->destroy($req, $jwt, $req->getParams()['id']));        
        },
        [
            new CorsMiddleware(), 
            new LoggingMiddleware(),
            new RateLimiterMiddleware(),
            new AuthMiddleware($jwt, $userRepository)
        ]
    );

    $router->group(
        '/users', 
        function($router) use ($jwt, $userRepository, $userController) 
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
            $router->add('PUT', '/{user_id}', fn($req) => $userController->update($req, $req->getParams()), [ new AuthMiddleware($jwt, $userRepository) ]);
            // Delete user
            $router->add('DELETE', '/{user_id}', fn($req) => $userController->destroy($req, $jwt,$req->getParams()), [ new AuthMiddleware($jwt, $userRepository) ]);
        },
        [
            new CorsMiddleware(), 
            new LoggingMiddleware(), 
            new RateLimiterMiddleware()
        ]
    );

    $router->group(
        '/auth', 
        function($router) use ($jwt, $userRepository, $authController) 
        {
            // Define routes within the /auth group
            // Login user
            $router->add('POST', '/login', fn($req) => $authController->login($req), [ 
                new ValidationMiddleware([
                    'user_id' => 'required|string|min:3|max:25', 
                    'user_password' => 'required|string|min:8|max:100' 
                ])
            ]);
            // Logout user
            $router->add('POST', '/logout', fn($req) => $authController->logout($req), [ 
                new AuthMiddleware($jwt, $userRepository) 
            ]);
            // Validate token
            $router->add('GET', '/validate', fn($req) => $authController->validateToken($req));
        },
        [
            new CorsMiddleware(), 
            new LoggingMiddleware(), 
            new RateLimiterMiddleware()
        ]
    );
});

return $router;