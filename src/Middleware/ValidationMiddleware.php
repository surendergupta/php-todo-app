<?php
// src/Middleware/ValidationMiddleware.php
namespace App\Middleware;

use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;


class ValidationMiddleware implements Middleware {
    private array $requiredFields;

    /** @param array<string,array<string,int|bool>> $rules */

    
    /**
     * Constructor to initialize the required fields to be validated.
     *
     * @param array<string> $requiredFields The fields that must be present in the request body.
     */
    public function __construct(array $requiredFields) {
        $this->requiredFields = $requiredFields;
    }

    /**
     * Validate the request body against the required fields.
     *
     * @param Request $request The request to validate.
     * @param callable $next The next middleware to call if the validation passes.
     *
     * @return Response The response if the validation failed, otherwise the response from the next middleware.
     */
    public function handle(Request $request, callable $next): Response {
        $data = $request->getBody();
        $errors = [];

        foreach ($this->requiredFields as $field) {
            // special case: is_admin can be false, so just check existence
            if ($field === 'is_admin') {
                if (!array_key_exists('is_admin', $data)) {
                    $errors[] = "Missing field: is_admin";
                }
                continue;
            }

            if (!isset($data[$field]) || trim((string)$data[$field]) === '') {
                $errors[] = "Missing or empty field: $field";
            }
        }
        
        if (!empty($errors)) {
            return Response::json(['errors' => $errors], 422);
        }
        return $next($request);
    }
}
