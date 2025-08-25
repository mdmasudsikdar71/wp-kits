<?php

namespace MDMasudSikdar\WpKits\Helpers;

use MDMasudSikdar\WpKits\Traits\SingletonTrait;

/**
 * Class CronManager
 *
 * Advanced WordPress Cron Manager with:
 * ✅ Singleton pattern via SingletonTrait
 * ✅ Static-like access after initialization
 * ✅ Namespaced cron hooks to avoid collisions
 * ✅ Automatic binding of callbacks
 * ✅ Conditional scheduling support
 * ✅ Batch scheduling and clearing
 * ✅ Optional logging for all actions
 *
 * Usage:
 * ```php
 * // Initialize once with plugin prefix
 * CronManager::init()->setPrefix('my_plugin');
 *
 * // Anywhere later in the plugin
 * CronManager::scheduleAll();
 * CronManager::register('hourly_task', 'hourly', [$this, 'runTask']);
 * ```
 *
 * @package MDMasudSikdar\WpKits\Helpers
 */
class CronManager
{
    use SingletonTrait;

    /**
     * Plugin prefix used to namespace cron hooks.
     * Default is 'plugin', but should be customized in init.
     *
     * @var string
     */
    protected string $prefix = 'plugin_cron';

    /**
     * Stores all registered cron events.
     * Each event contains hook, recurrence, callback, timestamp, log flag, and condition.
     *
     * @var array<int, array{hook:string, recurrence:string, callback:callable|null, timestamp:int|null, log:bool, condition:callable|null}>
     */
    protected array $events = [];

    /**
     * Set the plugin prefix for namespacing cron hooks.
     *
     * Must be called once after init() for proper namespacing.
     *
     * @param string $prefix
     * @return void
     *
     * @example
     * ```php
     * CronManager::init()->setPrefix('my_plugin');
     * ```
     */
    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }

    /**
     * Register a cron event.
     *
     * This method stores the event, optionally binds the callback,
     * and allows conditional scheduling and logging.
     *
     * @param string $hook Hook name (without prefix)
     * @param string $recurrence Recurrence interval ('hourly', 'daily', etc.)
     * @param callable|null $callback Optional function to execute when cron fires
     * @param int|null $timestamp First run timestamp, default is current time
     * @param bool $log Whether to log scheduling actions
     * @param callable|null $condition Optional callable returning bool to decide scheduling
     *
     * @return void
     *
     * @example
     * ```php
     * CronManager::register(
     *     'hourly_task',
     *     'hourly',
     *     [$this, 'runTask'],
     *     null,
     *     true,
     *     function() {
     *         return get_option('enable_task') === 'yes';
     *     }
     * );
     * ```
     */
    public function register(
        string $hook,
        string $recurrence = 'hourly',
        ?callable $callback = null,
        ?int $timestamp = null,
        bool $log = false,
        ?callable $condition = null
    ): void {
        // Apply plugin prefix for namespacing
        $namespacedHook = $this->prefix . '_' . $hook;

        // Store the event in internal events array
        $this->events[] = [
            'hook'       => $namespacedHook,
            'recurrence' => $recurrence,
            'callback'   => $callback,
            'timestamp'  => $timestamp,
            'log'        => $log,
            'condition'  => $condition,
        ];

        // Bind callback immediately if provided
        if ($callback) {
            Hook::addActionRaw($namespacedHook, $callback);
        }
    }

    /**
     * Schedule all registered events that meet their conditions.
     *
     * This method checks each event for a condition, and schedules it
     * if not already scheduled. Optional logging is performed.
     *
     * @return void
     *
     * @example
     * ```php
     * CronManager::scheduleAll();
     * ```
     */
    public function scheduleAll(): void
    {
        foreach ($this->events as $event) {
            // Skip scheduling if condition exists and returns false
            if ($event['condition'] && !$event['condition']()) {
                continue;
            }

            // Use provided timestamp or current time
            $timestamp = $event['timestamp'] ?? time();

            // Schedule only if not already scheduled
            if (!wp_next_scheduled($event['hook'])) {
                wp_schedule_event($timestamp, $event['recurrence'], $event['hook']);

                // Log scheduling if requested
                if ($event['log']) {
                    Logger::info(
                        "Cron '{$event['hook']}' scheduled for '{$event['recurrence']}' starting at {$timestamp}."
                    );
                }
            }
        }
    }

    /**
     * Unschedule all registered events.
     *
     * Loops through all events and removes all scheduled occurrences.
     *
     * @param bool $log Whether to log unscheduling actions
     * @return void
     *
     * @example
     * ```php
     * CronManager::clearAll(true);
     * ```
     */
    public function clearAll(bool $log = false): void
    {
        foreach ($this->events as $event) {
            // Get next scheduled timestamp
            $timestamp = wp_next_scheduled($event['hook']);

            // Remove all occurrences
            while ($timestamp) {
                wp_unschedule_event($timestamp, $event['hook']);
                $timestamp = wp_next_scheduled($event['hook']);
            }

            // Log clearing if requested
            if ($log) {
                Logger::info("All occurrences of cron '{$event['hook']}' cleared.");
            }
        }
    }

    /**
     * Add a custom cron interval.
     *
     * @param string $name Unique interval name
     * @param int $interval Interval in seconds
     * @param string $display Human-readable label
     * @param string $textDomain Optional translation text domain
     * @return void
     *
     * @example
     * ```php
     * CronManager::addCustomInterval('every_five_minutes', 300, 'Every 5 Minutes');
     * ```
     */
    public function addCustomInterval(string $name, int $interval, string $display, string $textDomain = 'wordpress-plugin-boilerplate'): void
    {
        Hook::addFilterRaw('cron_schedules', function ($schedules) use ($name, $interval, $display, $textDomain) {
            // Only add if not already defined
            if (!isset($schedules[$name])) {
                $schedules[$name] = [
                    'interval' => $interval,
                    'display'  => __($display, $textDomain),
                ];
            }
            return $schedules;
        });
    }

    /**
     * Check if a specific namespaced cron event is scheduled.
     *
     * @param string $hook Hook name without prefix
     * @return bool True if scheduled, false otherwise
     *
     * @example
     * ```php
     * if (CronManager::isScheduled('hourly_task')) {
     *     // Do something
     * }
     * ```
     */
    public function isScheduled(string $hook): bool
    {
        $namespacedHook = $this->prefix . '_' . $hook;
        return (bool) wp_next_scheduled($namespacedHook);
    }

    /**
     * Magic static forwarder.
     *
     * Allows calling instance methods statically like:
     * CronManager::scheduleAll() after init()
     *
     * @param string $name Method name
     * @param array $arguments Method arguments
     * @return mixed
     */
    public static function __callStatic(string $name, array $arguments)
    {
        $instance = static::init();
        if (method_exists($instance, $name)) {
            return $instance->$name(...$arguments);
        }

        throw new \BadMethodCallException("Method {$name} does not exist.");
    }
}
