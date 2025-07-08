<?php


namespace MDMasudSikdar\WpKits\Helpers;

/**
 * Class Cron
 *
 * A helper class to simplify scheduling, clearing,
 * and hooking custom WordPress cron jobs.
 *
 * @package MDMasudSikdar\WpKits\Helpers
 */
class Cron
{
    /**
     * Schedule a cron event if not already scheduled.
     *
     * This method checks if the specified cron hook is already scheduled.
     * If not, it schedules a new event at the given timestamp and recurrence interval.
     *
     * @param string $hook The hook name for the cron event.
     * @param string $recurrence Recurrence interval such as 'hourly', 'daily', 'twicedaily' or any custom interval.
     * @param int|null $timestamp Unix timestamp for the first event. Defaults to current time if null.
     *
     * @return bool True if the event was scheduled, false if it was already scheduled.
     *
     * @usage
     * ```php
     * // Schedule a cron event to run hourly
     * Cron::schedule('my_plugin_hourly_task', 'hourly');
     * ```
     */
    public static function schedule(string $hook, string $recurrence = 'hourly', ?int $timestamp = null): bool
    {
        $timestamp = $timestamp ?? time();

        // Check if event is already scheduled
        if (!wp_next_scheduled($hook)) {
            // Schedule event
            return wp_schedule_event($timestamp, $recurrence, $hook);
        }

        // Event already scheduled
        return false;
    }

    /**
     * Unschedule all occurrences of a cron event by hook name.
     *
     * This will remove every scheduled event with the specified hook.
     *
     * @param string $hook The hook name to clear scheduled events for.
     *
     * @return void
     *
     * @usage
     * ```php
     * // Unschedule all events hooked to 'my_plugin_hourly_task'
     * Cron::clear('my_plugin_hourly_task');
     * ```
     */
    public static function clear(string $hook): void
    {
        // Get next scheduled timestamp for the hook
        $timestamp = wp_next_scheduled($hook);

        // Unschedule all instances of the hook
        while ($timestamp) {
            wp_unschedule_event($timestamp, $hook);
            $timestamp = wp_next_scheduled($hook);
        }
    }

    /**
     * Attach a callback function to a cron hook.
     *
     * This allows you to easily bind functionality to a custom cron event.
     *
     * @param string $hook The hook name to bind to.
     * @param callable $callback The callback function to execute when cron fires.
     * @param int $priority Optional. Hook priority. Default 10.
     * @param int $acceptedArgs Optional. Number of accepted args. Default 0.
     *
     * @return void
     *
     * @usage
     * ```php
     * Cron::addAction('my_plugin_hourly_task', function() {
     *     // Task code here
     * });
     * ```
     */
    public static function addAction(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 0): void
    {
        add_action($hook, $callback, $priority, $acceptedArgs);
    }

    /**
     * Add a custom cron schedule interval.
     *
     * WordPress supports 'hourly', 'daily', 'twicedaily' by default.
     * This method lets you add additional custom intervals.
     *
     * @param string $name Unique name for the interval (e.g. 'every_five_minutes').
     * @param int $interval Interval duration in seconds.
     * @param string $display Human-readable label to display in cron UI.
     * @param string $textDomain Optional. Text domain for translation. Defaults to 'wordpress-plugin-boilerplate'.
     *
     * @return void
     *
     * @usage
     * ```php
     * Cron::addCustomInterval('every_five_minutes', 300, 'Every 5 Minutes');
     *
     * // Or with custom text domain:
     * Cron::addCustomInterval('every_five_minutes', 300, 'Every 5 Minutes', 'my-plugin-textdomain');
     *
     * // Then schedule with your custom interval:
     * Cron::schedule('my_custom_hook', 'every_five_minutes');
     * ```
     */
    public static function addCustomInterval(string $name, int $interval, string $display, string $textDomain): void
    {
        add_filter('cron_schedules', function ($schedules) use ($name, $interval, $display, $textDomain) {
            if (!isset($schedules[$name])) {
                $schedules[$name] = [
                    'interval' => $interval,
                    'display' => __($display, $textDomain),
                ];
            }
            return $schedules;
        });
    }
}
