<?php

namespace MDMasudSikdar\WpKits\Helpers;

/**
 * Class Language
 *
 * Singleton-style language helper for WordPress plugins.
 *
 * Initialize once with your plugin text domain. Then, anywhere in your plugin,
 * use WordPress-style translation methods without passing the domain again.
 *
 * Example usage in main plugin file:
 * ```php
 * Language::init('my-plugin-domain');
 * ```
 *
 * Usage in any other plugin file:
 * ```php
 * echo Language::__('Settings');
 * Language::_e('Welcome Admin');
 * echo Language::_x('Post', 'noun');
 * echo Language::esc_html__('Settings');
 * Language::esc_html_e('Welcome Admin');
 * ```
 *
 * @package MDMasudSikdar\WpKits\Helpers
 */
final class Language
{
    /**
     * The plugin text domain for translations.
     *
     * @var string|null
     */
    private static ?string $domain = null;

    /**
     * Initialize the language helper with the plugin text domain.
     *
     * This should be called once in your main plugin file before using any translation methods.
     *
     * @param string $domain Plugin text domain
     * @return void
     *
     * @example
     * Language::init('my-plugin-domain');
     */
    public static function init(string $domain): void
    {
        if (self::$domain === null) {
            self::$domain = $domain;
        }
    }

    /**
     * Ensure the helper has been initialized.
     *
     * Stops execution using wp_die() if the helper is not initialized.
     *
     * @return void
     */
    private static function ensureInit(): void
    {
        if (self::$domain === null) {
            wp_die(
                esc_html__('Language helper not initialized. Call Language::init() with plugin text domain first.', 'default-text-domain'),
                esc_html__('Plugin Error', 'default-text-domain')
            );
        }
    }

    /**
     * Translate a string (__()).
     *
     * @param string $text The string to translate
     * @return string Translated string
     *
     * @example
     * echo Language::__('Settings');
     */
    public static function __(string $text): string
    {
        self::ensureInit();
        return __($text, self::$domain);
    }

    /**
     * Translate a string and echo it (_e()).
     *
     * @param string $text The string to translate and echo
     * @return void
     *
     * @example
     * Language::_e('Welcome Admin');
     */
    public static function _e(string $text): void
    {
        self::ensureInit();
        _e($text, self::$domain);
    }

    /**
     * Translate a string with context (_x()).
     *
     * @param string $text The string to translate
     * @param string $context The context for translators
     * @return string Translated string with context
     *
     * @example
     * echo Language::_x('Post', 'noun');
     */
    public static function _x(string $text, string $context): string
    {
        self::ensureInit();
        return _x($text, $context, self::$domain);
    }

    /**
     * Translate a string and escape it for safe HTML output (esc_html__()).
     *
     * @param string $text The string to translate and escape
     * @return string Escaped translated string
     *
     * @example
     * echo Language::esc_html__('Settings');
     */
    public static function esc_html__(string $text): string
    {
        self::ensureInit();
        return esc_html(self::__($text));
    }

    /**
     * Translate a string, escape it, and echo immediately (esc_html_e()).
     *
     * @param string $text The string to translate, escape, and echo
     * @return void
     *
     * @example
     * Language::esc_html_e('Welcome Admin');
     */
    public static function esc_html_e(string $text): void
    {
        self::ensureInit();
        echo self::esc_html__($text);
    }

    /**
     * Get the plugin text domain currently in use.
     *
     * @return string The plugin text domain
     *
     * @example
     * $domain = Language::getDomain();
     */
    public static function getDomain(): string
    {
        self::ensureInit();
        return self::$domain;
    }
}
