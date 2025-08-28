<?php
namespace App\Core;

class Kernel {
    public static function run(array $middlewares, Request $request, callable $controller): Response {
        $pipeline = array_reduce(
            array_reverse($middlewares),
            fn($next, $middleware) => fn($req) => $middleware->handle($req, $next),
            $controller
        );

        return $pipeline($request);
    }
}