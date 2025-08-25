<?php

namespace MDMasudSikdar\WpKits\RestApi;

/**
 * Class Route
 *
 * Provides a Laravel-style, fluent static API for registering WordPress REST routes.
 *
 * Supports GET, POST, PUT, PATCH, and DELETE methods.
 *
 * Features:
 * ✅ Clean, readable syntax for route registration
 * ✅ Automatic delegation to RestApi helper
 * ✅ Supports controller array syntax [Class::class, 'method']
 * ✅ Optional permission callbacks and argument schemas
 *
 * Example usage:
 * ```php
 * use MDMasudSikdar\WpKits\RestApi\Route;
 * use MyPlugin\Controllers\HelloController;
 *
 * // GET route
 * Route::get('wpkits/v1/hello', [HelloController::class, 'index']);
 *
 * // POST route
 * Route::post('wpkits/v1/hello', [HelloController::class, 'store']);
 *
 * // PUT route
 * Route::put('wpkits/v1/hello/{id}', [HelloController::class, 'update']);
 *
 * // PATCH route
 * Route::patch('wpkits/v1/hello/{id}', [HelloController::class, 'partialUpdate']);
 *
 * // DELETE route
 * Route::delete('wpkits/v1/hello/{id}', [HelloController::class, 'destroy']);
 * ```
 *
 * @package MDMasudSikdar\WpKits\RestApi
 */
final class Route
{
    /**
     * Register a GET route.
     *
     * Typically used for retrieving resources.
     *
     * @param string        $uri REST route URI, e.g., 'wpkits/v1/hello'
     * @param array         $controller Controller array: [Class::class, 'method']
     * @param callable|null $permission Optional permission callback
     * @param array         $args Optional argument schema for validation
     * @return void
     *
     * @example
     * ```php
     * Route::get('wpkits/v1/status', [StatusController::class, 'index']);
     * ```
     */
    public static function get(string $uri, array $controller, ?callable $permission = null, array $args = []): void
    {
        // Delegate to the generic register method
        self::register('GET', $uri, $controller, $permission, $args);
    }

    /**
     * Register a POST route.
     *
     * Typically used for creating resources.
     *
     * @param string        $uri REST route URI
     * @param array         $controller Controller array
     * @param callable|null $permission Optional permission callback
     * @param array         $args Optional argument schema
     * @return void
     *
     * @example
     * ```php
     * Route::post('wpkits/v1/users', [UserController::class, 'store']);
     * ```
     */
    public static function post(string $uri, array $controller, ?callable $permission = null, array $args = []): void
    {
        self::register('POST', $uri, $controller, $permission, $args);
    }

    /**
     * Register a PUT route.
     *
     * Typically used for full updates of resources.
     *
     * @param string        $uri REST route URI
     * @param array         $controller Controller array
     * @param callable|null $permission Optional permission callback
     * @param array         $args Optional argument schema
     * @return void
     *
     * @example
     * ```php
     * Route::put('wpkits/v1/users/{id}', [UserController::class, 'update']);
     * ```
     */
    public static function put(string $uri, array $controller, ?callable $permission = null, array $args = []): void
    {
        self::register('PUT', $uri, $controller, $permission, $args);
    }

    /**
     * Register a PATCH route.
     *
     * Typically used for partial updates of resources.
     *
     * @param string        $uri REST route URI
     * @param array         $controller Controller array
     * @param callable|null $permission Optional permission callback
     * @param array         $args Optional argument schema
     * @return void
     *
     * @example
     * ```php
     * Route::patch('wpkits/v1/users/{id}', [UserController::class, 'partialUpdate']);
     * ```
     */
    public static function patch(string $uri, array $controller, ?callable $permission = null, array $args = []): void
    {
        self::register('PATCH', $uri, $controller, $permission, $args);
    }

    /**
     * Register a DELETE route.
     *
     * Typically used for removing resources.
     *
     * @param string        $uri REST route URI
     * @param array         $controller Controller array
     * @param callable|null $permission Optional permission callback
     * @param array         $args Optional argument schema
     * @return void
     *
     * @example
     * ```php
     * Route::delete('wpkits/v1/users/{id}', [UserController::class, 'destroy']);
     * ```
     */
    public static function delete(string $uri, array $controller, ?callable $permission = null, array $args = []): void
    {
        self::register('DELETE', $uri, $controller, $permission, $args);
    }

    /**
     * Generic route registration method.
     *
     * Handles the actual route registration by delegating to the RestApi helper.
     * Enforces array syntax [Class::class, 'method'] for better type safety and IDE autocompletion.
     *
     * @param string        $method HTTP method (GET, POST, PUT, PATCH, DELETE)
     * @param string        $uri REST route URI
     * @param array         $controller Controller array [Class::class, 'method']
     * @param callable|null $permission Optional permission callback
     * @param array         $args Optional argument schema
     * @return void
     *
     * @example
     * ```php
     * Route::register('GET', 'wpkits/v1/status', [StatusController::class, 'index']);
     * ```
     */
    protected static function register(
        string $method,
        string $uri,
        array $controller,
        ?callable $permission,
        array $args
    ): void {
        // Delegate route registration to the centralized RestApi controller helper
        RestApi::controller($uri, $method, $controller, $permission, $args);
    }
}
