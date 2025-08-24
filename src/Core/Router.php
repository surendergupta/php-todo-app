<?php
// src/Core/Router.php
namespace App\Core;

use App\Core\Request;
use App\Core\Response;

class Router {
    private array $routes = [];
    private string $groupPrefix = '';
    private array $groupMiddlewares = [];

    /**
     * Add a route to the router
     *
     * @param string $method The HTTP method of the route (e.g. GET, POST, PUT, DELETE)
     * @param string $path The path of the route (e.g. '/', '/users', '/users/{id}')
     * @param callable $action The callback to be executed when the route is matched
     * @param array $middlewares An array of middleware classes to be executed before the action
     * @return void
     */
    public function add(string $method, string $path, callable $action, array $middlewares = []): void {
        $fullPath = rtrim($this->groupPrefix . $path, '/');
        if ($fullPath === '') {
            $fullPath = '/';
        }

        // Convert {param} to regex capture groups
        $pattern = preg_replace('#\{([^/]+)\}#', '(?P<$1>[^/]+)', $fullPath);
        $pattern = "#^" . $pattern . "$#";

        // For debugging
        // var_dump("Adding route: $method $path");

        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $fullPath,
            'pattern' => $pattern,
            'action' => $action,
            'middlewares' => array_merge($this->groupMiddlewares, $middlewares),
        ];
    }

    /**
     * Add a route to the router without applying the current group prefix and middlewares
     *
     * @param string $method The HTTP method of the route (e.g. GET, POST, PUT, DELETE)
     * @param string $path The path of the route (e.g. '/', '/users', '/users/{id}')
     * @param callable $action The callback to be executed when the route is matched
     * @param array $middlewares An array of middleware classes to be executed before the action
     * @return void
     */
    public function withoutGroupAdd(string $method, string $path, callable $action, array $middlewares = []): void {
        
        // For debugging
        $this->routes[] = compact(
            'method', 
            'path', 
            'action', 
            'middlewares'
        );
    }

    /**
     * Register a group of routes with a common prefix and shared middlewares
     *
     * @param string $prefix The common prefix for all routes in this group
     * @param callable $callback A callback that registers the routes in this group
     * @param array $middlewares An array of middleware classes to be executed before any actions in this group
     * @return void
     */
    public function group(string $prefix, callable $callback, array $middlewares = []): void {
        // Save current group state
        $previousPrefix = $this->groupPrefix;
        $previousMiddlewares = $this->groupMiddlewares;

        // Update prefix & middlewares for this group
        $this->groupPrefix .= $prefix;
        $this->groupMiddlewares = array_merge($this->groupMiddlewares, $middlewares);
        
        // Run callback to register routes in this group
        $callback($this);

        // Restore previous state after group
        $this->groupPrefix = $previousPrefix;
        $this->groupMiddlewares = $previousMiddlewares;
    }

    /**
     * Returns an array of all routes registered with the router.
     *
     * @return array A list of route data, where each route is an associative array
     *               containing keys 'method', 'path', 'action', and 'middlewares'
     */
    public function getRoutes(): array {
        return $this->routes;
    }

    /**
     * Dispatch a request to a matching route, applying middlewares and returning
     * a response.
     *
     * @param string $method The HTTP method of the request (e.g. GET, POST, PUT, DELETE)
     * @param string $path The path of the request (e.g. '/', '/users', '/users/{id}')
     * @param Request $request The request to be dispatched
     * @return Response The response to be sent back to the client
     */
    public function dispatch(string $method, string $path, Request $request): Response {
        $normalizedPath = rtrim($path, '/');
        if ($normalizedPath === '') {
            $normalizedPath = '/';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] === $method &&
            preg_match($route['pattern'], $normalizedPath, $matches)) {
                
                // Extract named params
                $params = [];
                foreach ($matches as $key => $value) {
                    if (!is_int($key)) {
                        $params[$key] = $value;
                    }
                }

                // Inject params into Request
                $request->setParams($params);
                
                // Build middleware pipeline
                $pipeline = array_reduce(
                    array_reverse($route['middlewares']),
                    fn($next, $middleware) => fn($req) => $middleware->handle($req, $next),
                    fn($req) => $route['action']($req)
                );

                // Execute pipeline
                $result = $pipeline($request);

                if ($result instanceof Response) {
                    $result->send();
                }

                // Auto-wrap arrays/strings into JSON
                if (is_array($result) || is_string($result)) {
                    return Response::json($result);
                }
                return new Response('', 204);                
            }
        }
        // Always return a Response
        $response = Response::json(['error' => 'Route not found'], 404);
        $response->send();
        return $response;
    }
}
