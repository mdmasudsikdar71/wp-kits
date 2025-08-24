<?php

namespace MDMasudSikdar\WpKits\Helpers;

use Exception;

/**
 * Class ApiClient
 *
 * A reusable, advanced HTTP client for WordPress plugin development.
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
 * // GET request with query params
 * $response = $client->get('users', ['page' => 1]);
 *
 * // POST request with payload
 * $response = $client->post('users', ['name' => 'John', 'email' => 'john@example.com']);
 * ```
 *
 * @package MDMasudSikdar\WpKits\Helpers
 */
class ApiClient
{
  /** @var string Base URL for all requests */
  protected string $baseUrl;

  /** @var array Default headers applied to every request */
  protected array $defaultHeaders = [];

  /** @var int Timeout in seconds for requests */
  protected int $timeout = 15;

  /** @var int Maximum retry attempts if request fails */
  protected int $maxRetries = 3;

  /** @var int Retry delay in milliseconds between attempts */
  protected int $retryDelay = 1000;

  /** @var bool Throw exception on request failure instead of returning error array */
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
   */
  public function __construct(string $baseUrl, array $config = [])
  {
    $this->baseUrl = rtrim($baseUrl, '/'); // ensure no trailing slash
    $this->defaultHeaders = $config['headers'] ?? [];
    $this->timeout = $config['timeout'] ?? 15;
    $this->maxRetries = $config['max_retries'] ?? 3;
    $this->retryDelay = $config['retry_delay'] ?? 1000;
    $this->throwOnError = $config['throw_on_error'] ?? false;
  }

  /**
   * Make an HTTP request with retries and error handling.
   *
   * @param string $endpoint API endpoint relative to base URL.
   * @param string $method HTTP method (GET, POST, PUT, DELETE).
   * @param array $args Optional arguments:
   *                    - headers: array of headers for this request
   *                    - body: array|string for POST/PUT/PATCH payload
   *                    - query: array for GET query parameters
   * @return array Response array containing:
   *               - success: bool
   *               - status: HTTP status code
   *               - headers: response headers
   *               - body: decoded JSON or raw body
   *               - error: string if failed
   * @throws Exception
   */
  public function request(string $endpoint, string $method = 'GET', array $args = []): array
  {
    // Combine base URL and endpoint
    $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
    $method = strtoupper($method);

    // Merge default headers with request-specific headers
    $options = [
      'headers' => array_merge($this->defaultHeaders, $args['headers'] ?? []),
      'timeout' => $this->timeout,
    ];

    // Encode body automatically if method supports payload
    if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
      $body = $args['body'] ?? [];
      $options['body'] = is_array($body) ? wp_json_encode($body) : $body;
      $options['headers']['Content-Type'] = $options['headers']['Content-Type'] ?? 'application/json';
    } elseif ($method === 'GET' && !empty($args['query'])) {
      // Append query parameters to GET requests
      $url = add_query_arg($args['query'], $url);
    }

    return $this->executeWithRetries($url, $method, $options);
  }

  /**
   * Executes the request with retry logic and error handling.
   *
   * @param string $url Full request URL.
   * @param string $method HTTP method.
   * @param array $options Options passed to wp_remote_request.
   * @return array Response array as in request().
   * @throws Exception if throwOnError=true and request fails.
   */
  protected function executeWithRetries(string $url, string $method, array $options): array
  {
    $attempt = 0;

    // Retry loop
    do {
      $response = wp_remote_request($url, array_merge($options, ['method' => $method]));
      if (!is_wp_error($response)) break;
      $attempt++;
      usleep($this->retryDelay * 1000); // milliseconds to microseconds
    } while ($attempt < $this->maxRetries);

    // Handle error
    if (is_wp_error($response)) {
      $error = $response->get_error_message();
      if ($this->throwOnError) {
        throw new Exception($error);
      }
      return ['success' => false, 'error' => $error];
    }

    // Decode response body if JSON
    $body = wp_remote_retrieve_body($response);
    $decoded = json_decode($body, true);

    return [
      'success' => true,
      'status' => wp_remote_retrieve_response_code($response),
      'headers' => wp_remote_retrieve_headers($response),
      'body' => $decoded ?? $body,
    ];
  }

  /**
   * Shortcut for GET requests.
   *
   * @param string $endpoint API endpoint.
   * @param array $query Optional query parameters.
   * @return array Response array
   * @throws Exception
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
   * @return array Response array
   * @throws Exception
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
   * @return array Response array
   * @throws Exception
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
   * @return array Response array
   * @throws Exception
   */
  public function delete(string $endpoint, array $body = []): array
  {
    return $this->request($endpoint, 'DELETE', ['body' => $body]);
  }
}
