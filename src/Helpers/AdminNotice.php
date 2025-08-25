<?php

namespace MDMasudSikdar\WpKits\Helpers;

/**
 * Class AdminNotice
 *
 * Utility class to display styled admin notices in the WordPress dashboard.
 *
 * Supports notice types: 'success', 'error', 'warning', 'info'
 * Automatically hooks into `admin_notices` (and network_admin_notices)
 * Optionally dismissible
 * Supports queuing multiple notices
 * Optionally persistent across page loads
 *
 * @package MDMasudSikdar\WpKits\Helpers
 */
class AdminNotice
{
    /**
     * Queue of notices to display
     *
     * @var array<int, array{message:string, type:string, dismissible:bool, classes:array<string>, persistent:bool}>
     */
    protected static array $notices = [];

    /**
     * Add a new admin notice.
     *
     * @param string $message       The message to display.
     * @param string $type          The type of notice: 'success', 'error', 'warning', 'info'. Default 'info'.
     * @param bool   $dismissible   Whether the notice can be dismissed. Default true.
     * @param array  $classes       Additional CSS classes to apply.
     * @param bool   $persistent    Whether the notice should persist across page reloads. Default false.
     *
     * @return void
     *
     * @example
     * ```php
     * AdminNotice::add('Settings saved successfully.', 'success');
     * AdminNotice::add('Something went wrong.', 'error', false, ['custom-class']);
     * AdminNotice::add('Persistent notice across pages.', 'info', true, [], true);
     * ```
     */
    public static function add(
        string $message,
        string $type = 'info',
        bool $dismissible = true,
        array $classes = [],
        bool $persistent = false
    ): void {
        // Validate type
        $allowedTypes = ['success', 'error', 'warning', 'info'];
        $type = in_array($type, $allowedTypes, true) ? $type : 'info';

        // Add notice to the queue
        self::$notices[] = [
            'message'     => $message,
            'type'        => $type,
            'dismissible' => $dismissible,
            'classes'     => $classes,
            'persistent'  => $persistent,
        ];

        // If persistent, store in transient for cross-page display
        if ($persistent) {
            $existing = get_transient('admin_notices_queue') ?: [];
            $existing[] = end(self::$notices);
            set_transient('admin_notices_queue', $existing, 60 * 60); // 1 hour
        }

        // Hook into admin notices once
        add_action('admin_notices', [static::class, 'render']);
        add_action('network_admin_notices', [static::class, 'render']);
    }

    /**
     * Render all queued notices.
     *
     * @return void
     *
     * @internal
     */
    public static function render(): void
    {
        // Include any persistent notices from transients
        $persistent = get_transient('admin_notices_queue') ?: [];
        if (!empty($persistent)) {
            self::$notices = array_merge($persistent, self::$notices);
            delete_transient('admin_notices_queue'); // Remove after displaying
        }

        // Loop through all notices
        foreach (self::$notices as $notice) {
            $classes = array_merge(['notice', "notice-{$notice['type']}"], $notice['classes']);
            if ($notice['dismissible']) {
                $classes[] = 'is-dismissible';
            }

            printf(
                '<div class="%s"><p>%s</p></div>',
                esc_attr(implode(' ', $classes)),
                wp_kses_post($notice['message'])
            );
        }

        // Clear notices after rendering
        self::$notices = [];
    }

    /**
     * Clear all queued notices (non-persistent).
     *
     * @return void
     *
     * @example
     * ```php
     * AdminNotice::clear();
     * ```
     */
    public static function clear(): void
    {
        self::$notices = [];
    }
}
