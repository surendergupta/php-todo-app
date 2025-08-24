<?php
// src/Middleware/AuthMiddleware.php
namespace App\Middleware;

use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use App\Security\Jwt;

class AuthMiddleware implements Middleware {
    public function __construct(private Jwt $jwt) {}

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  callable  $next
     * @return Response
     */
    public function handle(Request $request, callable $next): Response {
        $token = $request->getBearerToken();
        if (!$token) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }

        try {
            $claims = $this->jwt->verify($token);

            // attach claims to request (immutable way)
            $request->setAttribute('auth', $claims);

            // attach auth to request (simple way)            
            // $request->auth = $claims; // dynamic property for quick use
        } catch (\Throwable $e) {
            return Response::json(['error' => 'Invalid or expired token'], 401);
        }

        return $next($request);
    }
}
