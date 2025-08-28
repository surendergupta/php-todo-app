<?php
// src/Middleware/ValidationMiddleware.php
namespace App\Middleware;

use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;


class ValidationMiddleware implements MiddlewareInterface {
    private array $rules;

    /**
     * Constructor for ValidationMiddleware.
     *
     * @param array $rules An array of required field names to validate in the request body.
     */
    public function __construct(array $rules) {
        $this->rules = $rules;
    }

    
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  callable  $next
     * @return Response
     */
    public function handle(Request $request, callable $next): Response {
        $data = $request->getBody();
        $validator = (new Validator())->validate($data, $this->rules);
        //  die();
        if ($validator->fails()) {
            // show validation errors
            // var_dump($validator->errors());  
            return Response::json(['errors' => $validator->errors()], 400);
        } else {
            // only valid, sanitized fields
            // var_dump($validator->validated()); 
            $request->setBody($validator->validated());
        }

        return $next($request);
    }
}
