<?php
// src/Core/Request.php
namespace App\Core;

class Request
{
    protected array $query;
    protected array $body;
    protected array $headers;
    protected string $method;
    protected string $uri;
    protected string $ip;

    /** Attributes for middleware / controller */
    protected array $attributes = [];

    protected array $params = [];

    /**
     * Constructor to initialize the Request object.
     *
     * This method initializes the following properties from the superglobal variables:
     * - method
     * - uri
     * - query
     * - body
     * - headers
     * - ip
     *
     * @return void
     */
    public function __construct()
    {
        $this->method  = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->uri     = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        $this->query   = $_GET ?? [];
        $this->body    = $this->parseBody();
        $this->headers = $this->parseHeaders();
        $this->ip      = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    /**
     * Set a value in the attributes array.
     * 
     * This method allows you to store arbitrary data in the request object,
     * which can be accessed later by middleware or controller.
     *
     * @param string $key   The key of the attribute to set.
     * @param mixed  $value The value of the attribute to set.
     * @return void
     */
    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Get a value from the attributes array.
     * 
     * This method allows you to access the attributes set by middleware or controller.
     * If the attribute does not exist, the default value will be returned.
     *
     * @param string $key   The key of the attribute to get.
     * @param mixed  $default   The default value to return if the attribute does not exist.
     * @return mixed The value of the attribute or the default value.
     */
    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * Parse the request body content.
     *
     * This method checks if the request is a JSON request and decodes the
     * content. If the request is not a JSON request and the method is one of
     * the following (POST, PUT, PATCH), it will parse the form data and return
     * it as an array. Otherwise, it will return an empty array.
     *
     * @return array The parsed request body content.
     */
    protected function parseBody(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (stripos($contentType, 'application/json') !== false) {
            $raw = file_get_contents('php://input');
            $decoded = json_decode($raw, true);
            return $decoded ?: [];
        }

        if (in_array($this->getMethod(), ['POST', 'PUT', 'PATCH'])) {
            return $_POST ?: $this->parseFormData();
        }

        return [];
    }

    /**
     * Parse the request body content as form data.
     *
     * This method reads the request body content and parses it as form data.
     * The parsed data is returned as an associative array.
     *
     * @return array The parsed request body content.
     */
    protected function parseFormData(): array
    {
        $raw = file_get_contents('php://input');
        $data = [];
        parse_str($raw, $data);
        return $data;
    }

    /**
     * Parse the request headers.
     *
     * This method reads the request headers and parses them into an associative
     * array. The keys of the array are the header names in lowercase and the
     * values are the header values.
     *
     * @return array The parsed request headers.
     */
    protected function parseHeaders(): array
    {
        $headers = [];
        // Apache (if available)
        if (function_exists('getallheaders')) {
            foreach (getallheaders() as $key => $value) {
                $headers[strtolower($key)] = $value;
            }
            return $headers;
        }

        // Fallback for Nginx / PHP-FPM / CLI
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$name] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'])) {
                $name = strtolower(str_replace('_', '-', $key));
                $headers[$name] = $value;
            }
        }
        return $headers;
    }

    /**
     * Set the request parameters.
     *
     * This method sets the request parameters to the given associative array.
     *
     * @param array $params The request parameters.
     */
    public function setParams(array $params): void {
        $this->params = $params;
    }

    /**
     * Get the request parameters.
     *
     * This method returns the request parameters as an associative array.
     *
     * @return array The request parameters.
     */
    public function getParams(): array {
        return $this->params;
    }

    // === Public accessors ===

    /**
     * Get the query string parameter(s).
     *
     * This method returns the query string parameter(s) as an associative array.
     * If the $key parameter is given, it returns the value of the given key.
     * If the given key does not exist, it returns the given default value.
     *
     * @param string $key     The key of the query string parameter to retrieve.
     * @param mixed  $default The default value to return if the key does not exist.
     *
     * @return array|string|null The query string parameter(s) or the value of the given key.
     */
    public function getQuery(string $key = null, $default = null)
    {
        if ($key === null) return $this->query;
        return $this->query[$key] ?? $default;
    }

    /**
     * Get the request body parameter(s).
     *
     * This method returns the request body parameter(s) as an associative array.
     * If the $key parameter is given, it returns the value of the given key.
     * If the given key does not exist, it returns the given default value.
     *
     * @param string $key     The key of the request body parameter to retrieve.
     * @param mixed  $default The default value to return if the key does not exist.
     *
     * @return array|string|null The request body parameter(s) or the value of the given key.
     */
    public function getBody(string $key = null, $default = null)
    {
        if ($key === null) return $this->body;
        return $this->body[$key] ?? $default;
    }

    public function setBody(array $body): void
    {
        $this->body = $body;
    }

    /**
     * Get the request header(s).
     *
     * This method returns the request header(s) as an associative array.
     * If the $key parameter is given, it returns the value of the given key.
     * If the given key does not exist, it returns the given default value.
     *
     * @param string $key     The key of the request header to retrieve.
     * @param mixed  $default The default value to return if the key does not exist.
     *
     * @return array|string|null The request header(s) or the value of the given key.
     */
    public function getHeader(string $key = null, $default = null)
    {
        if ($key === null) return $this->headers;
        $key = strtolower($key);
        return $this->headers[$key] ?? $default;
    }

    /**
     * Set a request header.
     *
     * This method sets a request header to the given value.
     *
     * @param string $key   The key of the request header to set.
     * @param string $value The value of the request header to set.
     *
     * @return void
     */
    public function setHeader(string $key, string $value): void {
        $this->headers[strtolower($key)] = $value;
    }
    
    /**
     * Get the request method.
     *
     * This method returns the request method as a string, e.g. 'GET', 'POST', 'PUT', 'DELETE', etc.
     *
     * @return string The request method.
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    public function setMethod(string $method): void
    {
        $this->method = strtoupper($method);
    }

    /**
     * Get the request URI.
     *
     * This method returns the request URI as a string, e.g. '/todos', '/users', etc.
     *
     * @return string The request URI.
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    public function setUri(string $uri): void
    {
        $this->uri = $uri;
    }

    /**
     * Get the client IP address.
     *
     * This method returns the client IP address as a string, e.g. '192.168.1.1', '127.0.0.1', etc.
     *
     * @return string The client IP address.
     */
    public function ip(): string {
        return $this->ip;
    }

    /**
     * Get the Bearer token from the Authorization header.
     *
     * This method extracts and returns the Bearer token from the Authorization header.
     * If the Authorization header does not contain a Bearer token, it will return null.
     *
     * @return string|null The Bearer token or null if not found.
     */
    public function getBearerToken(): ?string
    {
        $auth = $this->getHeader('authorization');
        if ($auth && preg_match('/Bearer\s(\S+)/', $auth, $matches)) {
            return $matches[1];
        }
        return null;
    }

}
