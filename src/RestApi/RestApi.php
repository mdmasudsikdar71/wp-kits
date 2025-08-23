<?php

namespace MDMasudSikdar\WpKits\RestApi;

use ReflectionMethod;

/**
 * Class RestApi
 *
 * Provides a static utility to register WordPress REST API routes
 * with a clean and reusable syntax.
 *
 * @example Registering a GET route
 * ```php
 * RestApi::register(
 *     'wpkits/v1/hello',
 *     'GET',
 *     fn() => ['message' => 'Hello World']
 * );
 * ```
 *
 * @example Registering a POST route with permission callback
 * ```php
 * RestApi::register(
 *     'wpkits/v1/secure',
 *     'POST',
 *     fn($request) => ['message' => 'Authorized'],
 *     fn() => current_user_can('edit_posts')
 * );
 * ```
 *
 * @package MDMasudSikdar\WpKits\Helpers
 */
class RestApi
{
    /**
     * Register a REST route.
     *
     * @param string $route Full route path, e.g. 'wpkits/v1/example'.
     * @param string $method HTTP method: GET, POST, PUT, DELETE, etc.
     * @param callable $callback The function to handle the request.
     * @param callable|null $permission_callback Optional permission callback.
     * @param array $args Arguments schema for validation.
     */
    public static function register(
        string $route,
        string $method,
        callable $callback,
        ?callable $permission_callback = null,
        array $args = []
    ): void {
        register_rest_route(
            self::normalize_namespace($route),
            self::normalize_endpoint($route),
            [
                'methods'             => strtoupper($method),
                'callback'            => $callback,
                'permission_callback' => $permission_callback ?? '__return_true',
                'args'                => $args,
            ]
        );
    }

    /**
     * Register a controller method as a REST endpoint.
     *
     * @param string $route e.g. 'wpkits/v1/hello'
     * @param string $method e.g. GET|POST|PUT|DELETE
     * @param string $controller e.g. App\Controllers\HelloController@index
     * @param callable|null $permission_callback
     * @param array $args
     */
    public static function controller(
        string $route,
        string $method,
        string $controller,
        ?callable $permission_callback = null,
        array $args = []
    ): void {
        [$class, $action] = explode('@', $controller);

        self::register(
            $route,
            $method,
            function (\WP_REST_Request $request) use ($class, $action) {
                $instance = new $class();

                if (!method_exists($instance, $action)) {
                    return self::response("Method {$action} not found in {$class}", false, 404);
                }

                $reflection = new ReflectionMethod($instance, $action);
                $params     = $reflection->getParameters();

                try {
                    $result = $reflection->invokeArgs(
                        $instance,
                        count($params) > 0 ? [$request] : []
                    );

                    return self::response($result, true);
                } catch (\Throwable $e) {
                    return self::response($e->getMessage(), false, 500);
                }
            },
            $permission_callback,
            $args
        );
    }

    /**
     * Unified response wrapper.
     *
     * Always returns the same structure:
     * [
     *   'success' => bool,
     *   'payload' => mixed, // either data or error message
     * ]
     *
     * @param mixed $payload Data or error message.
     * @param bool $success Success state.
     * @param int $status HTTP status code.
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
     * Extract the namespace from a full route string.
     *
     * @param string $route E.g. 'wpkits/v1/endpoint'
     * @return string Namespace, e.g. 'wpkits/v1'
     */
    private static function normalize_namespace(string $route): string
    {
        $parts = explode('/', trim($route, '/'));
        return implode('/', array_slice($parts, 0, 2));
    }

    /**
     * Extract the endpoint from a full route string.
     *
     * @param string $route E.g. 'wpkits/v1/endpoint'
     * @return string Endpoint, e.g. '/endpoint'
     */
    private static function normalize_endpoint(string $route): string
    {
        $parts = explode('/', trim($route, '/'));
        return '/' . implode('/', array_slice($parts, 2));
    }
}
