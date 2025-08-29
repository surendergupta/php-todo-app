<?php
// src/Middleware/AuthMiddleware.php
namespace App\Middleware;

use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Security\Jwt;
use App\Repository\UserRepository;

class AuthMiddleware implements MiddlewareInterface {
    public function __construct(private Jwt $jwt, private UserRepository $userRepository) {}

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
            // verify token
            $claims = $this->jwt->verify($token);
            
            // token in table or token value is null
            // check token against DB
            $userId = $claims['user_id'] ?? null;

            // get token from DB via repository
            $dbToken = $this->userRepository->getTokenFromDB($userId);

            if (!$dbToken || $dbToken !== $token) {
                return Response::json(['error' => 'Token revoked or invalid'], 401);
            }

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