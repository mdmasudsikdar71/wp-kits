<?php


namespace MDMasudSikdar\WpKits\Helpers;

/**
 * Class Hook
 *
 * Provides static utility methods for managing WordPress actions and filters
 * with a consistent and clean API.
 *
 * @package MDMasudSikdar\WpKits\Helpers
 */
class Hook
{
    /**
     * Register an action hook.
     *
     * @param string $hook The hook name.
     * @param callable $callback The callback to run.
     * @param int $priority Priority of execution.
     * @param int $acceptedArgs Number of arguments the callback accepts.
     *
     * @example
     * ```php
     * Hook::addAction('init', [MyClass::class, 'initHandler']);
     * Hook::addAction('admin_notices', function () {
     *     echo 'Hello Admin!';
     * });
     * ```
     */
    public static function addAction(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        add_action($hook, $callback, $priority, $acceptedArgs);
    }

    /**
     * Register a filter hook.
     *
     * @param string $hook The filter name.
     * @param callable $callback The callback to run.
     * @param int $priority Priority of execution.
     * @param int $acceptedArgs Number of arguments the callback accepts.
     *
     * @example
     * ```php
     * Hook::addFilter('the_content', [MyClass::class, 'filterContent']);
     * Hook::addFilter('plugin_action_links', function ($links) {
     *     $links[] = '<a href="settings.php">Settings</a>';
     *     return $links;
     * });
     * ```
     */
    public static function addFilter(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        add_filter($hook, $callback, $priority, $acceptedArgs);
    }

    /**
     * Remove an action hook.
     *
     * @param string $hook
     * @param callable $callback
     * @param int $priority
     *
     * @example
     * ```php
     * Hook::removeAction('init', [MyClass::class, 'initHandler']);
     * ```
     */
    public static function removeAction(string $hook, callable $callback, int $priority = 10): void
    {
        remove_action($hook, $callback, $priority);
    }

    /**
     * Remove a filter hook.
     *
     * @param string $hook
     * @param callable $callback
     * @param int $priority
     *
     * @example
     * ```php
     * Hook::removeFilter('the_content', [MyClass::class, 'filterContent']);
     * ```
     */
    public static function removeFilter(string $hook, callable $callback, int $priority = 10): void
    {
        remove_filter($hook, $callback, $priority);
    }

    /**
     * Execute an action immediately.
     *
     * @param string $hook
     * @param mixed ...$args Arguments to pass to action callback.
     *
     * @example
     * ```php
     * Hook::doAction('my_custom_hook', $arg1, $arg2);
     * ```
     */
    public static function doAction(string $hook, ...$args): void
    {
        do_action($hook, ...$args);
    }

    /**
     * Apply filters to a value.
     *
     * @param string $hook
     * @param mixed $value
     * @param mixed ...$args
     * @return mixed
     *
     * @example
     * ```php
     * $value = Hook::applyFilters('my_filter', $originalValue);
     * ```
     */
    public static function applyFilters(string $hook, $value, ...$args): mixed
    {
        return apply_filters($hook, $value, ...$args);
    }
}
