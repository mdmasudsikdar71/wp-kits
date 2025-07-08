<?php

namespace MDMasudSikdar\WpKits\Helpers;

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
     * @return void
     */
    public static function register(
        string $route,
        string $method,
        callable $callback,
        ?callable $permission_callback = null
    ): void {
        add_action('rest_api_init', function () use ($route, $method, $callback, $permission_callback) {
            register_rest_route(
                self::normalize_namespace($route),
                self::normalize_endpoint($route),
                [
                    'methods'             => strtoupper($method),
                    'callback'            => $callback,
                    'permission_callback' => $permission_callback ?? '__return_true',
                ]
            );
        });
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
