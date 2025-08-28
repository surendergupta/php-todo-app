<?php
// src/Middleware/LoggingMiddleware.php
namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\MiddlewareInterface;

class LoggingMiddleware implements MiddlewareInterface {
    /**
     * Logs the request and response to the log file.
     *
     * @param  Request  $request
     * @param  callable $next
     * @return Response
     */
    public function handle(Request $request, callable $next): Response {
        $start = microtime(true);
        $response = $next($request);
        $duration = round((microtime(true) - $start) * 1000, 2);

        $logLine = sprintf("[%s] %s %s (%dms)\n",
            date('Y-m-d H:i:s'),
            $request->getMethod(),
            $request->getUri(),
            $duration
        );
        $filepath = __DIR__ . '/../../logs/app.log';
        if (!file_exists(dirname($filepath))) {
            mkdir(dirname($filepath), 0777, true);
        }

        file_put_contents(
            $filepath,
             $logLine,
            FILE_APPEND | LOCK_EX            
        );

        error_log("[LOG] {$request->getMethod()} {$request->getUri()}");
        return $response;
    }
}
