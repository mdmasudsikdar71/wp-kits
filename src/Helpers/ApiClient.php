<?php

namespace MDMasudSikdar\WpKits\Helpers;

use Exception;

/**
 * Class ApiClient
 *
 * Advanced HTTP client for WordPress plugin development.
 * Supports GET, POST, PUT, DELETE requests, automatic JSON encoding,
 * retries, custom headers, timeouts, and optional exception throwing.
 *
 * Example usage:
 * ```php
 * $client = new ApiClient('https://api.example.com', [
 *     'headers' => ['Authorization' => 'Bearer YOUR_TOKEN'],
 *     'timeout' => 10,
 *     'max_retries' => 3,
 *     'retry_delay' => 500,
 *     'throw_on_error' => true
 * ]);
 *
 * $response = $client->get('users', ['page' => 1]);
 * $response = $client->post('users', ['name' => 'John', 'email' => 'john@example.com']);
 * ```
 *
 * @package MDMasudSikdar\WpKits\Helpers
 */
class ApiClient
{
    /**
     * Base URL for all API requests.
     * Example: "https://api.example.com/v1"
     *
     * @var string
     */
    protected string $baseUrl;

    /**
     * Default headers applied to every request.
     * Example: ['Authorization' => 'Bearer TOKEN']
     *
     * @var array<string, string>
     */
    protected array $defaultHeaders = [];

    /**
     * Timeout for requests in seconds.
     * Default: 15
     *
     * @var int
     */
    protected int $timeout = 15;

    /**
     * Maximum retry attempts if a request fails.
     * Default: 3
     *
     * @var int
     */
    protected int $maxRetries = 3;

    /**
     * Delay between retry attempts in milliseconds.
     * Default: 1000ms = 1 second
     *
     * @var int
     */
    protected int $retryDelay = 1000;

    /**
     * Whether to throw an Exception on request failure
     * instead of returning a response array with 'success' => false.
     * Default: false
     *
     * @var bool
     */
    protected bool $throwOnError = false;

    /**
     * ApiClient constructor.
     *
     * @param string $baseUrl Base URL of the API.
     * @param array $config Optional configuration:
     *                      - headers: array of default headers
     *                      - timeout: int seconds
     *                      - max_retries: int
     *                      - retry_delay: int milliseconds
     *                      - throw_on_error: bool
     *
     * @return void
     *
     * @example
     * ```php
     * $client = new ApiClient('https://api.example.com', [
     *     'headers' => ['Authorization' => 'Bearer TOKEN'],
     *     'timeout' => 10,
     *     'max_retries' => 3,
     *     'retry_delay' => 500,
     *     'throw_on_error' => true
     * ]);
     * ```
     */
    public function __construct(string $baseUrl, array $config = [])
    {
        // Ensure base URL has no trailing slash
        $this->baseUrl = rtrim($baseUrl, '/');

        // Set default headers
        $this->defaultHeaders = $config['headers'] ?? [];

        // Set request timeout
        $this->timeout = $config['timeout'] ?? 15;

        // Set maximum retry attempts
        $this->maxRetries = $config['max_retries'] ?? 3;

        // Set retry delay in milliseconds
        $this->retryDelay = $config['retry_delay'] ?? 1000;

        // Set whether to throw exceptions on errors
        $this->throwOnError = $config['throw_on_error'] ?? false;
    }

    /**
     * Make an HTTP request with retries and error handling.
     *
     * @param string $endpoint API endpoint relative to base URL.
     * @param string $method HTTP method (GET, POST, PUT, DELETE).
     * @param array $args Optional arguments:
     *                    - headers: array of headers
     *                    - body: array|string for POST/PUT payload
     *                    - query: array for GET query parameters
     *
     * @return array Response array containing:
     *               - success: bool
     *               - status: int HTTP status code
     *               - headers: array response headers
     *               - body: decoded JSON or raw body
     *               - error: string|null if failed
     *
     * @throws Exception If throwOnError=true and request fails
     *
     * @example
     * ```php
     * $response = $client->request('users', 'GET', ['query' => ['page' => 1]]);
     * ```
     */
    public function request(string $endpoint, string $method = 'GET', array $args = []): array
    {
        // Combine base URL and endpoint
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');

        // Normalize HTTP method
        $method = strtoupper($method);

        // Merge default headers with request-specific headers
        $options = [
            'headers' => array_merge($this->defaultHeaders, $args['headers'] ?? []),
            'timeout' => $this->timeout,
        ];

        // Handle body for POST, PUT, PATCH
        if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            $body = $args['body'] ?? [];
            $options['body'] = is_array($body) ? wp_json_encode($body) : $body;
            $options['headers']['Content-Type'] = $options['headers']['Content-Type'] ?? 'application/json';
        }

        // Handle GET query parameters
        if ($method === 'GET' && !empty($args['query'])) {
            $url = add_query_arg($args['query'], $url);
        }

        // Execute the request with retries
        return $this->executeWithRetries($url, $method, $options);
    }

    /**
     * Execute HTTP request with retry logic and error handling.
     *
     * @param string $url Full request URL.
     * @param string $method HTTP method.
     * @param array $options Options passed to wp_remote_request.
     *
     * @return array Response array same as request().
     *
     * @throws Exception If throwOnError=true and request fails.
     *
     * @example
     * ```php
     * $response = $client->executeWithRetries('https://api.example.com/users', 'GET', ['timeout' => 10]);
     * ```
     */
    protected function executeWithRetries(string $url, string $method, array $options): array
    {
        $attempt = 0;

        // Retry loop
        do {
            // Make the HTTP request
            $response = wp_remote_request($url, array_merge($options, ['method' => $method]));

            // Break if request succeeded
            if (!is_wp_error($response)) {
                break;
            }

            // Wait before retrying
            $attempt++;
            usleep($this->retryDelay * 1000); // convert ms to microseconds
        } while ($attempt < $this->maxRetries);

        // Handle errors
        if (is_wp_error($response)) {
            $error = $response->get_error_message();
            if ($this->throwOnError) {
                throw new Exception($error);
            }
            return [
                'success' => false,
                'status' => 0,
                'headers' => [],
                'body' => null,
                'error' => $error,
            ];
        }

        // Decode JSON response body if applicable
        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);

        return [
            'success' => true,
            'status' => wp_remote_retrieve_response_code($response),
            'headers' => wp_remote_retrieve_headers($response),
            'body' => $decoded ?? $body,
            'error' => null,
        ];
    }

    /**
     * Shortcut for GET requests.
     *
     * @param string $endpoint API endpoint.
     * @param array $query Optional query parameters.
     *
     * @return array Response array.
     *
     * @throws Exception
     *
     * @example
     * ```php
     * $response = $client->get('users', ['page' => 1]);
     * ```
     */
    public function get(string $endpoint, array $query = []): array
    {
        return $this->request($endpoint, 'GET', ['query' => $query]);
    }

    /**
     * Shortcut for POST requests.
     *
     * @param string $endpoint API endpoint.
     * @param array $body POST payload.
     * @param array $headers Optional headers.
     *
     * @return array Response array.
     *
     * @throws Exception
     *
     * @example
     * ```php
     * $response = $client->post('users', ['name' => 'John'], ['Authorization' => 'Bearer TOKEN']);
     * ```
     */
    public function post(string $endpoint, array $body = [], array $headers = []): array
    {
        return $this->request($endpoint, 'POST', ['body' => $body, 'headers' => $headers]);
    }

    /**
     * Shortcut for PUT requests.
     *
     * @param string $endpoint API endpoint.
     * @param array $body PUT payload.
     * @param array $headers Optional headers.
     *
     * @return array Response array.
     *
     * @throws Exception
     *
     * @example
     * ```php
     * $response = $client->put('users/1', ['name' => 'John Updated']);
     * ```
     */
    public function put(string $endpoint, array $body = [], array $headers = []): array
    {
        return $this->request($endpoint, 'PUT', ['body' => $body, 'headers' => $headers]);
    }

    /**
     * Shortcut for DELETE requests.
     *
     * @param string $endpoint API endpoint.
     * @param array $body Optional DELETE payload.
     *
     * @return array Response array.
     *
     * @throws Exception
     *
     * @example
     * ```php
     * $response = $client->delete('users/1');
     * ```
     */
    public function delete(string $endpoint, array $body = []): array
    {
        return $this->request($endpoint, 'DELETE', ['body' => $body]);
    }
}
