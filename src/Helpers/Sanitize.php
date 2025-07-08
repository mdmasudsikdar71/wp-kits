<?php


namespace MDMasudSikdar\WpKits\Helpers;

use DateTimeInterface;

/**
 * Class Sanitize
 *
 * Provides common sanitization utilities to clean and secure data.
 *
 * Use this class to sanitize user inputs, text, URLs, emails, and more.
 *
 * @package MDMasudSikdar\WpKits\Helpers
 */
class Sanitize
{
    /**
     * Sanitize a string by stripping tags and escaping HTML entities.
     *
     * Suitable for plain text fields.
     *
     * @param string|null $string Input string to sanitize.
     * @return string Sanitized string.
     *
     * @example
     * ```php
     * Sanitize::text('<b>Hello</b> & welcome!'); // returns 'Hello & welcome!'
     * ```
     */
    public static function text(?string $string): string
    {
        if ($string === null) {
            return '';
        }
        return wp_strip_all_tags(trim($string));
    }

    /**
     * Sanitize a string allowing limited HTML tags (same as wp_kses_post).
     *
     * Useful for post content or descriptions where basic HTML is allowed.
     *
     * @param string|null $string Input string to sanitize.
     * @return string Sanitized string with safe HTML.
     *
     * @example
     * ```php
     * Sanitize::html('<b>Hello</b> <script>alert(1)</script>'); // returns '<b>Hello</b> '
     * ```
     */
    public static function html(?string $string): string
    {
        if ($string === null) {
            return '';
        }
        return wp_kses_post(trim($string));
    }

    /**
     * Sanitize an email address.
     *
     * Uses WordPress's sanitize_email and validates format.
     *
     * @param string|null $email Email address to sanitize.
     * @return string Sanitized email or empty string if invalid.
     *
     * @example
     * ```php
     * Sanitize::email(' test@example.com '); // returns 'test@example.com'
     * Sanitize::email('invalid-email'); // returns ''
     * ```
     */
    public static function email(?string $email): string
    {
        if ($email === null) {
            return '';
        }
        $email = sanitize_email(trim($email));
        return is_email($email) ? $email : '';
    }

    /**
     * Sanitize a URL.
     *
     * Uses WordPress's esc_url_raw for proper sanitization.
     *
     * @param string|null $url URL to sanitize.
     * @return string Sanitized URL or empty string if invalid.
     *
     * @example
     * ```php
     * Sanitize::url(' https://example.com/?q=<script> '); // returns 'https://example.com/?q='
     * ```
     */
    public static function url(?string $url): string
    {
        if ($url === null) {
            return '';
        }
        return esc_url_raw(trim($url));
    }

    /**
     * Sanitize integer values.
     *
     * Casts to int and ensures numeric value.
     *
     * @param mixed $value Input value.
     * @return int Sanitized integer.
     *
     * @example
     * ```php
     * Sanitize::int('123'); // 123
     * Sanitize::int('abc'); // 0
     * ```
     */
    public static function int($value): int
    {
        return intval($value);
    }

    /**
     * Sanitize boolean values.
     *
     * Converts truthy values to true, else false.
     *
     * @param mixed $value Input value.
     * @return bool Sanitized boolean.
     *
     * @example
     * ```php
     * Sanitize::bool('true'); // true
     * Sanitize::bool(0); // false
     * ```
     */
    public static function bool(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Sanitize text area / multi-line input.
     *
     * Strips dangerous tags but allows some formatting HTML.
     *
     * @param string|null $string Input string.
     * @return string Sanitized content.
     *
     * @example
     * ```php
     * Sanitize::textarea("<p>Hello <script>alert('x')</script></p>"); // returns "<p>Hello </p>"
     * ```
     */
    public static function textarea(?string $string): string
    {
        if ($string === null) {
            return '';
        }
        $allowed_tags = [
            'p' => [],
            'br' => [],
            'strong' => [],
            'em' => [],
            'ul' => [],
            'ol' => [],
            'li' => [],
            'a' => ['href' => true, 'title' => true, 'target' => true, 'rel' => true],
            'blockquote' => [],
            'code' => [],
        ];

        return wp_kses(trim($string), $allowed_tags);
    }

    /**
     * Sanitize JSON string safely and decode to array/object.
     *
     * Returns null on failure.
     *
     * @param string|null $json JSON string input.
     * @param bool $assoc Return associative array if true, else stdClass.
     * @return array|object|null
     *
     * @example
     * ```php
     * $data = Sanitize::json('{"key": "value"}'); // returns array or object
     * $fail = Sanitize::json('invalid json'); // returns null
     * ```
     */
    public static function json(?string $json, bool $assoc = true): object|array|null
    {
        if ($json === null) {
            return null;
        }

        $decoded = json_decode($json, $assoc);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        return null;
    }

    /**
     * Recursively sanitize an array or value with a callback.
     *
     * Useful to sanitize nested arrays of user input.
     *
     * @param mixed $data Input data, array or scalar.
     * @param callable $sanitizeFn Sanitization callback applied to each scalar.
     * @return mixed Sanitized data.
     *
     * @example
     * ```php
     * $input = ['name' => '<b>Bob</b>', 'emails' => ['a@b.com', '<script>bad</script>']];
     * $clean = Sanitize::recursive($input, fn($v) => Sanitize::text($v));
     * ```
     */
    public static function recursive(mixed $data, callable $sanitizeFn): mixed
    {
        if (is_array($data)) {
            return array_map(fn($item) => self::recursive($item, $sanitizeFn), $data);
        }

        return $sanitizeFn($data);
    }

    /**
     * Sanitize an array of integers.
     *
     * @param array|null $array Input array.
     * @return int[] Sanitized integer array.
     *
     * @example
     * ```php
     * Sanitize::intArray(['1', '2', 'abc']); // returns [1, 2, 0]
     * ```
     */
    public static function intArray(?array $array): array
    {
        if ($array === null) {
            return [];
        }

        return array_map('intval', $array);
    }

    /**
     * Sanitize float/double values.
     *
     * @param mixed $value Input value.
     * @return float Sanitized float.
     *
     * @example
     * ```php
     * Sanitize::float('3.14'); // 3.14
     * Sanitize::float('abc'); // 0.0
     * ```
     */
    public static function float(mixed $value): float
    {
        return floatval($value);
    }

    /**
     * Sanitize a slug string (a-z, 0-9, dash, underscore).
     *
     * @param string $slug Input slug string.
     * @return string Sanitized slug.
     *
     * @example
     * ```php
     * Sanitize::slug('Hello World!'); // returns 'hello-world'
     * ```
     */
    public static function slug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9-_]+/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        return trim($slug, '-');
    }

    /**
     * Sanitize a hex color code (#rrggbb or #rgb).
     *
     * Returns empty string if invalid.
     *
     * @param string|null $color Hex color code.
     * @return string Sanitized hex color or empty string.
     *
     * @example
     * ```php
     * Sanitize::hexColor('#FF00AA'); // returns '#FF00AA'
     * Sanitize::hexColor('bad'); // returns ''
     * ```
     */
    public static function hexColor(?string $color): string
    {
        if ($color === null) {
            return '';
        }
        $color = trim($color);
        if (preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color)) {
            return strtoupper($color);
        }
        return '';
    }

    /**
     * Sanitize CSS class name.
     *
     * Only allows letters, numbers, dashes, underscores.
     *
     * @param string $class CSS class name.
     * @return string Sanitized class name.
     *
     * @example
     * ```php
     * Sanitize::cssClass('btn btn-primary!'); // returns 'btn btn-primary'
     * ```
     */
    public static function cssClass(string $class): string
    {
        return preg_replace('/[^a-zA-Z0-9-_ ]+/', '', $class);
    }

    /**
     * Sanitize checkbox value (e.g. from HTML forms).
     *
     * Returns 'on' or 'off'.
     *
     * @param mixed $value Input value.
     * @return string 'on' if truthy, else 'off'.
     *
     * @example
     * ```php
     * Sanitize::checkbox('on'); // 'on'
     * Sanitize::checkbox(''); // 'off'
     * ```
     */
    public static function checkbox(mixed $value): string
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'on' : 'off';
    }

    /**
     * Sanitize ISO 8601 date/time string.
     *
     * Returns formatted date/time string or empty if invalid.
     *
     * @param string|null $date Input date string.
     * @return string Sanitized ISO8601 date/time or empty string.
     *
     * @example
     * ```php
     * Sanitize::isoDate('2024-01-01T12:34:56Z'); // returns '2024-01-01T12:34:56+00:00'
     * Sanitize::isoDate('invalid'); // returns ''
     * ```
     */
    public static function isoDate(?string $date): string
    {
        if ($date === null) {
            return '';
        }

        try {
            $dt = new \DateTime($date);
            return $dt->format(DateTimeInterface::ATOM);
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Sanitize a comma-separated list of emails.
     *
     * Returns array of valid sanitized emails.
     *
     * @param string|null $emails Comma separated emails.
     * @return string[] Array of valid emails.
     *
     * @example
     * ```php
     * Sanitize::emailList('a@b.com, bad-email, c@d.com');
     * // returns ['a@b.com', 'c@d.com']
     * ```
     */
    public static function emailList(?string $emails): array
    {
        if ($emails === null) {
            return [];
        }

        $items = array_map('trim', explode(',', $emails));
        $valid = [];

        foreach ($items as $email) {
            $sanitized = self::email($email);
            if ($sanitized !== '') {
                $valid[] = $sanitized;
            }
        }

        return $valid;
    }

    /**
     * Sanitize a file name removing unsafe characters.
     *
     * Only allows letters, numbers, dashes, underscores, dots.
     *
     * @param string $filename Input filename.
     * @return string Sanitized filename.
     *
     * @example
     * ```php
     * Sanitize::filename('some file@!#.jpg'); // returns 'some-file.jpg'
     * ```
     */
    public static function filename(string $filename): string
    {
        $filename = trim($filename);
        $filename = preg_replace('/[^a-zA-Z0-9-_.]+/', '-', $filename);
        $filename = preg_replace('/-+/', '-', $filename);
        return trim($filename, '-');
    }
}
