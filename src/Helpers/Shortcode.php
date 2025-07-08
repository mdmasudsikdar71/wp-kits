<?php

namespace MDMasudSikdar\WpKits\Helpers;

/**
 * Class Shortcode
 *
 * Provides a clean API for managing WordPress shortcodes programmatically.
 *
 * This helper allows centralizing all shortcode registration and callbacks.
 *
 * @package MDMasudSikdar\WpKits\Helpers
 */
class Shortcode
{
    /**
     * Register a shortcode with WordPress.
     *
     * @param string $tag The shortcode tag (e.g., 'my_shortcode').
     * @param callable $callback The function that renders the output.
     *                           Signature: function(array $atts = [], string|null $content = null): string
     *
     * @return void
     *
     * @example
     * ```php
     * Shortcode::register('hello_world', function () {
     *     return 'Hello, world!';
     * });
     *
     * Shortcode::register('wpbp_button', function ($atts) {
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
     * ```
     */
    public static function register(string $tag, callable $callback): void
    {
        add_shortcode($tag, $callback);
    }

    /**
     * Remove a registered shortcode.
     *
     * @param string $tag The shortcode tag to remove.
     *
     * @return void
     *
     * @example
     * ```php
     * Shortcode::remove('hello_world');
     * ```
     */
    public static function remove(string $tag): void
    {
        remove_shortcode($tag);
    }

    /**
     * Check if a shortcode is already registered.
     *
     * @param string $tag The shortcode tag.
     * @return bool True if registered, false otherwise.
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
        return isset($shortcode_tags[$tag]);
    }

    /**
     * Remove all registered shortcodes (not usually recommended).
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
        remove_all_shortcodes();
    }
}
