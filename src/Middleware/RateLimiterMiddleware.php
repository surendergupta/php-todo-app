<?php
// src/Middleware/RateLimiterMiddleware.php
namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

class RateLimiterMiddleware {
    private int $limit = 10; // 60 requests
    private int $window = 60; // per 60 seconds

    /**
     * Configure the rate limiter middleware
     *
     * @param int $limit     number of requests allowed in the time window
     * @param int $window    time window in seconds
     */
    public function __construct(int $limit = 300, int $window = 60) {
        $this->limit = $limit;
        $this->window = $window;
    }

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  callable  $next
     * @return Response
     */
    public function handle(Request $request, callable $next): Response {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = md5("rate_limit_" . $ip);
        $cacheFile = sys_get_temp_dir() . "/$key.json";

        $data = file_exists($cacheFile) ? 
            json_decode(file_get_contents($cacheFile), true) 
            : ['count'=>0, 'time'=>time()];

        if (time() - $data['time'] > $this->window) {
            $data = ['count'=>0, 'time'=>time()];
        }

        if ($data['count'] >= $this->limit) {
            return Response::json(['error'=>'Too many requests'], 429);
        }

        $data['count']++;
        file_put_contents($cacheFile, json_encode($data));

        return $next($request);
    }
}
