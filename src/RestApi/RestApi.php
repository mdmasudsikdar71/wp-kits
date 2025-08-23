<?php

namespace MDMasudSikdar\WpKits\RestApi;

use ReflectionMethod;

/**
 * Class RestApi
 *
 * Provides a modern, centralized utility for registering WordPress REST API routes.
 * Supports both callback functions and controller array syntax [Class::class, 'method'].
 *
 * Automatically handles both:
 * - Pretty permalinks: /wp-json/...
 * - Plain permalinks: /index.php?rest_route=...
 *
 * @package MDMasudSikdar\WpKits\RestApi
 */
class RestApi
{
    /**
     * Register a simple REST route with a callback function.
     *
     * @param string        $route Full route string (e.g., 'wpbp/v1/hello')
     * @param string        $method HTTP method (GET, POST, PUT, DELETE)
     * @param callable      $callback Function that handles the request
     * @param callable|null $permission_callback Optional permission callback
     * @param array         $args Optional arguments schema for validation
     */
    public static function register(
        string $route,
        string $method,
        callable $callback,
        ?callable $permission_callback = null,
        array $args = []
    ): void {
        // Register the REST route with WordPress core
        register_rest_route(
            self::normalize_namespace($route),  // Extract the namespace part of route
            self::normalize_endpoint($route),   // Extract the endpoint part
            [
                'methods'             => strtoupper($method),         // Ensure uppercase HTTP method
                'callback'            => $callback,                  // The actual callback function
                'permission_callback' => $permission_callback ?? '__return_true', // Default: allow all
                'args'                => $args,                      // Optional argument schema
            ]
        );

        // Automatically handle plain permalink requests for non-pretty URLs
        self::plain_permalink_fallback($route, $callback);
    }

    /**
     * Register a controller method as a REST endpoint.
     *
     * Accepts array syntax [ControllerClass::class, 'method'].
     * Supports dependency-free invocation of controller methods.
     *
     * @param string        $route Full route string (e.g., 'wpbp/v1/hello')
     * @param string        $method HTTP method
     * @param array         $controller Array [Class::class, 'method']
     * @param callable|null $permission_callback Optional permission callback
     * @param array         $args Optional arguments schema
     */
    public static function controller(
        string $route,
        string $method,
        array $controller,
        ?callable $permission_callback = null,
        array $args = []
    ): void {
        // Extract class name and method from the array
        [$class, $action] = $controller;

        // Wrap controller method in a callback for WordPress REST API
        $callback = function (\WP_REST_Request $request) use ($class, $action) {
            $instance = new $class(); // Instantiate the controller

            // Validate the requested method exists
            if (!method_exists($instance, $action)) {
                return self::response("Method {$action} not found in {$class}", false, 404);
            }

            // Use reflection to inspect method parameters
            $reflection = new ReflectionMethod($instance, $action);
            $params = $reflection->getParameters();

            try {
                // Invoke method with request if method expects arguments
                $result = $reflection->invokeArgs(
                    $instance,
                    count($params) > 0 ? [$request] : []
                );

                // Return standardized REST response
                return self::response($result, true);
            } catch (\Throwable $e) {
                // Catch exceptions and return a 500 response
                return self::response($e->getMessage(), false, 500);
            }
        };

        // Delegate to the main register function
        self::register($route, $method, $callback, $permission_callback, $args);
    }

    /**
     * Standardized REST API response wrapper.
     *
     * Always returns the same JSON structure:
     * [
     *   'success' => bool,
     *   'payload' => mixed // either response data or error message
     * ]
     *
     * @param mixed $payload Response data or error message
     * @param bool  $success True if request succeeded
     * @param int   $status HTTP status code (default: 200)
     * @return \WP_REST_Response
     */
    public static function response(mixed $payload, bool $success = true, int $status = 200): \WP_REST_Response
    {
        return new \WP_REST_Response([
            'success' => $success,
            'payload' => $payload,
        ], $status);
    }

    /**
     * Extract the namespace part from a full route string.
     *
     * Example:
     * 'wpbp/v1/hello' => 'wpbp/v1'
     *
     * @param string $route Full route string
     * @return string Namespace
     */
    private static function normalize_namespace(string $route): string
    {
        $parts = explode('/', trim($route, '/'));
        return implode('/', array_slice($parts, 0, 2));
    }

    /**
     * Extract the endpoint part from a full route string.
     *
     * Example:
     * 'wpbp/v1/hello' => '/hello'
     *
     * @param string $route Full route string
     * @return string Endpoint string
     */
    private static function normalize_endpoint(string $route): string
    {
        $parts = explode('/', trim($route, '/'));
        return '/' . implode('/', array_slice($parts, 2));
    }

    /**
     * Handles plain permalink requests automatically.
     *
     * This avoids the need for users to manually flush permalinks.
     * Works by intercepting the 'rest_route' query parameter.
     *
     * @param string   $route Full route string
     * @param callable $callback The callback to invoke for the route
     */
    private static function plain_permalink_fallback(string $route, callable $callback): void
    {
        // Use init hook to ensure WordPress query vars are ready
        add_action('init', function () use ($route, $callback) {
            global $wp_rewrite;

            // Only apply fallback if pretty permalinks are disabled
            if (empty($wp_rewrite->rules)) {

                // Add 'rest_route' as a valid query var
                add_filter('query_vars', function ($vars) {
                    $vars[] = 'rest_route';
                    return $vars;
                });

                // Intercept plain permalink requests during request parsing
                add_action('parse_request', function ($wp) use ($route, $callback) {
                    if (!empty($_GET['rest_route'])) {
                        $requested = ltrim($_GET['rest_route'], '/');
                        if ($requested === $route) {
                            // Call the route callback and send JSON response immediately
                            $response = call_user_func($callback, null);
                            wp_send_json($response);
                            exit;
                        }
                    }
                });
            }
        });
    }
}
