<?php

namespace MDMasudSikdar\WpKits\RestApi;

use ReflectionMethod;

/**
 * Class RestApi
 *
 * Centralized utility for registering WordPress REST API routes
 * with clean, reusable, and modern syntax.
 *
 * Features:
 * ✅ Supports simple callbacks and controller methods
 * ✅ Automatic namespace and endpoint parsing
 * ✅ Standardized responses with success/status
 * ✅ Nonce verification for security
 *
 * Example usage:
 * ```php
 * // Simple route with callback
 * RestApi::register(
 *     'wpkits/v1/hello',
 *     'GET',
 *     function($request) {
 *         return RestApi::response(['message' => 'Hello World']);
 *     }
 * );
 *
 * // Controller-based route
 * RestApi::controller(
 *     'wpkits/v1/users',
 *     'POST',
 *     [UserController::class, 'createUser']
 * );
 * ```
 *
 * @package MDMasudSikdar\WpKits\RestApi
 */
class RestApi
{
    /**
     * Register a simple REST route with a callback.
     *
     * @param string        $route Full route string (e.g., 'wpkits/v1/hello')
     * @param string        $method HTTP method: GET, POST, PUT, DELETE
     * @param callable      $callback Function that handles the request
     * @param callable|null $permission_callback Optional permission callback
     * @param array         $args Optional schema for validation
     * @return void
     *
     * @example
     * ```php
     * RestApi::register('wpkits/v1/status', 'GET', function($request){
     *     return RestApi::response(['status'=>'ok']);
     * });
     * ```
     */
    public static function register(
        string $route,
        string $method,
        callable $callback,
        ?callable $permission_callback = null,
        array $args = []
    ): void {
        // Call WordPress register_rest_route with normalized namespace and endpoint
        register_rest_route(
            self::normalize_namespace($route), // Extract namespace, e.g., 'wpkits/v1'
            self::normalize_endpoint($route),  // Extract endpoint, e.g., '/hello'
            [
                'methods'             => strtoupper($method),                 // HTTP method
                'callback'            => $callback,                           // Route handler
                'permission_callback' => $permission_callback ?? '__return_true', // Default allow all
                'args'                => $args,                               // Optional arguments
            ]
        );
    }

    /**
     * Register a controller class method as a REST endpoint.
     *
     * Accepts array syntax [Class::class, 'method'].
     *
     * @param string        $route Full route string, e.g. 'wpkits/v1/hello'
     * @param string        $method HTTP method
     * @param array         $controller Array with class and method: [Class::class, 'method']
     * @param callable|null $permission_callback Optional permission callback
     * @param array         $args Optional validation schema
     * @return void
     *
     * @example
     * ```php
     * RestApi::controller('wpkits/v1/users', 'POST', [UserController::class, 'createUser']);
     * ```
     */
    public static function controller(
        string $route,
        string $method,
        array $controller,
        ?callable $permission_callback = null,
        array $args = []
    ): void {
        // Extract class and method from controller array
        [$class, $action] = $controller;

        // Wrap permission callback to include nonce verification
        $permission_callback = function (\WP_REST_Request $request) use ($permission_callback) {
            // Only verify nonce for non-GET requests
            if ($request->get_method() !== 'GET') {
                if (!self::verifyNonce($request->get_header('X-WP-Nonce'), $request->get_header('X-Plugin-Nonce'))) {
                    return self::response(__('Invalid or missing nonce'), false, 403);
                }
            }

            // Call original permission callback if exists
            if ($permission_callback && is_callable($permission_callback)) {
                return (bool) call_user_func($permission_callback, $request);
            }

            return true; // Default allow
        };

        // Delegate to register method
        self::register(
            $route,
            $method,
            function (\WP_REST_Request $request) use ($class, $action) {
                // Instantiate controller
                $instance = new $class();

                // Validate method exists
                if (!method_exists($instance, $action)) {
                    return self::response("Method {$action} not found in {$class}", false, 404);
                }

                // Inspect method parameters using Reflection
                $reflection = new ReflectionMethod($instance, $action);
                $params = $reflection->getParameters();

                try {
                    // Call method, passing WP_REST_Request if needed
                    $result = $reflection->invokeArgs(
                        $instance,
                        count($params) > 0 ? [$request] : []
                    );

                    // Return standardized success response
                    return self::response($result, true);
                } catch (\Throwable $e) {
                    // Return standardized error response on exception
                    return self::response($e->getMessage(), false, 500);
                }
            },
            $permission_callback,
            $args
        );
    }

    /**
     * Standardized REST API response wrapper.
     *
     * Converts raw data to WP_REST_Response while preserving headers and status.
     *
     * @param mixed $payload Data to return
     * @param bool  $success Success flag
     * @param int   $status HTTP status code
     * @param array $headers Optional HTTP headers
     * @return \WP_REST_Response
     *
     * @example
     * ```php
     * return RestApi::response(['message'=>'Hello'], true, 200);
     * ```
     */
    public static function response(mixed $payload, bool $success = true, int $status = 200, array $headers = []): \WP_REST_Response
    {
        // If already WP_REST_Response, return as-is
        if ($payload instanceof \WP_REST_Response) {
            return $payload;
        }

        // Prepare structured response
        $response_data = [
            'success' => $success,
            'payload' => $payload,
            'headers' => $headers,
            'status'  => $status,
        ];

        // Create WP_REST_Response instance
        $response = new \WP_REST_Response($response_data, $status);

        // Set custom headers
        foreach ($headers as $key => $value) {
            $response->header($key, $value);
        }

        return $response;
    }

    /**
     * Extract namespace from full route string.
     *
     * E.g., 'wpkits/v1/hello' => 'wpkits/v1'
     *
     * @param string $route Full route string
     * @return string Namespace
     */
    private static function normalize_namespace(string $route): string
    {
        $parts = explode('/', trim($route, '/')); // Split route into segments
        return implode('/', array_slice($parts, 0, 2)); // Take first 2 segments as namespace
    }

    /**
     * Extract endpoint from full route string.
     *
     * E.g., 'wpkits/v1/hello' => '/hello'
     *
     * @param string $route Full route string
     * @return string Endpoint string
     */
    private static function normalize_endpoint(string $route): string
    {
        $parts = explode('/', trim($route, '/')); // Split route
        return '/' . implode('/', array_slice($parts, 2)); // Join remaining parts as endpoint
    }

    /**
     * Verify WordPress nonce for security.
     *
     * @param string $nonce Nonce string
     * @param string $key Action/key to verify against
     * @return bool True if valid, false otherwise
     */
    private static function verifyNonce(string $nonce, string $key): bool
    {
        return wp_verify_nonce($nonce, $key);
    }

    /**
     * Generate a WordPress nonce for a given key.
     *
     * @param string $key Action/key for nonce
     * @return string Nonce string
     */
    private static function generateNonce(string $key): string
    {
        return wp_create_nonce($key);
    }
}
