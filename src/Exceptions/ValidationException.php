<?php
declare(strict_types=1);

namespace App\Exceptions;

class ValidationException extends BaseException
{
    private array $errors;

    public function __construct(array $errors, string $message = "Validation error", int $code = 422)
    {
        $this->errors = $errors;
        parent::__construct($message, $code, ['errors' => $errors]);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
