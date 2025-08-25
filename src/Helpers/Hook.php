<?php

namespace MDMasudSikdar\WpKits\Helpers;

/**
 * Class Hook
 *
 *  A powerful and extendable wrapper for WordPress hooks (actions and filters).
 *  Features:
 *  - Optional namespacing for your own hooks
 *  - One-time hooks
 *  - Conditional hooks
 *  - Hook introspection
 *  - Support for raw third-party hooks
 *
 * @package MDMasudSikdar\WpKits\Helpers
 */
class Hook
{
    /**
     * Stores all registered hooks for debugging and management.
     *
     * @var array
     */
    protected static array $hooks = [];

    /**
     * Optional namespace prefix for all hooks to avoid collisions.
     *
     * @var string
     */
    protected static string $namespace = '';

    /**
     * Set a namespace prefix for all hooks, or disable it by passing null.
     *
     * @param string|null $namespace Prefix to prepend to hook names, or null to disable.
     *
     * @example
     * ```php
     * // Enable namespace
     * Hook::setNamespace('my_plugin');
     * Hook::addAction('init', [MyClass::class, 'initHandler']); // 'my_plugin_init'
     *
     * // Disable namespace
     * Hook::setNamespace(null);
     * Hook::addAction('init', [MyClass::class, 'anotherHandler']); // 'init'
     * ```
     */
    public static function setNamespace(?string $namespace = null): void
    {
        self::$namespace = $namespace ? trim($namespace, '_') . '_' : '';
    }

    /**
     * Register an action hook with optional namespace.
     *
     * @param string $hook Hook name.
     * @param callable $callback Callback function or method.
     * @param int $priority Execution priority (default 10).
     * @param int $acceptedArgs Number of callback arguments (default 1).
     *
     * @example
     * ```php
     * Hook::addAction('init', [MyClass::class, 'initHandler']);
     * ```
     */
    public static function addAction(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        $hook = self::$namespace . $hook;
        add_action($hook, $callback, $priority, $acceptedArgs);
        self::$hooks['actions'][] = compact('hook', 'callback', 'priority', 'acceptedArgs');
    }

    /**
     * Register a filter hook with optional namespace.
     *
     * @param string $hook Hook name.
     * @param callable $callback Callback function or method.
     * @param int $priority Execution priority (default 10).
     * @param int $acceptedArgs Number of callback arguments (default 1).
     *
     * @example
     * ```php
     * Hook::addFilter('the_content', function ($content) {
     *     return $content . ' Extra content!';
     * });
     * ```
     */
    public static function addFilter(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        $hook = self::$namespace . $hook;
        add_filter($hook, $callback, $priority, $acceptedArgs);
        self::$hooks['filters'][] = compact('hook', 'callback', 'priority', 'acceptedArgs');
    }

    /**
     * Register a one-time action that removes itself after execution.
     *
     * @param string $hook Hook name.
     * @param callable $callback Callback function or method.
     * @param int $priority Execution priority (default 10).
     * @param int $acceptedArgs Number of callback arguments (default 1).
     *
     * @example
     * ```php
     * Hook::addActionOnce('wp_loaded', function () {
     *     error_log('This runs only once.');
     * });
     * ```
     */
    public static function addActionOnce(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        $hook = self::$namespace . $hook;

        $wrapper = function (...$args) use ($hook, $callback, $priority) {
            remove_action($hook, $callback, $priority);
            call_user_func_array($callback, $args);
        };

        self::addAction($hook, $wrapper, $priority, $acceptedArgs);
    }

    /**
     * Remove an action hook.
     *
     * @param string $hook Hook name.
     * @param callable $callback Callback to remove.
     * @param int $priority Priority used during registration.
     */
    public static function removeAction(string $hook, callable $callback, int $priority = 10): void
    {
        $hook = self::$namespace . $hook;
        remove_action($hook, $callback, $priority);
    }

    /**
     * Remove a filter hook.
     *
     * @param string $hook Hook name.
     * @param callable $callback Callback to remove.
     * @param int $priority Priority used during registration.
     */
    public static function removeFilter(string $hook, callable $callback, int $priority = 10): void
    {
        $hook = self::$namespace . $hook;
        remove_filter($hook, $callback, $priority);
    }

    /**
     * Execute an action immediately.
     *
     * @param string $hook Hook name.
     * @param mixed ...$args Arguments to pass to callback(s).
     */
    public static function doAction(string $hook, ...$args): void
    {
        $hook = self::$namespace . $hook;
        do_action($hook, ...$args);
    }

    /**
     * Apply filters to a value.
     *
     * @param string $hook Hook name.
     * @param mixed $value Value to filter.
     * @param mixed ...$args Additional arguments for callbacks.
     * @return mixed Filtered value.
     */
    public static function applyFilters(string $hook, $value, ...$args): mixed
    {
        $hook = self::$namespace . $hook;
        return apply_filters($hook, $value, ...$args);
    }

    /**
     * Get all registered hooks for debugging or introspection.
     *
     * @return array Associative array of actions and filters.
     */
    public static function getRegisteredHooks(): array
    {
        return self::$hooks;
    }

    /**
     * Conditionally register an action.
     *
     * @param string $hook Hook name.
     * @param callable $callback Callback function or method.
     * @param bool $condition Condition to decide registration.
     * @param int $priority Execution priority (default 10).
     * @param int $acceptedArgs Number of callback arguments (default 1).
     */
    public static function addActionIf(string $hook, callable $callback, bool $condition, int $priority = 10, int $acceptedArgs = 1): void
    {
        if ($condition) {
            self::addAction($hook, $callback, $priority, $acceptedArgs);
        }
    }

    /**
     * Conditionally register a filter.
     *
     * @param string $hook Hook name.
     * @param callable $callback Callback function or method.
     * @param bool $condition Condition to decide registration.
     * @param int $priority Execution priority (default 10).
     * @param int $acceptedArgs Number of callback arguments (default 1).
     */
    public static function addFilterIf(string $hook, callable $callback, bool $condition, int $priority = 10, int $acceptedArgs = 1): void
    {
        if ($condition) {
            self::addFilter($hook, $callback, $priority, $acceptedArgs);
        }
    }

    /**
     * Register an action **without applying namespace** (for third-party/core hooks).
     *
     * @param string $hook Hook name.
     * @param callable $callback Callback function or method.
     * @param int $priority Execution priority (default 10).
     * @param int $acceptedArgs Number of callback arguments (default 1).
     */
    public static function addActionRaw(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        add_action($hook, $callback, $priority, $acceptedArgs);
        self::$hooks['actions'][] = compact('hook', 'callback', 'priority', 'acceptedArgs');
    }

    /**
     * Register a filter **without applying namespace** (for third-party/core hooks).
     *
     * @param string $hook Hook name.
     * @param callable $callback Callback function or method.
     * @param int $priority Execution priority (default 10).
     * @param int $acceptedArgs Number of callback arguments (default 1).
     */
    public static function addFilterRaw(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        add_filter($hook, $callback, $priority, $acceptedArgs);
        self::$hooks['filters'][] = compact('hook', 'callback', 'priority', 'acceptedArgs');
    }
}
