<?php

namespace MDMasudSikdar\WpKits\RestApi;

/**
 * Class Route
 *
 * Provides a simple, Laravel-style DSL for registering WordPress REST API routes.
 *
 * This class wraps around the RestApi helper/service to make route registration
 * clean, readable, and consistent across all your plugins.
 *
 * Usage examples:
 *
 * ```php
 * use MDMasudSikdar\WpKits\RestApi\Route;
 *
 * // GET route
 * Route::get('wpkits/v1/hello', HelloController::class . '@index');
 *
 * // POST route
 * Route::post('wpkits/v1/hello', HelloController::class . '@store');
 *
 * // PUT route
 * Route::put('wpkits/v1/hello/{id}', HelloController::class . '@update');
 *
 * // PATCH route
 * Route::patch('wpkits/v1/hello/{id}', HelloController::class . '@update');
 *
 * // DELETE route
 * Route::delete('wpkits/v1/hello/{id}', HelloController::class . '@destroy');
 * ```
 *
 * @package MDMasudSikdar\WpKits\RestApi
 */
class Route
{
    /**
     * Register a GET route.
     *
     * @param string        $uri        The route URI, e.g., 'wpbp/v1/hello'.
     * @param string        $controller Controller@method string, e.g., HelloController@index.
     * @param callable|null $permission Optional permission callback.
     * @param array         $args       Optional argument schema for validation.
     * @return void
     */
    public static function get(string $uri, string $controller, ?callable $permission = null, array $args = []): void
    {
        // Delegate to generic register method with 'GET' HTTP method
        self::register('GET', $uri, $controller, $permission, $args);
    }

    /**
     * Register a POST route.
     *
     * @param string        $uri        The route URI.
     * @param string        $controller Controller@method string.
     * @param callable|null $permission Optional permission callback.
     * @param array         $args       Optional argument schema for validation.
     * @return void
     */
    public static function post(string $uri, string $controller, ?callable $permission = null, array $args = []): void
    {
        // Delegate to generic register method with 'POST' HTTP method
        self::register('POST', $uri, $controller, $permission, $args);
    }

    /**
     * Register a PUT route.
     *
     * @param string        $uri        The route URI.
     * @param string        $controller Controller@method string.
     * @param callable|null $permission Optional permission callback.
     * @param array         $args       Optional argument schema for validation.
     * @return void
     */
    public static function put(string $uri, string $controller, ?callable $permission = null, array $args = []): void
    {
        // Delegate to generic register method with 'PUT' HTTP method
        self::register('PUT', $uri, $controller, $permission, $args);
    }

    /**
     * Register a PATCH route.
     *
     * PATCH is commonly used for partial updates of resources.
     *
     * @param string        $uri        The route URI, e.g., 'wpkits/v1/hello/{id}'.
     * @param string        $controller Controller@method string.
     * @param callable|null $permission Optional permission callback.
     * @param array         $args       Optional argument schema for validation.
     * @return void
     */
    public static function patch(string $uri, string $controller, ?callable $permission = null, array $args = []): void
    {
        // Delegate to generic register method with 'PATCH' HTTP method
        self::register('PATCH', $uri, $controller, $permission, $args);
    }

    /**
     * Register a DELETE route.
     *
     * @param string        $uri        The route URI.
     * @param string        $controller Controller@method string.
     * @param callable|null $permission Optional permission callback.
     * @param array         $args       Optional argument schema for validation.
     * @return void
     */
    public static function delete(string $uri, string $controller, ?callable $permission = null, array $args = []): void
    {
        // Delegate to generic register method with 'DELETE' HTTP method
        self::register('DELETE', $uri, $controller, $permission, $args);
    }

    /**
     * Generic route registration method.
     *
     * This method wraps the RestApi::controller() call, which binds the controller
     * method, injects WP_REST_Request, handles exceptions, and returns unified responses.
     *
     * @param string        $method     HTTP method (GET, POST, PUT, DELETE)
     * @param string        $uri        The route URI.
     * @param string        $controller Controller@method string.
     * @param callable|null $permission Optional permission callback.
     * @param array         $args       Optional argument schema for validation.
     * @return void
     */
    protected static function register(string $method, string $uri, string $controller, ?callable $permission, array $args): void
    {
        // Call the RestApi service to register the route with the framework
        RestApi::controller($uri, $method, $controller, $permission, $args);
    }
}
