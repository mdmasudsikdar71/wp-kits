<?php

namespace MDMasudSikdar\WpKits\Helpers;

/**
 * Class Str
 *
 * Provides common string manipulation utilities for use throughout the plugin.
 *
 * @package MDMasudSikdar\WpKits\Helpers
 */
class Str
{
    /**
     * Check if a string starts with a given substring.
     *
     * @param string $haystack The full string to check.
     * @param string $needle The substring to search for at the start.
     * @return bool True if $haystack starts with $needle, false otherwise.
     *
     * @example
     * ```php
     * Str::startsWith('HelloWorld', 'Hello'); // returns true
     * Str::startsWith('HelloWorld', 'World'); // returns false
     * ```
     */
    public static function startsWith(string $haystack, string $needle): bool
    {
        return strncmp($haystack, $needle, strlen($needle)) === 0;
    }

    /**
     * Check if a string ends with a given substring.
     *
     * @param string $haystack The full string to check.
     * @param string $needle The substring to search for at the end.
     * @return bool True if $haystack ends with $needle, false otherwise.
     *
     * @example
     * ```php
     * Str::endsWith('HelloWorld', 'World'); // returns true
     * Str::endsWith('HelloWorld', 'Hello'); // returns false
     * ```
     */
    public static function endsWith(string $haystack, string $needle): bool
    {
        $length = strlen($needle);
        if ($length === 0) {
            return true;
        }
        return substr($haystack, -$length) === $needle;
    }

    /**
     * Convert a string to camelCase.
     *
     * Converts strings like "hello_world" or "hello-world" to "helloWorld".
     *
     * @param string $string The input string to convert.
     * @return string The camelCase version of the string.
     *
     * @example
     * ```php
     * Str::toCamelCase('hello_world'); // returns 'helloWorld'
     * Str::toCamelCase('my-example-string'); // returns 'myExampleString'
     * ```
     */
    public static function toCamelCase(string $string): string
    {
        $result = strtolower($string);
        $result = preg_replace('/[_-]+/', ' ', $result);
        $result = ucwords($result);
        $result = str_replace(' ', '', $result);
        return lcfirst($result);
    }

    /**
     * Convert a string to snake_case.
     *
     * Converts camelCase or PascalCase strings to snake_case.
     *
     * @param string $string The input string to convert.
     * @return string The snake_case version of the string.
     *
     * @example
     * ```php
     * Str::toSnakeCase('helloWorld'); // returns 'hello_world'
     * Str::toSnakeCase('HelloWorld'); // returns 'hello_world'
     * ```
     */
    public static function toSnakeCase(string $string): string
    {
        $pattern = '/[A-Z]/';
        $snake = strtolower(preg_replace_callback($pattern, function ($matches) {
            return '_' . strtolower($matches[0]);
        }, lcfirst($string)));

        return ltrim($snake, '_');
    }

    /**
     * Truncate a string to a specified length and append an ellipsis if truncated.
     *
     * @param string $string The input string.
     * @param int $limit The maximum allowed length.
     * @param string $ellipsis The string to append when truncation happens (default '...').
     * @return string The truncated string, or original if shorter than limit.
     *
     * @example
     * ```php
     * Str::truncate('This is a long sentence', 10); // returns 'This is a ...'
     * Str::truncate('Short', 10); // returns 'Short'
     * ```
     */
    public static function truncate(string $string, int $limit, string $ellipsis = '...'): string
    {
        if (mb_strlen($string) <= $limit) {
            return $string;
        }
        return mb_substr($string, 0, $limit) . $ellipsis;
    }

    /**
     * Convert a string to a URL-friendly slug.
     *
     * Strips special characters, converts spaces to hyphens,
     * and lowercases the string.
     *
     * @param string $string The input string to slugify.
     * @return string The slugified string.
     *
     * @example
     * ```php
     * Str::slugify('Hello World! This is great.'); // returns 'hello-world-this-is-great'
     * Str::slugify('Clean_URL--Slug'); // returns 'clean-url-slug'
     * ```
     */
    public static function slugify(string $string): string
    {
        $slug = strtolower(trim($string));
        // Replace non-alphanumeric chars with hyphen
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        // Remove duplicate hyphens
        $slug = preg_replace('/-+/', '-', $slug);
        return trim($slug, '-');
    }

    /**
     * Check if a string contains a given substring.
     *
     * @param string $haystack The full string to check.
     * @param string $needle The substring to search for.
     * @return bool True if $needle is found anywhere in $haystack, false otherwise.
     *
     * @example
     * ```php
     * Str::contains('Hello World', 'World'); // true
     * Str::contains('Hello World', 'Foo'); // false
     * ```
     */
    public static function contains(string $haystack, string $needle): bool
    {
        return str_contains($haystack, $needle);
    }

    /**
     * Repeat a string multiple times.
     *
     * @param string $string The string to repeat.
     * @param int $times Number of repetitions.
     * @return string The repeated string.
     *
     * @example
     * ```php
     * Str::repeat('abc', 3); // returns 'abcabcabc'
     * ```
     */
    public static function repeat(string $string, int $times): string
    {
        return str_repeat($string, $times);
    }

    /**
     * Replace all occurrences of the search string with the replacement string.
     *
     * @param string $search The substring to find.
     * @param string $replace The replacement string.
     * @param string $subject The original string.
     * @return string The resulting string after replacements.
     *
     * @example
     * ```php
     * Str::replace('world', 'everyone', 'Hello world!'); // returns 'Hello everyone!'
     * ```
     */
    public static function replace(string $search, string $replace, string $subject): string
    {
        return str_replace($search, $replace, $subject);
    }

    /**
     * Convert the first character of a string to uppercase.
     *
     * @param string $string The input string.
     * @return string The string with the first character uppercased.
     *
     * @example
     * ```php
     * Str::ucfirst('hello'); // returns 'Hello'
     * ```
     */
    public static function ucfirst(string $string): string
    {
        return ucfirst($string);
    }

    /**
     * Convert the first character of a string to lowercase.
     *
     * @param string $string The input string.
     * @return string The string with the first character lowercased.
     *
     * @example
     * ```php
     * Str::lcfirst('Hello'); // returns 'hello'
     * ```
     */
    public static function lcfirst(string $string): string
    {
        return lcfirst($string);
    }

    /**
     * Convert all characters in a string to lowercase.
     *
     * @param string $string The input string.
     * @return string The lowercase string.
     *
     * @example
     * ```php
     * Str::lower('HELLO WORLD'); // returns 'hello world'
     * ```
     */
    public static function lower(string $string): string
    {
        return mb_strtolower($string);
    }

    /**
     * Convert all characters in a string to uppercase.
     *
     * @param string $string The input string.
     * @return string The uppercase string.
     *
     * @example
     * ```php
     * Str::upper('hello world'); // returns 'HELLO WORLD'
     * ```
     */
    public static function upper(string $string): string
    {
        return mb_strtoupper($string);
    }
}
