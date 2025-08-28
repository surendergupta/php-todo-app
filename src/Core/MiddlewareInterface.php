<?php
// src/Core/Middleware.php
namespace App\Core;

interface MiddlewareInterface {
    public function handle(Request $request, callable $next): Response;
}