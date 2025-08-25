<?php

namespace MDMasudSikdar\WpKits\Helpers;

/**
 * Class Shortcode
 *
 * Provides a clean, centralized API for managing WordPress shortcodes programmatically.
 *
 * Features:
 * ✅ Centralized shortcode registration
 * ✅ Easy removal and existence checking
 * ✅ Supports default WordPress callback signatures
 *
 * Example usage:
 * ```php
 * use MDMasudSikdar\WpKits\Helpers\Shortcode;
 *
 * // Register a simple shortcode
 * Shortcode::register('hello_world', function () {
 *     return 'Hello, world!';
 * });
 *
 * // Register a shortcode with attributes
 * Shortcode::register('wpkits_button', function ($atts) {
 *     $atts = shortcode_atts([
 *         'text' => 'Click Me',
 *         'url'  => '#',
 *     ], $atts);
 *
 *     return sprintf(
 *         '<a href="%s" class="button">%s</a>',
 *         esc_url($atts['url']),
 *         esc_html($atts['text'])
 *     );
 * });
 *
 * // Remove a shortcode
 * Shortcode::remove('hello_world');
 *
 * // Check if a shortcode exists
 * if (!Shortcode::exists('custom_shortcode')) {
 *     Shortcode::register('custom_shortcode', 'my_callback');
 * }
 *
 * // Remove all shortcodes (use with caution)
 * Shortcode::removeAll();
 * ```
 *
 * @package MDMasudSikdar\WpKits\Helpers
 */
class Shortcode
{
    /**
     * Register a shortcode with WordPress.
     *
     * @param string   $tag The shortcode tag (e.g., 'my_shortcode').
     * @param callable $callback The callback that renders the output.
     *                           Signature: function(array $atts = [], string|null $content = null): string
     * @return void
     *
     * @example
     * ```php
     * Shortcode::register('hello_world', function () {
     *     return 'Hello, world!';
     * });
     * ```
     */
    public static function register(string $tag, callable $callback): void
    {
        // Register the shortcode with WordPress
        add_shortcode($tag, $callback);
    }

    /**
     * Remove a registered shortcode by tag.
     *
     * @param string $tag The shortcode tag to remove
     * @return void
     *
     * @example
     * ```php
     * Shortcode::remove('hello_world');
     * ```
     */
    public static function remove(string $tag): void
    {
        // Remove the shortcode from WordPress registry
        remove_shortcode($tag);
    }

    /**
     * Check if a shortcode is already registered.
     *
     * @param string $tag The shortcode tag to check
     * @return bool True if registered, false otherwise
     *
     * @example
     * ```php
     * if (!Shortcode::exists('custom_shortcode')) {
     *     Shortcode::register('custom_shortcode', 'my_callback');
     * }
     * ```
     */
    public static function exists(string $tag): bool
    {
        global $shortcode_tags;

        // Check the global $shortcode_tags array for existence
        return isset($shortcode_tags[$tag]);
    }

    /**
     * Remove all registered shortcodes.
     *
     * Use with caution, as this affects all plugins and theme shortcodes.
     *
     * @return void
     *
     * @example
     * ```php
     * Shortcode::removeAll();
     * ```
     */
    public static function removeAll(): void
    {
        // Remove all shortcodes from WordPress
        remove_all_shortcodes();
    }
}
