<?php
// src/Core/Response.php
namespace App\Core;

class Response {
    
    /**
     * Create a new Response instance
     *
     * @param mixed $data the data to be returned in the response
     * @param int $status the HTTP status code (default is 200)
     * @param array $headers the HTTP headers to send in the response (default is ['Content-Type' => 'application/json'])
     */
    public function __construct(
        private mixed $data,
        private int $status = 200,
        private array $headers = ['Content-Type' => 'application/json']
    ) {
        $this->data = $data;
        $this->status = $status;
        $this->headers = $headers;
    }

    /**
     * Create a new Response instance that returns a JSON response
     *
     * @param array $data the data to be returned in the response
     * @param int $status the HTTP status code (default is 200)
     * @return static
     */
    public static function json(array $data, int $status = 200): self {
        return new self($data, $status, ['Content-Type' => 'application/json']);
    }
    
    /**
     * Send the response
     *
     * This method sends the response and exits the script. It is
     * intended to be used as the last step in a controller method.
     */
    public function send(): void {
        http_response_code($this->status);
        foreach ($this->headers as $key => $value) {
            header("$key: $value");
        }
        echo json_encode($this->data);
        if (php_sapi_name() !== 'cli') {
            exit;
        }
    }
}
