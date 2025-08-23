<?php

namespace MDMasudSikdar\WpKits\RestApi;

/**
 * Class Route
 *
 * Provides a simple, Laravel-style DSL for registering WordPress REST API routes.
 *
 * Wraps the RestApi helper/service to make route registration
 * clean, readable, and consistent across all plugins.
 *
 * Usage examples:
 * ```php
 * use MDMasudSikdar\WpKits\RestApi\Route;
 *
 * Route::get('myplugin/v1/hello', 'HelloController@index');
 * Route::post('myplugin/v1/hello', 'HelloController@store');
 * Route::patch('myplugin/v1/hello/{id}', 'HelloController@partialUpdate');
 * Route::delete('myplugin/v1/hello/{id}', 'HelloController@destroy');
 * ```
 *
 * @package MDMasudSikdar\WpKits\RestApi
 */
final class Route
{
    /**
     * Register a GET route.
     *
     * @param string $uri The route URI, e.g., 'myplugin/v1/hello'
     * @param string $controller Controller@method string, e.g., 'HelloController@index'
     * @param callable|null $permission Optional permission callback
     * @param array $args Optional argument schema for validation
     * @return void
     */
    public static function get(string $uri, string $controller, ?callable $permission = null, array $args = []): void
    {
        // Call generic register method with HTTP method 'GET'
        self::register('GET', $uri, $controller, $permission, $args);
    }

    /**
     * Register a POST route.
     *
     * @param string $uri The route URI
     * @param string $controller Controller@method string
     * @param callable|null $permission Optional permission callback
     * @param array $args Optional argument schema
     * @return void
     */
    public static function post(string $uri, string $controller, ?callable $permission = null, array $args = []): void
    {
        // Call generic register method with HTTP method 'POST'
        self::register('POST', $uri, $controller, $permission, $args);
    }

    /**
     * Register a PUT route.
     *
     * Typically used to fully update a resource.
     *
     * @param string $uri The route URI
     * @param string $controller Controller@method string
     * @param callable|null $permission Optional permission callback
     * @param array $args Optional argument schema
     * @return void
     */
    public static function put(string $uri, string $controller, ?callable $permission = null, array $args = []): void
    {
        // Call generic register method with HTTP method 'PUT'
        self::register('PUT', $uri, $controller, $permission, $args);
    }

    /**
     * Register a PATCH route.
     *
     * Typically used to partially update a resource.
     *
     * @param string $uri The route URI
     * @param string $controller Controller@method string
     * @param callable|null $permission Optional permission callback
     * @param array $args Optional argument schema
     * @return void
     */
    public static function patch(string $uri, string $controller, ?callable $permission = null, array $args = []): void
    {
        // Call generic register method with HTTP method 'PATCH'
        self::register('PATCH', $uri, $controller, $permission, $args);
    }

    /**
     * Register a DELETE route.
     *
     * Typically used to remove a resource.
     *
     * @param string $uri The route URI
     * @param string $controller Controller@method string
     * @param callable|null $permission Optional permission callback
     * @param array $args Optional argument schema
     * @return void
     */
    public static function delete(string $uri, string $controller, ?callable $permission = null, array $args = []): void
    {
        // Call generic register method with HTTP method 'DELETE'
        self::register('DELETE', $uri, $controller, $permission, $args);
    }

    /**
     * Generic route registration method.
     *
     * Handles the actual registration with RestApi::controller(),
     * which internally binds the controller, injects WP_REST_Request,
     * handles exceptions, and returns unified responses.
     *
     * @param string $method HTTP method (GET, POST, PUT, PATCH, DELETE)
     * @param string $uri The route URI
     * @param string $controller Controller@method string
     * @param callable|null $permission Optional permission callback
     * @param array $args Optional argument schema
     * @return void
     */
    protected static function register(
        string $method,
        string $uri,
        string $controller,
        ?callable $permission,
        array $args
    ): void {

        // Delegate to the RestApi helper to register the controller as a route
        RestApi::controller($uri, $method, $controller, $permission, $args);
    }
}
