<?php
// src/Middleware/CorsMiddleware.php
namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

class CorsMiddleware {
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  callable  $next
     * @return Response
     */
    public function handle(Request $request, callable $next): Response {
        $response = $next($request);
        // Allow specific origins instead of *
        header("Access-Control-Allow-Origin: http://localhost:8000");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        header("Access-Control-Allow-Credentials: true");

        if ($request->getMethod() === "OPTIONS") {
            return Response::json([], 204);
        }
        
        return $response;
    }
}
