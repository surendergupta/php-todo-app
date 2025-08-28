<?php
declare(strict_types=1);

namespace App\Exceptions;

class RouteNotFoundException extends BaseException
{
    public function __construct(string $message = "Route not found", int $code = 404, array $context = [])
    {
        parent::__construct($message, $code, $context);
    }
}
