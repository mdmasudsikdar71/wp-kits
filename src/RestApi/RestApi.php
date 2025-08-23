<?php

namespace MDMasudSikdar\WpKits\RestApi;

use MDMasudSikdar\WpKits\Helpers\Language;
use ReflectionMethod;

/**
 * Class RestApi
 *
 * Provides a centralized utility for registering WordPress REST API routes
 * using clean, reusable, and modern syntax.
 *
 * Supports both simple callbacks and controller method registration
 * using array syntax: [ClassName::class, 'method'].
 *
 * @package MDMasudSikdar\WpKits\RestApi
 */
class RestApi
{
    /**
     * Register a simple REST route with a callback function.
     *
     * @param string        $route Full route string, e.g. 'wpkits/v1/hello'
     * @param string        $method HTTP method: GET, POST, PUT, DELETE
     * @param callable      $callback Function that handles the request
     * @param callable|null $permission_callback Optional permission callback
     * @param array         $args Optional schema for validation
     */
    public static function register(
        string $route,
        string $method,
        callable $callback,
        ?callable $permission_callback = null,
        array $args = []
    ): void {
        // Use WordPress register_rest_route function
        register_rest_route(
            self::normalize_namespace($route), // Extract the namespace part
            self::normalize_endpoint($route),  // Extract the endpoint part
            [
                'methods'             => strtoupper($method),           // Ensure uppercase HTTP method
                'callback'            => $callback,                    // Route handler
                'permission_callback' => $permission_callback ?? '__return_true', // Default allow all
                'args'                => $args,                        // Optional arguments schema
            ]
        );
    }

    /**
     * Register a controller class method as a REST endpoint.
     *
     * Accepts array syntax [Class::class, 'method'] instead of string "Controller@method".
     *
     * @param string        $route Full route string, e.g. 'wpkits/v1/hello'
     * @param string        $method HTTP method: GET, POST, PUT, DELETE
     * @param array         $controller Array with class and method: [Class::class, 'method']
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
        // Extract class and method from array
        [$class, $action] = $controller;

        // Wrap the permission callback to include nonce verification automatically
        $permission_callback = function (\WP_REST_Request $request) use ($permission_callback) {
            if (!!self::verifyNonce($request)) {
                return self::response(Language::__('Invalid or missing nonce'), false, 403);
            }

            // If an original permission callback exists, call it
            if ($permission_callback && is_callable($permission_callback)) {
                return (bool) call_user_func($permission_callback, $request);
            }

            return true;
        };

        // Delegate to the register method
        self::register(
            $route,
            $method,
            function (\WP_REST_Request $request) use ($class, $action) {
                // Instantiate the controller
                $instance = new $class();

                // Validate method exists
                if (!method_exists($instance, $action)) {
                    return self::response(
                        "Method {$action} not found in {$class}", // Error message
                        false,
                        404
                    );
                }

                // Reflection for argument inspection
                $reflection = new ReflectionMethod($instance, $action);
                $params = $reflection->getParameters();

                try {
                    // Invoke method with WP_REST_Request if required
                    $result = $reflection->invokeArgs(
                        $instance,
                        count($params) > 0 ? [$request] : []
                    );

                    // Return standardized response
                    return self::response($result, true);
                } catch (\Throwable $e) {
                    // Return error response if exception occurs
                    return self::response($e->getMessage(), false, 500);
                }
            },
            $permission_callback,
            $args
        );
    }

    /**
     * Standardized REST API response.
     *
     * Wraps raw data but preserves WP_REST_Response instances (headers & status intact).
     *
     * @param mixed $payload Response data or WP_REST_Response
     * @param bool  $success Whether the request was successful
     * @param int   $status HTTP status code (default 200)
     * @param array $headers Optional headers
     *
     * @return \WP_REST_Response
     */
    public static function response(mixed $payload, bool $success = true, int $status = 200, array $headers = []): \WP_REST_Response
    {
        // Already a WP_REST_Response? Return as-is
        if ($payload instanceof \WP_REST_Response) {
            return $payload;
        }

        // Prepare structured response
        $response_data = [
            'success' => $success,
            'payload'    => $payload,
            'headers' => $headers,
            'status'  => $status,
            'nonce'   => wp_create_nonce('rest_nonce_' . md5(self::normalizeRoute($_SERVER['REQUEST_URI'])))
        ];

        $response = new \WP_REST_Response($response_data, $status);

        // Add custom headers if any
        foreach ($headers as $key => $value) {
            $response->header($key, $value);
        }

        return $response;
    }

    /**
     * Extract the namespace part from a full route.
     *
     * E.g. 'wpkits/v1/hello' => 'wpkits/v1'
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
     * Extract the endpoint part from a full route.
     *
     * E.g. 'wpkits/v1/hello' => '/hello'
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
     * Enable plain permalink fallback for REST API routes.
     *
     * When WordPress permalinks are set to "Plain", `/wp-json/...` normally fails,
     * and only the `?rest_route=/...` format works.
     *
     * This method fixes that by intercepting `/wp-json/...` requests in `parse_request`,
     * mapping them to the `rest_route` query var, and manually loading the REST API.
     *
     * Usage: Call inside your plugin bootstrap (e.g. in a service provider).
     *
     * @return void
     */
    public static function enablePlainPermalinkFallback(): void
    {
        add_action('parse_request', function ($wp) {
            // Only apply when permalinks are plain
            if (get_option('permalink_structure') === '') {
                $uri = $_SERVER['REQUEST_URI'] ?? '';

                // Match /wp-json/... requests
                if (preg_match('#/wp-json(/.*)?$#', $uri, $matches)) {
                    $rest_route = !empty($matches[1]) ? $matches[1] : '/';

                    // Emulate the rest_route query var
                    $_GET['rest_route']           = $rest_route;
                    $wp->query_vars['rest_route'] = $rest_route;

                    // Manually boot WordPress REST API dispatcher
                    require_once ABSPATH . 'wp-includes/rest-api.php';
                    rest_api_loaded();

                    exit;
                }
            }
        });
    }

    /**
     * Verify the nonce automatically using current request route.
     *
     * @param \WP_REST_Request $request
     *
     * @return bool
     */
    protected static function verifyNonce(\WP_REST_Request $request): bool
    {
        $nonce = $request->get_header('X-WP-Nonce') ?: '';

        return wp_verify_nonce($nonce, 'rest_nonce_' . md5(self::normalizeRoute($_SERVER['REQUEST_URI'])));
    }

    /**
     * Normalize URI to route string for nonce generation.
     *
     * @param string $uri
     * @return string
     */
    private static function normalizeRoute(string $uri): string
    {
        // Remove query string and trailing slashes
        $path = parse_url($uri, PHP_URL_PATH);
        return trim($path, '/');
    }
}
