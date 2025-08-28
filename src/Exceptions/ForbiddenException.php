<?php
declare(strict_types=1);

namespace App\Exceptions;

class ForbiddenException extends BaseException
{
    public function __construct(string $message = "Forbidden", int $code = 403)
    {
        parent::__construct($message, $code);
    }
}
