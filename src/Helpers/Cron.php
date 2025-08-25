<?php

namespace MDMasudSikdar\WpKits\Helpers;

use MDMasudSikdar\WpKits\Traits\SingletonTrait;

/**
 * Class Cron
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
 * Cron::init()->setPrefix('my_plugin');
 *
 * // Anywhere later in the plugin
 * Cron::scheduleAll();
 * Cron::register('hourly_task', 'hourly', [$this, 'runTask']);
 * ```
 *
 * @package MDMasudSikdar\WpKits\Helpers
 */
class Cron
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
     * Cron::init()->setPrefix('my_plugin');
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
     * Cron::register(
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
     * Cron::scheduleAll();
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
     * Cron::clearAll(true);
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
     * Cron::addCustomInterval('every_five_minutes', 300, 'Every 5 Minutes');
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
     * if (Cron::isScheduled('hourly_task')) {
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
     * Cron::scheduleAll() after init()
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

    /**
     * Remove a specific scheduled cron event.
     *
     * Unschedules all occurrences of a given hook.
     *
     * @param string $hook Hook name without prefix
     * @param bool $log Whether to log the removal
     * @return void
     *
     * @example
     * ```php
     * Cron::remove('hourly_task', true);
     * ```
     */
    public function remove(string $hook, bool $log = false): void
    {
        $namespacedHook = $this->prefix . '_' . $hook;

        $timestamp = wp_next_scheduled($namespacedHook);

        while ($timestamp) {
            wp_unschedule_event($timestamp, $namespacedHook);
            $timestamp = wp_next_scheduled($namespacedHook);
        }

        if ($log) {
            Logger::info("All occurrences of cron '{$namespacedHook}' removed.");
        }
    }

    /**
     * Reschedule an existing cron event with new recurrence or timestamp.
     *
     * @param string $hook Hook name without prefix
     * @param string|null $recurrence New recurrence (optional)
     * @param int|null $timestamp New first run timestamp (optional)
     * @param bool $log Whether to log the action
     * @return void
     *
     * @example
     * ```php
     * Cron::reschedule('hourly_task', 'daily', time() + 3600, true);
     * ```
     */
    public function reschedule(string $hook, ?string $recurrence = null, ?int $timestamp = null, bool $log = false): void
    {
        $namespacedHook = $this->prefix . '_' . $hook;

        // Unschedule all current occurrences
        $currentTimestamp = wp_next_scheduled($namespacedHook);
        while ($currentTimestamp) {
            wp_unschedule_event($currentTimestamp, $namespacedHook);
            $currentTimestamp = wp_next_scheduled($namespacedHook);
        }

        // Find original event
        foreach ($this->events as &$event) {
            if ($event['hook'] === $namespacedHook) {
                $newRecurrence = $recurrence ?? $event['recurrence'];
                $newTimestamp  = $timestamp  ?? $event['timestamp'] ?? time();

                wp_schedule_event($newTimestamp, $newRecurrence, $namespacedHook);

                if ($log) {
                    Logger::info("Cron '{$namespacedHook}' rescheduled to '{$newRecurrence}' starting at {$newTimestamp}.");
                }
                break;
            }
        }
    }

    /**
     * Get all scheduled events with next run timestamp.
     *
     * @return array<string, int|null> Hook => next scheduled timestamp
     *
     * @example
     * ```php
     * $events = Cron::getScheduledEvents();
     * ```
     */
    public function getScheduledEvents(): array
    {
        $scheduled = [];
        foreach ($this->events as $event) {
            $scheduled[$event['hook']] = wp_next_scheduled($event['hook']);
        }
        return $scheduled;
    }

    /**
     * Update the callback of a registered cron event.
     *
     * @param string $hook Hook name without prefix
     * @param callable $callback New callback function
     * @param bool $log Whether to log the change
     * @return void
     *
     * @example
     * ```php
     * Cron::updateCallback('hourly_task', [$this, 'newTask'], true);
     * ```
     */
    public function updateCallback(string $hook, callable $callback, bool $log = false): void
    {
        $namespacedHook = $this->prefix . '_' . $hook;

        foreach ($this->events as &$event) {
            if ($event['hook'] === $namespacedHook) {
                $event['callback'] = $callback;

                // Rebind action
                Hook::addActionRaw($namespacedHook, $callback);

                if ($log) {
                    Logger::info("Callback for cron '{$namespacedHook}' updated.");
                }
                break;
            }
        }
    }

    /**
     * Check if a hook is registered in events array (regardless of scheduling).
     *
     * @param string $hook Hook name without prefix
     * @return bool
     *
     * @example
     * ```php
     * if (Cron::hasHook('hourly_task')) { ... }
     * ```
     */
    public function hasHook(string $hook): bool
    {
        $namespacedHook = $this->prefix . '_' . $hook;
        foreach ($this->events as $event) {
            if ($event['hook'] === $namespacedHook) {
                return true;
            }
        }
        return false;
    }

    /**
     * Remove a hook from the internal events array only.
     * Does NOT unschedule the cron event.
     *
     * @param string $hook Hook name without prefix
     * @param bool $log Whether to log the removal
     * @return void
     *
     * @example
     * ```php
     * Cron::clearHook('hourly_task', true);
     * ```
     */
    public function clearHook(string $hook, bool $log = false): void
    {
        $namespacedHook = $this->prefix . '_' . $hook;

        $this->events = array_filter($this->events, function ($event) use ($namespacedHook) {
            return $event['hook'] !== $namespacedHook;
        });

        if ($log) {
            Logger::info("Hook '{$namespacedHook}' removed from internal events array.");
        }
    }

    /**
     * Schedule a specific cron event only if a condition callback returns true.
     *
     * @param string $hook Hook name without prefix
     * @param callable $condition Condition callback that returns bool
     * @param bool $log Whether to log the action
     * @return void
     *
     * @example
     * ```php
     * Cron::scheduleIf('hourly_task', fn() => get_option('enable_task') === 'yes', true);
     * ```
     */
    public function scheduleIf(string $hook, callable $condition, bool $log = false): void
    {
        $namespacedHook = $this->prefix . '_' . $hook;
        if ($condition()) {
            $event = array_filter($this->events, fn($e) => $e['hook'] === $namespacedHook);
            $event = reset($event);
            if ($event && !wp_next_scheduled($namespacedHook)) {
                $timestamp = $event['timestamp'] ?? time();
                wp_schedule_event($timestamp, $event['recurrence'], $namespacedHook);
                if ($log) {
                    Logger::info("Cron '{$namespacedHook}' scheduled conditionally at {$timestamp}.");
                }
            }
        }
    }

    /**
     * Schedule multiple cron hooks at once.
     *
     * @param array<string> $hooks List of hook names without prefix
     * @param bool $log Whether to log each scheduling
     * @return void
     *
     * @example
     * ```php
     * Cron::scheduleBatch(['hourly_task', 'daily_task'], true);
     * ```
     */
    public function scheduleBatch(array $hooks, bool $log = false): void
    {
        foreach ($hooks as $hook) {
            if ($this->hasHook($hook)) {
                $this->scheduleIf($hook, fn() => true, $log);
            }
        }
    }

    /**
     * Unschedule multiple cron hooks at once.
     *
     * @param array<string> $hooks List of hook names without prefix
     * @param bool $log Whether to log each removal
     * @return void
     *
     * @example
     * ```php
     * Cron::clearBatch(['hourly_task', 'daily_task'], true);
     * ```
     */
    public function clearBatch(array $hooks, bool $log = false): void
    {
        foreach ($hooks as $hook) {
            $this->remove($hook, $log);
        }
    }

    /**
     * Get detailed information of a specific registered cron event.
     *
     * @param string $hook Hook name without prefix
     * @return array|null Event data or null if not found
     *
     * @example
     * ```php
     * $info = Cron::getHookInfo('hourly_task');
     * ```
     */
    public function getHookInfo(string $hook): ?array
    {
        $namespacedHook = $this->prefix . '_' . $hook;
        foreach ($this->events as $event) {
            if ($event['hook'] === $namespacedHook) {
                return $event;
            }
        }
        return null;
    }

    /**
     * Log all registered events with next scheduled timestamps.
     *
     * @return void
     *
     * @example
     * ```php
     * Cron::logAllEvents();
     * ```
     */
    public function logAllEvents(): void
    {
        foreach ($this->events as $event) {
            $next = wp_next_scheduled($event['hook']) ?: 'not scheduled';
            Logger::info("Cron '{$event['hook']}': Recurrence='{$event['recurrence']}', Next run={$next}");
        }
    }

    /**
     * Add multiple custom cron intervals at once.
     *
     * @param array<string, array{interval:int, display:string}> $intervals Key = interval name, value = ['interval'=>seconds, 'display'=>label]
     * @param string $textDomain Optional translation text domain
     * @return void
     *
     * @example
     * ```php
     * Cron::addCustomIntervals([
     *     'every_two_minutes' => ['interval'=>120, 'display'=>'Every 2 Minutes'],
     *     'every_fifteen_minutes' => ['interval'=>900, 'display'=>'Every 15 Minutes']
     * ]);
     * ```
     */
    public function addCustomIntervals(array $intervals, string $textDomain = 'wordpress-plugin-boilerplate'): void
    {
        Hook::addFilterRaw('cron_schedules', function ($schedules) use ($intervals, $textDomain) {
            foreach ($intervals as $name => $data) {
                if (!isset($schedules[$name])) {
                    $schedules[$name] = [
                        'interval' => $data['interval'],
                        'display'  => __($data['display'], $textDomain),
                    ];
                }
            }
            return $schedules;
        });
    }

    /**
     * Reschedule all registered cron events (keeps original recurrence or timestamp).
     *
     * @param bool $log Whether to log each action
     * @return void
     *
     * @example
     * ```php
     * Cron::rescheduleAll(true);
     * ```
     */
    public function rescheduleAll(bool $log = false): void
    {
        foreach ($this->events as $event) {
            $this->reschedule(
                str_replace($this->prefix . '_', '', $event['hook']),
                $event['recurrence'],
                $event['timestamp'] ?? null,
                $log
            );
        }
    }

    /**
     * Schedule all registered events that are not yet scheduled (missed or new).
     *
     * @param bool $log Whether to log each scheduling
     * @return void
     *
     * @example
     * ```php
     * Cron::scheduleMissed(true);
     * ```
     */
    public function scheduleMissed(bool $log = false): void
    {
        foreach ($this->events as $event) {
            if (!wp_next_scheduled($event['hook'])) {
                $timestamp = $event['timestamp'] ?? time();
                wp_schedule_event($timestamp, $event['recurrence'], $event['hook']);
                if ($log) {
                    Logger::info("Missed cron '{$event['hook']}' scheduled at {$timestamp}.");
                }
            }
        }
    }

    /**
     * Get the next scheduled timestamp for a specific hook.
     *
     * @param string $hook Hook name without prefix
     * @return int|null Timestamp or null if not scheduled
     *
     * @example
     * ```php
     * $nextRun = Cron::getNextRun('hourly_task');
     * ```
     */
    public function getNextRun(string $hook): ?int
    {
        $namespacedHook = $this->prefix . '_' . $hook;
        return wp_next_scheduled($namespacedHook) ?: null;
    }

    /**
     * Check if a hook is both registered and currently scheduled.
     *
     * @param string $hook Hook name without prefix
     * @return bool True if registered and scheduled
     *
     * @example
     * ```php
     * if (Cron::isHookActive('hourly_task')) { ... }
     * ```
     */
    public function isHookActive(string $hook): bool
    {
        return $this->hasHook($hook) && $this->isScheduled($hook);
    }

    /**
     * Register a cron event with arguments for the callback.
     *
     * @param string $hook Hook name without prefix
     * @param string $recurrence Recurrence interval
     * @param callable|null $callback Callback function
     * @param array $args Arguments to pass to the callback
     * @param int|null $timestamp First run timestamp
     * @param bool $log Whether to log the registration
     * @param callable|null $condition Optional scheduling condition
     * @return void
     *
     * @example
     * ```php
     * Cron::registerWithArgs('task_with_args', 'hourly', [$this, 'runTask'], ['param1', 'param2'], null, true);
     * ```
     */
    public function registerWithArgs(
        string $hook,
        string $recurrence = 'hourly',
        ?callable $callback = null,
        array $args = [],
        ?int $timestamp = null,
        bool $log = false,
        ?callable $condition = null
    ): void {
        $namespacedHook = $this->prefix . '_' . $hook;

        $this->events[] = [
            'hook'       => $namespacedHook,
            'recurrence' => $recurrence,
            'callback'   => $callback,
            'args'       => $args,
            'timestamp'  => $timestamp,
            'log'        => $log,
            'condition'  => $condition,
        ];

        if ($callback) {
            Hook::addActionRaw($namespacedHook, fn() => call_user_func_array($callback, $args));
        }

        if ($log) {
            Logger::info("Cron '{$namespacedHook}' registered with arguments.");
        }
    }

    /**
     * Update the arguments of a registered cron event.
     *
     * @param string $hook Hook name without prefix
     * @param array $args New arguments array
     * @param bool $log Whether to log the update
     * @return void
     *
     * @example
     * ```php
     * Cron::updateArgs('task_with_args', ['new1', 'new2'], true);
     * ```
     */
    public function updateArgs(string $hook, array $args, bool $log = false): void
    {
        $namespacedHook = $this->prefix . '_' . $hook;

        foreach ($this->events as &$event) {
            if ($event['hook'] === $namespacedHook) {
                $event['args'] = $args;

                if ($event['callback']) {
                    Hook::addActionRaw($namespacedHook, fn() => call_user_func_array($event['callback'], $args));
                }

                if ($log) {
                    Logger::info("Arguments for cron '{$namespacedHook}' updated.");
                }
                break;
            }
        }
    }

    /**
     * Disable logging for a specific registered cron event.
     *
     * @param string $hook Hook name without prefix
     * @return void
     *
     * @example
     * ```php
     * Cron::disableLogging('hourly_task');
     * ```
     */
    public function disableLogging(string $hook): void
    {
        $namespacedHook = $this->prefix . '_' . $hook;

        foreach ($this->events as &$event) {
            if ($event['hook'] === $namespacedHook) {
                $event['log'] = false;
                break;
            }
        }
    }

    /**
     * Enable logging for a specific registered cron event.
     *
     * @param string $hook Hook name without prefix
     * @return void
     *
     * @example
     * ```php
     * Cron::enableLogging('hourly_task');
     * ```
     */
    public function enableLogging(string $hook): void
    {
        $namespacedHook = $this->prefix . '_' . $hook;

        foreach ($this->events as &$event) {
            if ($event['hook'] === $namespacedHook) {
                $event['log'] = true;
                break;
            }
        }
    }

    /**
     * Completely reset all cron events: unschedule, clear internal storage, and optionally log.
     *
     * @param bool $log Whether to log the reset actions
     * @return void
     *
     * @example
     * ```php
     * Cron::resetAll(true);
     * ```
     */
    public function resetAll(bool $log = false): void
    {
        foreach ($this->events as $event) {
            $timestamp = wp_next_scheduled($event['hook']);
            while ($timestamp) {
                wp_unschedule_event($timestamp, $event['hook']);
                $timestamp = wp_next_scheduled($event['hook']);
            }
            if ($log) {
                Logger::info("Cron '{$event['hook']}' completely removed.");
            }
        }

        $this->events = [];
    }

    /**
     * Return all registered cron events with full details for debugging.
     *
     * @return array<int, array> List of all events with hook, recurrence, callback, args, timestamp, log, and condition
     *
     * @example
     * ```php
     * $events = Cron::dumpEvents();
     * ```
     */
    public function dumpEvents(): array
    {
        return $this->events;
    }

    /**
     * Check if there are any hooks currently scheduled.
     *
     * @return bool True if at least one hook is scheduled
     *
     * @example
     * ```php
     * if (Cron::hasScheduledHooks()) { ... }
     * ```
     */
    public function hasScheduledHooks(): bool
    {
        foreach ($this->events as $event) {
            if (wp_next_scheduled($event['hook'])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Log all currently scheduled hooks with their next run timestamps.
     *
     * @return void
     *
     * @example
     * ```php
     * Cron::logScheduledHooks();
     * ```
     */
    public function logScheduledHooks(): void
    {
        foreach ($this->events as $event) {
            $next = wp_next_scheduled($event['hook']);
            if ($next) {
                Logger::info("Scheduled cron '{$event['hook']}': Next run={$next}");
            }
        }
    }

    /**
     * Schedule a cron event to run after a specific delay in seconds.
     *
     * @param string $hook Hook name without prefix
     * @param int $delay Seconds to wait before first run
     * @param bool $log Whether to log the action
     * @return void
     *
     * @example
     * ```php
     * Cron::scheduleAfter('hourly_task', 600, true);
     * ```
     */
    public function scheduleAfter(string $hook, int $delay, bool $log = false): void
    {
        $namespacedHook = $this->prefix . '_' . $hook;
        $timestamp = time() + $delay;
        $event = array_filter($this->events, fn($e) => $e['hook'] === $namespacedHook);
        $event = reset($event);
        if ($event && !wp_next_scheduled($namespacedHook)) {
            wp_schedule_event($timestamp, $event['recurrence'], $namespacedHook);
            if ($log) {
                Logger::info("Cron '{$namespacedHook}' scheduled after {$delay} seconds at {$timestamp}.");
            }
        }
    }

    /**
     * Schedule a cron event at a specific UNIX timestamp.
     *
     * @param string $hook Hook name without prefix
     * @param int $timestamp UNIX timestamp for first run
     * @param bool $log Whether to log the action
     * @return void
     *
     * @example
     * ```php
     * Cron::scheduleAt('hourly_task', strtotime('tomorrow 9am'), true);
     * ```
     */
    public function scheduleAt(string $hook, int $timestamp, bool $log = false): void
    {
        $namespacedHook = $this->prefix . '_' . $hook;
        $event = array_filter($this->events, fn($e) => $e['hook'] === $namespacedHook);
        $event = reset($event);
        if ($event && !wp_next_scheduled($namespacedHook)) {
            wp_schedule_event($timestamp, $event['recurrence'], $namespacedHook);
            if ($log) {
                Logger::info("Cron '{$namespacedHook}' scheduled at {$timestamp}.");
            }
        }
    }

    /**
     * Get all hooks that are currently not scheduled but registered.
     *
     * @return array<int, string> List of hook names (without prefix)
     *
     * @example
     * ```php
     * $unscheduled = Cron::getUnscheduledHooks();
     * ```
     */
    public function getUnscheduledHooks(): array
    {
        $unscheduled = [];
        foreach ($this->events as $event) {
            if (!wp_next_scheduled($event['hook'])) {
                $unscheduled[] = str_replace($this->prefix . '_', '', $event['hook']);
            }
        }
        return $unscheduled;
    }

    /**
     * Temporarily disable a hook (unschedule it without removing from events array).
     *
     * @param string $hook Hook name without prefix
     * @param bool $log Whether to log the action
     * @return void
     *
     * @example
     * ```php
     * Cron::disableHook('hourly_task', true);
     * ```
     */
    public function disableHook(string $hook, bool $log = false): void
    {
        $namespacedHook = $this->prefix . '_' . $hook;
        $timestamp = wp_next_scheduled($namespacedHook);
        while ($timestamp) {
            wp_unschedule_event($timestamp, $namespacedHook);
            $timestamp = wp_next_scheduled($namespacedHook);
        }
        if ($log) {
            Logger::info("Cron '{$namespacedHook}' temporarily disabled.");
        }
    }

    /**
     * Enable a previously disabled hook (reschedule it with its original recurrence).
     *
     * @param string $hook Hook name without prefix
     * @param bool $log Whether to log the action
     * @return void
     *
     * @example
     * ```php
     * Cron::enableHook('hourly_task', true);
     * ```
     */
    public function enableHook(string $hook, bool $log = false): void
    {
        $namespacedHook = $this->prefix . '_' . $hook;
        $event = array_filter($this->events, fn($e) => $e['hook'] === $namespacedHook);
        $event = reset($event);
        if ($event && !wp_next_scheduled($namespacedHook)) {
            $timestamp = $event['timestamp'] ?? time();
            wp_schedule_event($timestamp, $event['recurrence'], $namespacedHook);
            if ($log) {
                Logger::info("Cron '{$namespacedHook}' re-enabled and scheduled at {$timestamp}.");
            }
        }
    }

    /**
     * Update recurrence for a registered cron hook.
     *
     * @param string $hook Hook name without prefix
     * @param string $recurrence New recurrence interval
     * @param bool $log Whether to log the update
     * @return void
     *
     * @example
     * ```php
     * Cron::updateRecurrence('hourly_task', 'daily', true);
     * ```
     */
    public function updateRecurrence(string $hook, string $recurrence, bool $log = false): void
    {
        $namespacedHook = $this->prefix . '_' . $hook;
        foreach ($this->events as &$event) {
            if ($event['hook'] === $namespacedHook) {
                $event['recurrence'] = $recurrence;
                $this->reschedule($hook, $recurrence, $event['timestamp'] ?? null, $log);
                break;
            }
        }
    }

    /**
     * Update timestamp for a registered cron hook.
     *
     * @param string $hook Hook name without prefix
     * @param int $timestamp New first run timestamp
     * @param bool $log Whether to log the update
     * @return void
     *
     * @example
     * ```php
     * Cron::updateTimestamp('hourly_task', time() + 3600, true);
     * ```
     */
    public function updateTimestamp(string $hook, int $timestamp, bool $log = false): void
    {
        $namespacedHook = $this->prefix . '_' . $hook;
        foreach ($this->events as &$event) {
            if ($event['hook'] === $namespacedHook) {
                $event['timestamp'] = $timestamp;
                $this->reschedule($hook, $event['recurrence'], $timestamp, $log);
                break;
            }
        }
    }

    /**
     * Schedule multiple hooks only if a shared condition is true.
     *
     * @param array<string> $hooks List of hook names without prefix
     * @param callable $condition Condition callback returning bool
     * @param bool $log Whether to log each scheduling
     * @return void
     *
     * @example
     * ```php
     * Cron::scheduleBatchIf(['hourly_task', 'daily_task'], fn() => get_option('enable_all') === 'yes', true);
     * ```
     */
    public function scheduleBatchIf(array $hooks, callable $condition, bool $log = false): void
    {
        if ($condition()) {
            foreach ($hooks as $hook) {
                if ($this->hasHook($hook)) {
                    $this->scheduleIf($hook, fn() => true, $log);
                }
            }
        }
    }

    /**
     * Get all hooks that are currently enabled (scheduled).
     *
     * @return array<int, string> List of hook names without prefix
     *
     * @example
     * ```php
     * $enabledHooks = Cron::getEnabledHooks();
     * ```
     */
    public function getEnabledHooks(): array
    {
        $enabled = [];
        foreach ($this->events as $event) {
            if (wp_next_scheduled($event['hook'])) {
                $enabled[] = str_replace($this->prefix . '_', '', $event['hook']);
            }
        }
        return $enabled;
    }

    /**
     * Get all hooks that are currently disabled (registered but not scheduled).
     *
     * @return array<int, string> List of hook names without prefix
     *
     * @example
     * ```php
     * $disabledHooks = Cron::getDisabledHooks();
     * ```
     */
    public function getDisabledHooks(): array
    {
        $disabled = [];
        foreach ($this->events as $event) {
            if (!wp_next_scheduled($event['hook'])) {
                $disabled[] = str_replace($this->prefix . '_', '', $event['hook']);
            }
        }
        return $disabled;
    }

    /**
     * Log a custom message related to a specific hook.
     *
     * @param string $hook Hook name without prefix
     * @param string $message Custom message
     * @return void
     *
     * @example
     * ```php
     * Cron::logHookMessage('hourly_task', 'Custom info message');
     * ```
     */
    public function logHookMessage(string $hook, string $message): void
    {
        $namespacedHook = $this->prefix . '_' . $hook;
        Logger::info("Cron '{$namespacedHook}': {$message}");
    }

    /**
     * Return a summary of all hooks with status (scheduled or not) and next timestamp.
     *
     * @return array<int, array{hook:string, scheduled:bool, next:int|null}>
     *
     * @example
     * ```php
     * $summary = Cron::getHooksSummary();
     * ```
     */
    public function getHooksSummary(): array
    {
        $summary = [];
        foreach ($this->events as $event) {
            $next = wp_next_scheduled($event['hook']);
            $summary[] = [
                'hook'      => str_replace($this->prefix . '_', '', $event['hook']),
                'scheduled' => (bool)$next,
                'next'      => $next,
            ];
        }
        return $summary;
    }

    /**
     * Return true if all registered hooks are currently scheduled.
     *
     * @return bool
     *
     * @example
     * ```php
     * if (Cron::areAllHooksScheduled()) { ... }
     * ```
     */
    public function areAllHooksScheduled(): bool
    {
        foreach ($this->events as $event) {
            if (!wp_next_scheduled($event['hook'])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Return true if any registered hook is currently scheduled.
     *
     * @return bool
     *
     * @example
     * ```php
     * if (Cron::isAnyHookScheduled()) { ... }
     * ```
     */
    public function isAnyHookScheduled(): bool
    {
        foreach ($this->events as $event) {
            if (wp_next_scheduled($event['hook'])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the total number of registered cron hooks.
     *
     * @return int
     *
     * @example
     * ```php
     * $count = Cron::countHooks();
     * ```
     */
    public function countHooks(): int
    {
        return count($this->events);
    }

    /**
     * Clear all scheduled hooks that match a specific recurrence.
     *
     * @param string $recurrence Recurrence interval ('hourly', 'daily', etc.)
     * @param bool $log Whether to log each removal
     * @return void
     *
     * @example
     * ```php
     * Cron::clearByRecurrence('hourly', true);
     * ```
     */
    public function clearByRecurrence(string $recurrence, bool $log = false): void
    {
        foreach ($this->events as $event) {
            if ($event['recurrence'] === $recurrence) {
                $this->remove(str_replace($this->prefix . '_', '', $event['hook']), $log);
            }
        }
    }

    /**
     * Schedule multiple hooks with the same recurrence and optional timestamp.
     *
     * @param array<string> $hooks Hook names without prefix
     * @param string $recurrence Recurrence interval
     * @param int|null $timestamp First run timestamp
     * @param bool $log Whether to log each scheduling
     * @return void
     *
     * @example
     * ```php
     * Cron::scheduleBatchWithRecurrence(['hourly_task', 'daily_task'], 'hourly', null, true);
     * ```
     */
    public function scheduleBatchWithRecurrence(array $hooks, string $recurrence, ?int $timestamp = null, bool $log = false): void
    {
        foreach ($hooks as $hook) {
            if ($this->hasHook($hook)) {
                $namespacedHook = $this->prefix . '_' . $hook;
                $event = array_filter($this->events, fn($e) => $e['hook'] === $namespacedHook);
                $event = reset($event);
                if ($event && !wp_next_scheduled($namespacedHook)) {
                    $ts = $timestamp ?? $event['timestamp'] ?? time();
                    wp_schedule_event($ts, $recurrence, $namespacedHook);
                    if ($log) {
                        Logger::info("Cron '{$namespacedHook}' scheduled with recurrence '{$recurrence}' at {$ts}.");
                    }
                }
            }
        }
    }

    /**
     * Remove all hooks that meet a condition callback.
     *
     * @param callable $condition Function receiving event array and returning bool
     * @param bool $log Whether to log each removal
     * @return void
     *
     * @example
     * ```php
     * Cron::removeIf(fn($event) => $event['recurrence'] === 'hourly', true);
     * ```
     */
    public function removeIf(callable $condition, bool $log = false): void
    {
        foreach ($this->events as $event) {
            if ($condition($event)) {
                $this->remove(str_replace($this->prefix . '_', '', $event['hook']), $log);
            }
        }
    }

    /**
     * Get all hooks with a specific recurrence.
     *
     * @param string $recurrence Recurrence interval
     * @return array<int, string> List of hook names without prefix
     *
     * @example
     * ```php
     * $hourlyHooks = Cron::getHooksByRecurrence('hourly');
     * ```
     */
    public function getHooksByRecurrence(string $recurrence): array
    {
        $result = [];
        foreach ($this->events as $event) {
            if ($event['recurrence'] === $recurrence) {
                $result[] = str_replace($this->prefix . '_', '', $event['hook']);
            }
        }
        return $result;
    }

    /**
     * Check if a hook meets a condition callback.
     *
     * @param string $hook Hook name without prefix
     * @param callable $condition Function receiving event array and returning bool
     * @return bool
     *
     * @example
     * ```php
     * if (Cron::checkHookCondition('hourly_task', fn($event) => $event['log'])) { ... }
     * ```
     */
    public function checkHookCondition(string $hook, callable $condition): bool
    {
        $namespacedHook = $this->prefix . '_' . $hook;
        foreach ($this->events as $event) {
            if ($event['hook'] === $namespacedHook) {
                return $condition($event);
            }
        }
        return false;
    }

    /**
     * Return all hooks that have logging enabled.
     *
     * @return array<int, string> List of hook names without prefix
     *
     * @example
     * ```php
     * $loggedHooks = Cron::getHooksWithLogging();
     * ```
     */
    public function getHooksWithLogging(): array
    {
        $result = [];
        foreach ($this->events as $event) {
            if (!empty($event['log'])) {
                $result[] = str_replace($this->prefix . '_', '', $event['hook']);
            }
        }
        return $result;
    }

    /**
     * Return all hooks that have a specific callback assigned.
     *
     * @param callable $callback Callback function to search for
     * @return array<int, string> List of hook names without prefix
     *
     * @example
     * ```php
     * $hooks = Cron::getHooksByCallback([$this, 'runTask']);
     * ```
     */
    public function getHooksByCallback(callable $callback): array
    {
        $result = [];
        foreach ($this->events as $event) {
            if ($event['callback'] === $callback) {
                $result[] = str_replace($this->prefix . '_', '', $event['hook']);
            }
        }
        return $result;
    }

    /**
     * Check if all hooks in a given list are currently scheduled.
     *
     * @param array<string> $hooks List of hook names without prefix
     * @return bool True if all are scheduled
     *
     * @example
     * ```php
     * if (Cron::areHooksScheduled(['hourly_task', 'daily_task'])) { ... }
     * ```
     */
    public function areHooksScheduled(array $hooks): bool
    {
        foreach ($hooks as $hook) {
            if (!$this->isScheduled($hook)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Unschedule all hooks in a given list.
     *
     * @param array<string> $hooks List of hook names without prefix
     * @param bool $log Whether to log each removal
     * @return void
     *
     * @example
     * ```php
     * Cron::unscheduleHooks(['hourly_task', 'daily_task'], true);
     * ```
     */
    public function unscheduleHooks(array $hooks, bool $log = false): void
    {
        foreach ($hooks as $hook) {
            $this->remove($hook, $log);
        }
    }

    /**
     * Execute a hook immediately with its current arguments (bypassing cron schedule).
     *
     * @param string $hook Hook name without prefix
     * @return void
     *
     * @example
     * ```php
     * Cron::runHookNow('hourly_task');
     * ```
     */
    public function runHookNow(string $hook): void
    {
        $namespacedHook = $this->prefix . '_' . $hook;
        foreach ($this->events as $event) {
            if ($event['hook'] === $namespacedHook && $event['callback']) {
                $args = $event['args'] ?? [];
                call_user_func_array($event['callback'], $args);
                break;
            }
        }
    }

    /**
     * Execute multiple hooks immediately that meet a condition.
     *
     * @param callable $condition Function receiving event array and returning bool
     * @return void
     *
     * @example
     * ```php
     * Cron::runHooksIf(fn($event) => $event['recurrence'] === 'hourly');
     * ```
     */
    public function runHooksIf(callable $condition): void
    {
        foreach ($this->events as $event) {
            if ($condition($event) && $event['callback']) {
                $args = $event['args'] ?? [];
                call_user_func_array($event['callback'], $args);
            }
        }
    }

    /**
     * Return the earliest next scheduled timestamp among all hooks.
     *
     * @return int|null Timestamp or null if no hooks scheduled
     *
     * @example
     * ```php
     * $earliest = Cron::getEarliestNextRun();
     * ```
     */
    public function getEarliestNextRun(): ?int
    {
        $timestamps = [];
        foreach ($this->events as $event) {
            $ts = wp_next_scheduled($event['hook']);
            if ($ts) {
                $timestamps[] = $ts;
            }
        }
        return $timestamps ? min($timestamps) : null;
    }

    /**
     * Return the latest next scheduled timestamp among all hooks.
     *
     * @return int|null Timestamp or null if no hooks scheduled
     *
     * @example
     * ```php
     * $latest = Cron::getLatestNextRun();
     * ```
     */
    public function getLatestNextRun(): ?int
    {
        $timestamps = [];
        foreach ($this->events as $event) {
            $ts = wp_next_scheduled($event['hook']);
            if ($ts) {
                $timestamps[] = $ts;
            }
        }
        return $timestamps ? max($timestamps) : null;
    }

    /**
     * Update arguments for multiple hooks in a batch.
     *
     * @param array<string, array> $hooksArgs Associative array: hook => arguments array
     * @param bool $log Whether to log each update
     * @return void
     *
     * @example
     * ```php
     * Cron::updateBatchArgs([
     *     'hourly_task' => ['param1', 'param2'],
     *     'daily_task'  => ['paramA']
     * ], true);
     * ```
     */
    public function updateBatchArgs(array $hooksArgs, bool $log = false): void
    {
        foreach ($hooksArgs as $hook => $args) {
            $this->updateArgs($hook, $args, $log);
        }
    }

    /**
     * Check if any hook meets a condition callback.
     *
     * @param callable $condition Function receiving event array and returning bool
     * @return bool True if at least one hook meets the condition
     *
     * @example
     * ```php
     * if (Cron::isAnyHookMatching(fn($event) => $event['recurrence'] === 'hourly')) { ... }
     * ```
     */
    public function isAnyHookMatching(callable $condition): bool
    {
        foreach ($this->events as $event) {
            if ($condition($event)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Enable logging for multiple hooks at once.
     *
     * @param array<string> $hooks List of hook names without prefix
     * @return void
     *
     * @example
     * ```php
     * Cron::enableLoggingBatch(['hourly_task', 'daily_task']);
     * ```
     */
    public function enableLoggingBatch(array $hooks): void
    {
        foreach ($hooks as $hook) {
            $this->enableLogging($hook);
        }
    }

    /**
     * Disable logging for multiple hooks at once.
     *
     * @param array<string> $hooks List of hook names without prefix
     * @return void
     *
     * @example
     * ```php
     * Cron::disableLoggingBatch(['hourly_task', 'daily_task']);
     * ```
     */
    public function disableLoggingBatch(array $hooks): void
    {
        foreach ($hooks as $hook) {
            $this->disableLogging($hook);
        }
    }

    /**
     * Reset specific hooks (unschedule and remove from events array).
     *
     * @param array<string> $hooks List of hook names without prefix
     * @param bool $log Whether to log each reset
     * @return void
     *
     * @example
     * ```php
     * Cron::resetHooks(['hourly_task', 'daily_task'], true);
     * ```
     */
    public function resetHooks(array $hooks, bool $log = false): void
    {
        foreach ($hooks as $hook) {
            $this->remove($hook, $log);
            $this->clearHook($hook, $log);
        }
    }

    /**
     * Check if a hook has arguments defined.
     *
     * @param string $hook Hook name without prefix
     * @return bool True if arguments exist
     *
     * @example
     * ```php
     * if (Cron::hasArgs('hourly_task')) { ... }
     * ```
     */
    public function hasArgs(string $hook): bool
    {
        $namespacedHook = $this->prefix . '_' . $hook;
        foreach ($this->events as $event) {
            if ($event['hook'] === $namespacedHook) {
                return !empty($event['args']);
            }
        }
        return false;
    }

    /**
     * Return all hooks that have a specific condition callback defined.
     *
     * @param callable $condition Condition callback to match
     * @return array<int, string> List of hook names without prefix
     *
     * @example
     * ```php
     * $hooks = Cron::getHooksWithCondition(fn($e) => $e['recurrence'] === 'hourly');
     * ```
     */
    public function getHooksWithCondition(callable $condition): array
    {
        $result = [];
        foreach ($this->events as $event) {
            if ($event['condition'] === $condition) {
                $result[] = str_replace($this->prefix . '_', '', $event['hook']);
            }
        }
        return $result;
    }

    /**
     * Check if a specific hook has a condition callback defined.
     *
     * @param string $hook Hook name without prefix
     * @return bool True if a condition is defined
     *
     * @example
     * ```php
     * if (Cron::hasCondition('hourly_task')) { ... }
     * ```
     */
    public function hasCondition(string $hook): bool
    {
        $namespacedHook = $this->prefix . '_' . $hook;
        foreach ($this->events as $event) {
            if ($event['hook'] === $namespacedHook) {
                return !is_null($event['condition']);
            }
        }
        return false;
    }

    /**
     * Get all hooks with a specific recurrence and logging enabled.
     *
     * @param string $recurrence Recurrence interval
     * @return array<int, string> List of hook names without prefix
     *
     * @example
     * ```php
     * $hooks = Cron::getHooksByRecurrenceWithLogging('hourly');
     * ```
     */
    public function getHooksByRecurrenceWithLogging(string $recurrence): array
    {
        $result = [];
        foreach ($this->events as $event) {
            if ($event['recurrence'] === $recurrence && !empty($event['log'])) {
                $result[] = str_replace($this->prefix . '_', '', $event['hook']);
            }
        }
        return $result;
    }

    /**
     * Reschedule multiple hooks with the same new recurrence.
     *
     * @param array<string> $hooks List of hook names without prefix
     * @param string $newRecurrence New recurrence interval
     * @param bool $log Whether to log each reschedule
     * @return void
     *
     * @example
     * ```php
     * Cron::rescheduleBatch(['hourly_task', 'daily_task'], 'daily', true);
     * ```
     */
    public function rescheduleBatch(array $hooks, string $newRecurrence, bool $log = false): void
    {
        foreach ($hooks as $hook) {
            $this->updateRecurrence($hook, $newRecurrence, $log);
        }
    }

    /**
     * Retrieve all hooks whose next run is before a given timestamp.
     *
     * @param int $timestamp UNIX timestamp
     * @return array<int, string> List of hook names without prefix
     *
     * @example
     * ```php
     * $hooks = Cron::getHooksBeforeTimestamp(time() + 3600);
     * ```
     */
    public function getHooksBeforeTimestamp(int $timestamp): array
    {
        $result = [];
        foreach ($this->events as $event) {
            $next = wp_next_scheduled($event['hook']);
            if ($next && $next < $timestamp) {
                $result[] = str_replace($this->prefix . '_', '', $event['hook']);
            }
        }
        return $result;
    }

    /**
     * Retrieve all hooks whose next run is after a given timestamp.
     *
     * @param int $timestamp UNIX timestamp
     * @return array<int, string> List of hook names without prefix
     *
     * @example
     * ```php
     * $hooks = Cron::getHooksAfterTimestamp(time() + 3600);
     * ```
     */
    public function getHooksAfterTimestamp(int $timestamp): array
    {
        $result = [];
        foreach ($this->events as $event) {
            $next = wp_next_scheduled($event['hook']);
            if ($next && $next > $timestamp) {
                $result[] = str_replace($this->prefix . '_', '', $event['hook']);
            }
        }
        return $result;
    }

    /**
     * Unschedule multiple hooks only if they meet a condition callback.
     *
     * @param array<string> $hooks List of hook names without prefix
     * @param callable $condition Function receiving event array and returning bool
     * @param bool $log Whether to log each removal
     * @return void
     *
     * @example
     * ```php
     * Cron::unscheduleBatchIf(['hourly_task','daily_task'], fn($e) => $e['recurrence']==='hourly', true);
     * ```
     */
    public function unscheduleBatchIf(array $hooks, callable $condition, bool $log = false): void
    {
        foreach ($hooks as $hook) {
            $namespacedHook = $this->prefix . '_' . $hook;
            foreach ($this->events as $event) {
                if ($event['hook'] === $namespacedHook && $condition($event)) {
                    $this->remove($hook, $log);
                }
            }
        }
    }

    /**
     * Reschedule a hook only if it is currently scheduled.
     *
     * @param string $hook Hook name without prefix
     * @param string|null $recurrence Optional new recurrence
     * @param int|null $timestamp Optional new timestamp
     * @param bool $log Whether to log the rescheduling
     * @return void
     *
     * @example
     * ```php
     * Cron::rescheduleIfScheduled('hourly_task', 'daily', null, true);
     * ```
     */
    public function rescheduleIfScheduled(string $hook, ?string $recurrence = null, ?int $timestamp = null, bool $log = false): void
    {
        if ($this->isScheduled($hook)) {
            $event = $this->getHookInfo($hook);
            if ($event) {
                $newRecurrence = $recurrence ?? $event['recurrence'];
                $newTimestamp  = $timestamp ?? $event['timestamp'] ?? time();
                $this->reschedule($hook, $newRecurrence, $newTimestamp, $log);
            }
        }
    }

    /**
     * Retrieve all hooks that have a specific argument at a given index.
     *
     * @param int $index Argument index
     * @param mixed $value Value to match
     * @return array<int, string> List of hook names without prefix
     *
     * @example
     * ```php
     * $hooks = Cron::getHooksByArg(0, 'param1');
     * ```
     */
    public function getHooksByArg(int $index, mixed $value): array
    {
        $result = [];
        foreach ($this->events as $event) {
            $args = $event['args'] ?? [];
            if (isset($args[$index]) && $args[$index] === $value) {
                $result[] = str_replace($this->prefix . '_', '', $event['hook']);
            }
        }
        return $result;
    }

    /**
     * Remove all hooks that are currently unscheduled.
     *
     * @param bool $log Whether to log each removal
     * @return void
     *
     * @example
     * ```php
     * Cron::removeAllUnscheduled(true);
     * ```
     */
    public function removeAllUnscheduled(bool $log = false): void
    {
        foreach ($this->events as $event) {
            if (!wp_next_scheduled($event['hook'])) {
                $this->remove(str_replace($this->prefix . '_', '', $event['hook']), $log);
            }
        }
    }

    /**
     * Return the total number of hooks that are currently scheduled.
     *
     * @return int
     *
     * @example
     * ```php
     * $count = Cron::countScheduledHooks();
     * ```
     */
    public function countScheduledHooks(): int
    {
        $count = 0;
        foreach ($this->events as $event) {
            if (wp_next_scheduled($event['hook'])) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Clear arguments for a specific hook.
     *
     * @param string $hook Hook name without prefix
     * @param bool $log Whether to log the action
     * @return void
     *
     * @example
     * ```php
     * Cron::clearArgs('hourly_task', true);
     * ```
     */
    public function clearArgs(string $hook, bool $log = false): void
    {
        $namespacedHook = $this->prefix . '_' . $hook;
        foreach ($this->events as &$event) {
            if ($event['hook'] === $namespacedHook) {
                $event['args'] = [];
                if ($log) {
                    Logger::info("Arguments cleared for cron '{$namespacedHook}'.");
                }
                break;
            }
        }
    }

    /**
     * Disable multiple hooks conditionally based on a callback.
     *
     * @param callable $condition Function receiving event array and returning bool
     * @param bool $log Whether to log each action
     * @return void
     *
     * @example
     * ```php
     * Cron::disableHooksIf(fn($event) => $event['recurrence']==='hourly', true);
     * ```
     */
    public function disableHooksIf(callable $condition, bool $log = false): void
    {
        foreach ($this->events as $event) {
            if ($condition($event)) {
                $this->disableHook(str_replace($this->prefix . '_', '', $event['hook']), $log);
            }
        }
    }

    /**
     * Enable multiple hooks conditionally based on a callback.
     *
     * @param callable $condition Function receiving event array and returning bool
     * @param bool $log Whether to log each action
     * @return void
     *
     * @example
     * ```php
     * Cron::enableHooksIf(fn($event) => $event['recurrence']==='daily', true);
     * ```
     */
    public function enableHooksIf(callable $condition, bool $log = false): void
    {
        foreach ($this->events as $event) {
            if ($condition($event)) {
                $this->enableHook(str_replace($this->prefix . '_', '', $event['hook']), $log);
            }
        }
    }

    /**
     * Retrieve all hooks that have arguments defined.
     *
     * @return array<int, string> List of hook names without prefix
     *
     * @example
     * ```php
     * $hooksWithArgs = Cron::getHooksWithArgs();
     * ```
     */
    public function getHooksWithArgs(): array
    {
        $result = [];
        foreach ($this->events as $event) {
            if (!empty($event['args'])) {
                $result[] = str_replace($this->prefix . '_', '', $event['hook']);
            }
        }
        return $result;
    }

    /**
     * Retrieve all hooks that currently have no arguments.
     *
     * @return array<int, string> List of hook names without prefix
     *
     * @example
     * ```php
     * $hooksWithoutArgs = Cron::getHooksWithoutArgs();
     * ```
     */
    public function getHooksWithoutArgs(): array
    {
        $result = [];
        foreach ($this->events as $event) {
            if (empty($event['args'])) {
                $result[] = str_replace($this->prefix . '_', '', $event['hook']);
            }
        }
        return $result;
    }

    /**
     * Shift the next scheduled timestamp of a hook by a given number of seconds.
     *
     * @param string $hook Hook name without prefix
     * @param int $seconds Number of seconds to shift (positive or negative)
     * @param bool $log Whether to log the action
     * @return void
     *
     * @example
     * ```php
     * Cron::shiftNextRun('hourly_task', 3600, true);
     * ```
     */
    public function shiftNextRun(string $hook, int $seconds, bool $log = false): void
    {
        $namespacedHook = $this->prefix . '_' . $hook;
        $next = wp_next_scheduled($namespacedHook);
        if ($next) {
            wp_unschedule_event($next, $namespacedHook);
            $event = $this->getHookInfo($hook);
            if ($event) {
                $newTimestamp = ($event['timestamp'] ?? time()) + $seconds;
                wp_schedule_event($newTimestamp, $event['recurrence'], $namespacedHook);
                if ($log) {
                    Logger::info("Cron '{$namespacedHook}' shifted by {$seconds} seconds to {$newTimestamp}.");
                }
            }
        }
    }

    /**
     * Batch shift next run timestamps for multiple hooks.
     *
     * @param array<string> $hooks List of hook names without prefix
     * @param int $seconds Number of seconds to shift
     * @param bool $log Whether to log each action
     * @return void
     *
     * @example
     * ```php
     * Cron::shiftNextRunBatch(['hourly_task', 'daily_task'], 600, true);
     * ```
     */
    public function shiftNextRunBatch(array $hooks, int $seconds, bool $log = false): void
    {
        foreach ($hooks as $hook) {
            $this->shiftNextRun($hook, $seconds, $log);
        }
    }

    /**
     * Check if a hook’s next run is before a given timestamp.
     *
     * @param string $hook Hook name without prefix
     * @param int $timestamp UNIX timestamp
     * @return bool
     *
     * @example
     * ```php
     * if (Cron::isNextRunBefore('hourly_task', time() + 3600)) { ... }
     * ```
     */
    public function isNextRunBefore(string $hook, int $timestamp): bool
    {
        $namespacedHook = $this->prefix . '_' . $hook;
        $next = wp_next_scheduled($namespacedHook);
        return $next !== false && $next < $timestamp;
    }

    /**
     * Check if a hook’s next run is after a given timestamp.
     *
     * @param string $hook Hook name without prefix
     * @param int $timestamp UNIX timestamp
     * @return bool
     *
     * @example
     * ```php
     * if (Cron::isNextRunAfter('hourly_task', time() + 3600)) { ... }
     * ```
     */
    public function isNextRunAfter(string $hook, int $timestamp): bool
    {
        $namespacedHook = $this->prefix . '_' . $hook;
        $next = wp_next_scheduled($namespacedHook);
        return $next !== false && $next > $timestamp;
    }

    /**
     * Retrieve all hooks with next run timestamps within a specific range.
     *
     * @param int $start UNIX timestamp start
     * @param int $end UNIX timestamp end
     * @return array<int, string> List of hook names without prefix
     *
     * @example
     * ```php
     * $hooks = Cron::getHooksInRange(time(), time()+3600);
     * ```
     */
    public function getHooksInRange(int $start, int $end): array
    {
        $result = [];
        foreach ($this->events as $event) {
            $next = wp_next_scheduled($event['hook']);
            if ($next && $next >= $start && $next <= $end) {
                $result[] = str_replace($this->prefix . '_', '', $event['hook']);
            }
        }
        return $result;
    }

    /**
     * Log all registered hooks and their next run timestamps.
     *
     * @return void
     *
     * @example
     * ```php
     * Cron::logAllHooks();
     * ```
     */
    public function logAllHooks(): void
    {
        foreach ($this->events as $event) {
            $next = wp_next_scheduled($event['hook']);
            Logger::info("Cron '{$event['hook']}' next run: " . ($next ?: 'not scheduled'));
        }
    }

    /**
     * Check if a hook’s arguments match a given array.
     *
     * @param string $hook Hook name without prefix
     * @param array $args Arguments array to compare
     * @return bool True if arguments match
     *
     * @example
     * ```php
     * if (Cron::argsMatch('hourly_task', ['param1', 'param2'])) { ... }
     * ```
     */
    public function argsMatch(string $hook, array $args): bool
    {
        $event = $this->getHookInfo($hook);
        return $event && ($event['args'] ?? []) === $args;
    }

    /**
     * Clear all arguments for all hooks.
     *
     * @param bool $log Whether to log each clearing
     * @return void
     *
     * @example
     * ```php
     * Cron::clearAllArgs(true);
     * ```
     */
    public function clearAllArgs(bool $log = false): void
    {
        foreach ($this->events as $event) {
            $this->clearArgs(str_replace($this->prefix . '_', '', $event['hook']), $log);
        }
    }

    /**
     * Retrieve all hooks whose arguments match a specific array.
     *
     * @param array $args Arguments array to match
     * @return array<int, string> List of hook names without prefix
     *
     * @example
     * ```php
     * $matchingHooks = Cron::getHooksByArgs(['param1','param2']);
     * ```
     */
    public function getHooksByArgs(array $args): array
    {
        $result = [];
        foreach ($this->events as $event) {
            if (($event['args'] ?? []) === $args) {
                $result[] = str_replace($this->prefix . '_', '', $event['hook']);
            }
        }
        return $result;
    }

    /**
     * Enable multiple hooks at once.
     *
     * @param array<string> $hooks List of hook names without prefix
     * @param bool $log Whether to log each enabling
     * @return void
     *
     * @example
     * ```php
     * Cron::enableHooks(['hourly_task','daily_task'], true);
     * ```
     */
    public function enableHooks(array $hooks, bool $log = false): void
    {
        foreach ($hooks as $hook) {
            if ($this->hasHook($hook)) {
                $this->scheduleIf($hook, fn($event)=>true, $log);
            }
        }
    }
}
