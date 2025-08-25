<?php

namespace MDMasudSikdar\WpKits\Helpers;

/**
 * Class Option
 *
 * Advanced WordPress options helper.
 *
 * Features:
 * ✅ Safe get and set for WP options
 * ✅ Automatic JSON encode/decode for arrays and objects
 * ✅ Default fallback values for get operations
 * ✅ Bulk get/set multiple options
 * ✅ Safe option deletion
 * ✅ Fully static and reusable across plugins
 *
 * Example usage:
 * ```php
 * use MDMasudSikdar\WpKits\Helpers\Option;
 *
 * $value = Option::get('my_option', 'default');
 * Option::set('my_option', ['foo' => 'bar']);
 * $options = Option::getMultiple(['opt1', 'opt2'], 'default');
 * Option::setMultiple(['opt1' => 'value1', 'opt2' => ['foo'=>'bar']]);
 * Option::delete('my_option');
 * ```
 *
 * @package MDMasudSikdar\WpKits\Helpers
 */
class Option
{
    /**
     * Get an option and decode JSON automatically.
     *
     * @param string $key Option name
     * @param mixed  $default Default value if option does not exist
     *
     * @return mixed Option value or default
     *
     * @example
     * ```php
     * Option::get('my_option', 'default');
     * ```
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        // Retrieve option from WordPress database
        $value = get_option($key, $default);

        // Decode JSON if stored as a string
        $decoded = json_decode($value, true);

        // Return decoded value if JSON, else raw value
        return $decoded ?? $value;
    }

    /**
     * Set an option and encode arrays/objects as JSON.
     *
     * @param string $key Option name
     * @param mixed  $value Value to store (array/object will be JSON-encoded)
     *
     * @return void
     *
     * @example
     * ```php
     * Option::set('my_option', ['foo' => 'bar']);
     * ```
     */
    public static function set(string $key, mixed $value): void
    {
        // Convert arrays or objects to JSON
        if (is_array($value) || is_object($value)) {
            $value = wp_json_encode($value);
        }

        // Update option in WordPress
        update_option($key, $value);
    }

    /**
     * Retrieve multiple options at once with default fallback.
     *
     * @param string[] $keys Array of option names
     * @param mixed    $default Default value if option does not exist
     *
     * @return array Associative array of key => value
     *
     * @example
     * ```php
     * $options = Option::getMultiple(['opt1', 'opt2'], 'default');
     * ```
     */
    public static function getMultiple(array $keys, mixed $default = null): array
    {
        $results = [];

        // Loop through each key
        foreach ($keys as $key) {
            $results[$key] = self::get($key, $default);
        }

        return $results;
    }

    /**
     * Set multiple options at once.
     *
     * @param array $data Associative array of key => value
     *
     * @return void
     *
     * @example
     * ```php
     * Option::setMultiple([
     *     'opt1' => 'value1',
     *     'opt2' => ['foo' => 'bar']
     * ]);
     * ```
     */
    public static function setMultiple(array $data): void
    {
        // Loop through each key-value pair
        foreach ($data as $key => $value) {
            self::set($key, $value);
        }
    }

    /**
     * Delete an option safely.
     *
     * @param string $key Option name
     *
     * @return void
     *
     * @example
     * ```php
     * Option::delete('my_option');
     * ```
     */
    public static function delete(string $key): void
    {
        // Remove the option from WordPress database
        delete_option($key);
    }

    /**
     * Check if an option exists in the database.
     *
     * @param string $key Option name
     *
     * @return bool True if the option exists, false otherwise
     *
     * @example
     * ```php
     * if (Option::exists('my_option')) {
     *     // Do something
     * }
     * ```
     */
    public static function exists(string $key): bool
    {
        // Use get_option with a unique default to detect existence
        $uniqueDefault = '__option_not_found__';
        $value = get_option($key, $uniqueDefault);

        // Return true if value is not the unique default
        return $value !== $uniqueDefault;
    }

    /**
     * Toggle a boolean option (true/false).
     *
     * If the option does not exist, it will be created with the given default.
     *
     * @param string $key Option name
     * @param bool $default Default value if option does not exist
     *
     * @return bool The new value of the option after toggling
     *
     * @example
     * ```php
     * $newValue = Option::toggle('feature_enabled', false);
     * ```
     */
    public static function toggle(string $key, bool $default = false): bool
    {
        // Get current value, or default if not set
        $current = self::get($key, $default);

        // Determine new value (flip boolean)
        $newValue = !$current;

        // Save new value
        self::set($key, $newValue);

        // Return the updated value
        return $newValue;
    }

    /**
     * Increment a numeric option by a given step.
     *
     * If the option does not exist or is not numeric, it will be initialized to the step value.
     *
     * @param string $key Option name
     * @param int|float $step Amount to increment (default 1)
     *
     * @return int|float The new value after increment
     *
     * @example
     * ```php
     * $newCount = Option::increment('visit_count', 1);
     * ```
     */
    public static function increment(string $key, int|float $step = 1): int|float
    {
        // Get current value, defaulting to 0 if not numeric
        $current = self::get($key, 0);

        // Ensure the current value is numeric
        if (!is_numeric($current)) {
            $current = 0;
        }

        // Calculate new value
        $newValue = $current + $step;

        // Save the incremented value
        self::set($key, $newValue);

        // Return the updated value
        return $newValue;
    }

    /**
     * Decrement a numeric option by a given step.
     *
     * If the option does not exist or is not numeric, it will be initialized to 0 and decremented.
     * Optionally, a minimum floor can be set to prevent the value from going below a threshold.
     *
     * @param string $key Option name
     * @param int|float $step Amount to decrement (default 1)
     * @param int|float|null $min Optional minimum value
     *
     * @return int|float The new value after decrement
     *
     * @example
     * ```php
     * $newCount = Option::decrement('visit_count', 1, 0);
     * ```
     */
    public static function decrement(string $key, int|float $step = 1, int|float|null $min = null): int|float
    {
        // Get current value, defaulting to 0 if not numeric
        $current = self::get($key, 0);

        // Ensure the current value is numeric
        if (!is_numeric($current)) {
            $current = 0;
        }

        // Calculate new value
        $newValue = $current - $step;

        // Apply minimum floor if provided
        if ($min !== null && $newValue < $min) {
            $newValue = $min;
        }

        // Save the decremented value
        self::set($key, $newValue);

        // Return the updated value
        return $newValue;
    }

    /**
     * Append a value to an array option.
     *
     * If the option does not exist, it will be initialized as an array.
     * Ensures that the existing array is preserved.
     *
     * @param string $key Option name
     * @param mixed  $value Value to append
     *
     * @return array The updated array after appending
     *
     * @example
     * ```php
     * $updated = Option::append('my_array_option', 'new_item');
     * ```
     */
    public static function append(string $key, mixed $value): array
    {
        // Get current value, defaulting to empty array
        $current = self::get($key, []);

        // Ensure the current value is an array
        if (!is_array($current)) {
            $current = [];
        }

        // Append the new value
        $current[] = $value;

        // Save updated array
        self::set($key, $current);

        // Return updated array
        return $current;
    }

    /**
     * Remove a value from an array option.
     *
     * If the option does not exist or is not an array, nothing is done.
     *
     * @param string $key Option name
     * @param mixed  $value Value to remove
     *
     * @return array The updated array after removal
     *
     * @example
     * ```php
     * $updated = Option::removeFromArray('my_array_option', 'item_to_remove');
     * ```
     */
    public static function removeFromArray(string $key, mixed $value): array
    {
        // Get current value, defaulting to empty array
        $current = self::get($key, []);

        // Ensure the current value is an array
        if (!is_array($current)) {
            $current = [];
        }

        // Remove all occurrences of the value
        $current = array_values(array_filter($current, fn($item) => $item !== $value));

        // Save updated array
        self::set($key, $current);

        // Return updated array
        return $current;
    }

    /**
     * Merge an associative array into an array option.
     *
     * If the option does not exist, it will be initialized with the provided array.
     * Existing keys are overwritten, new keys are added.
     *
     * @param string $key Option name
     * @param array  $data Associative array to merge
     *
     * @return array The updated array after merging
     *
     * @example
     * ```php
     * $updated = Option::merge('my_array_option', ['foo' => 'bar', 'baz' => 123]);
     * ```
     */
    public static function merge(string $key, array $data): array
    {
        // Get current value, defaulting to empty array
        $current = self::get($key, []);

        // Ensure the current value is an array
        if (!is_array($current)) {
            $current = [];
        }

        // Merge new data into existing array
        $merged = array_merge($current, $data);

        // Save updated array
        self::set($key, $merged);

        // Return merged array
        return $merged;
    }

    /**
     * Prepend a value to an array option.
     *
     * If the option does not exist, it will be initialized as an array.
     * Ensures that the existing array is preserved.
     *
     * @param string $key Option name
     * @param mixed  $value Value to prepend
     *
     * @return array The updated array after prepending
     *
     * @example
     * ```php
     * $updated = Option::prepend('my_array_option', 'new_first_item');
     * ```
     */
    public static function prepend(string $key, mixed $value): array
    {
        // Get current value, defaulting to empty array
        $current = self::get($key, []);

        // Ensure the current value is an array
        if (!is_array($current)) {
            $current = [];
        }

        // Prepend the new value
        array_unshift($current, $value);

        // Save updated array
        self::set($key, $current);

        // Return updated array
        return $current;
    }

    /**
     * Clear an option safely.
     *
     * Can either delete the option completely or reset it to an empty value.
     *
     * @param string $key Option name
     * @param bool $delete If true, delete the option. If false, reset to empty string.
     *
     * @return void
     *
     * @example
     * ```php
     * Option::clear('my_option', true);  // Delete option
     * Option::clear('my_option', false); // Reset to empty string
     * ```
     */
    public static function clear(string $key, bool $delete = true): void
    {
        if ($delete) {
            // Remove the option from the database
            delete_option($key);
        } else {
            // Reset the option to an empty string
            self::set($key, '');
        }
    }

    /**
     * Get an option and always decode it as JSON.
     *
     * If the stored value is not valid JSON, returns null.
     *
     * @param string $key Option name
     * @param mixed $default Default value if option does not exist
     *
     * @return mixed|null Decoded value or default
     *
     * @example
     * ```php
     * $config = Option::getJson('my_json_option', []);
     * ```
     */
    public static function getJson(string $key, mixed $default = null): mixed
    {
        // Retrieve option from database
        $value = get_option($key, $default);

        // Decode JSON if possible
        $decoded = json_decode($value, true);

        // Return decoded value if valid, else default
        return $decoded ?? $default;
    }

    /**
     * Set an option and encode the value as JSON.
     *
     * Arrays or objects will be JSON-encoded before storing.
     *
     * @param string $key Option name
     * @param mixed  $value Value to store (array, object, or scalar)
     *
     * @return void
     *
     * @example
     * ```php
     * Option::setJson('my_json_option', ['foo' => 'bar']);
     * ```
     */
    public static function setJson(string $key, mixed $value): void
    {
        // Encode arrays or objects as JSON
        if (is_array($value) || is_object($value)) {
            $value = wp_json_encode($value);
        }

        // Save the option in WordPress database
        update_option($key, $value);
    }

    /**
     * Retrieve an option or compute and store it if it does not exist.
     *
     * This method is useful for caching values that require expensive computation.
     *
     * @param string   $key Option name
     * @param callable $callback Function to compute value if option does not exist
     *
     * @return mixed The existing or computed option value
     *
     * @example
     * ```php
     * $value = Option::remember('expensive_option', fn() => expensive_computation());
     * ```
     */
    public static function remember(string $key, callable $callback): mixed
    {
        // Check if option exists
        if (self::exists($key)) {
            // Return existing value
            return self::get($key);
        }

        // Compute the value using the callback
        $value = $callback();

        // Save the computed value
        self::set($key, $value);

        // Return the new value
        return $value;
    }

    /**
     * Retrieve a JSON option or compute and store it as JSON if it does not exist.
     *
     * Useful for caching arrays or objects that require expensive computation.
     *
     * @param string   $key Option name
     * @param callable $callback Function to compute value if option does not exist
     *
     * @return mixed The existing or computed option value (decoded from JSON)
     *
     * @example
     * ```php
     * $config = Option::rememberJson('expensive_json_option', fn() => ['foo' => 'bar']);
     * ```
     */
    public static function rememberJson(string $key, callable $callback): mixed
    {
        // Check if option exists
        if (self::exists($key)) {
            // Return decoded JSON value
            return self::getJson($key);
        }

        // Compute value using callback
        $value = $callback();

        // Store the value as JSON
        self::setJson($key, $value);

        // Return the stored value
        return $value;
    }

    /**
     * Flush all options with a specific prefix.
     *
     * Useful for resetting plugin settings or cleaning up multiple options at once.
     *
     * @param string $prefix Option prefix to match
     *
     * @return void
     *
     * @example
     * ```php
     * Option::flush('my_plugin_'); // Deletes all options starting with 'my_plugin_'
     * ```
     */
    public static function flush(string $prefix): void
    {
        global $wpdb;

        // Prepare SQL to find all options matching the prefix
        $like = $wpdb->esc_like($prefix) . '%';
        $options = $wpdb->get_col($wpdb->prepare(
            "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
            $like
        ));

        // Delete each matching option
        foreach ($options as $option) {
            delete_option($option);
        }
    }

    /**
     * Retrieve the first existing option from a list of keys.
     *
     * Useful when migrating or providing fallback options.
     *
     * @param string[] $keys Array of option names to check
     * @param mixed    $default Default value if none of the options exist
     *
     * @return mixed The first existing option value or the default
     *
     * @example
     * ```php
     * $value = Option::getWithFallback(['primary_option', 'secondary_option'], 'default');
     * ```
     */
    public static function getWithFallback(array $keys, mixed $default = null): mixed
    {
        // Loop through each key
        foreach ($keys as $key) {
            if (self::exists($key)) {
                return self::get($key);
            }
        }

        // Return default if none exist
        return $default;
    }

    /**
     * Rename an existing option to a new name.
     *
     * If the old option does not exist, nothing is done.
     * If the new option already exists, it will be overwritten.
     *
     * @param string $oldKey Existing option name
     * @param string $newKey New option name
     *
     * @return void
     *
     * @example
     * ```php
     * Option::rename('old_option_name', 'new_option_name');
     * ```
     */
    public static function rename(string $oldKey, string $newKey): void
    {
        // Check if the old option exists
        if (!self::exists($oldKey)) {
            return;
        }

        // Retrieve the current value
        $value = self::get($oldKey);

        // Save the value to the new option name
        self::set($newKey, $value);

        // Delete the old option
        self::delete($oldKey);
    }

    /**
     * Prefix an option name for consistent namespacing.
     *
     * Useful for avoiding collisions with other plugins or WordPress core options.
     *
     * @param string $key Option name
     * @param string $prefix Prefix to apply (default: plugin-specific)
     *
     * @return string Prefixed option name
     *
     * @example
     * ```php
     * $namespacedKey = Option::prefix('setting', 'my_plugin_'); // Returns 'my_plugin_setting'
     * ```
     */
    public static function prefix(string $key, string $prefix = 'plugin_'): string
    {
        // Concatenate prefix and key
        return $prefix . $key;
    }

    /**
     * Get a prefixed option value.
     *
     * Automatically applies a prefix to the option name to avoid collisions.
     *
     * @param string $key Option name
     * @param mixed  $default Default value if option does not exist
     * @param string $prefix Prefix to apply (default: 'plugin_')
     *
     * @return mixed The option value or default
     *
     * @example
     * ```php
     * $value = Option::getPrefixed('setting', 'default', 'my_plugin_'); // Retrieves 'my_plugin_setting'
     * ```
     */
    public static function getPrefixed(string $key, mixed $default = null, string $prefix = 'plugin_'): mixed
    {
        // Apply prefix to the key
        $namespacedKey = self::prefix($key, $prefix);

        // Return the option value
        return self::get($namespacedKey, $default);
    }

    /**
     * Set a prefixed option value.
     *
     * Automatically applies a prefix to the option name to avoid collisions.
     *
     * @param string $key Option name
     * @param mixed  $value Value to store
     * @param string $prefix Prefix to apply (default: 'plugin_')
     *
     * @return void
     *
     * @example
     * ```php
     * Option::setPrefixed('setting', 'value', 'my_plugin_'); // Stores 'my_plugin_setting'
     * ```
     */
    public static function setPrefixed(string $key, mixed $value, string $prefix = 'plugin_'): void
    {
        // Apply prefix to the key
        $namespacedKey = self::prefix($key, $prefix);

        // Save the value
        self::set($namespacedKey, $value);
    }

    /**
     * Delete a prefixed option safely.
     *
     * Automatically applies a prefix to the option name to avoid collisions.
     *
     * @param string $key Option name
     * @param string $prefix Prefix to apply (default: 'plugin_')
     *
     * @return void
     *
     * @example
     * ```php
     * Option::deletePrefixed('setting', 'my_plugin_'); // Deletes 'my_plugin_setting'
     * ```
     */
    public static function deletePrefixed(string $key, string $prefix = 'plugin_'): void
    {
        // Apply prefix to the key
        $namespacedKey = self::prefix($key, $prefix);

        // Delete the option
        self::delete($namespacedKey);
    }

    /**
     * Get a nested value from an array option using dot notation.
     *
     * If the option or nested key does not exist, returns the default value.
     *
     * @param string $key Option name
     * @param string $path Dot-notated path to the nested key (e.g., 'settings.display.color')
     * @param mixed  $default Default value if key does not exist
     *
     * @return mixed The value at the nested key or default
     *
     * @example
     * ```php
     * $color = Option::getNested('my_array_option', 'settings.display.color', 'blue');
     * ```
     */
    public static function getNested(string $key, string $path, mixed $default = null): mixed
    {
        // Retrieve the option as an array
        $array = self::get($key, []);

        // Ensure the option is an array
        if (!is_array($array)) {
            return $default;
        }

        // Split the path into keys
        $segments = explode('.', $path);

        // Traverse the nested array
        foreach ($segments as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }
            $array = $array[$segment];
        }

        // Return the found value
        return $array;
    }

    /**
     * Set a nested value in an array option using dot notation.
     *
     * If the option or nested keys do not exist, they will be created automatically.
     *
     * @param string $key Option name
     * @param string $path Dot-notated path to the nested key (e.g., 'settings.display.color')
     * @param mixed  $value Value to set at the nested key
     *
     * @return void
     *
     * @example
     * ```php
     * Option::setNested('my_array_option', 'settings.display.color', 'red');
     * ```
     */
    public static function setNested(string $key, string $path, mixed $value): void
    {
        // Retrieve the current option as an array
        $array = self::get($key, []);

        // Ensure it is an array
        if (!is_array($array)) {
            $array = [];
        }

        // Split the path into keys
        $segments = explode('.', $path);

        // Reference to traverse the array
        $ref = &$array;

        // Traverse and create nested keys if necessary
        foreach ($segments as $segment) {
            if (!isset($ref[$segment]) || !is_array($ref[$segment])) {
                $ref[$segment] = [];
            }
            $ref = &$ref[$segment];
        }

        // Set the final value
        $ref = $value;

        // Save the updated option back
        self::set($key, $array);
    }

    /**
     * Merge an array into a nested path of an option using dot notation.
     *
     * Existing values are preserved; new values are added or overwritten.
     *
     * @param string $key Option name
     * @param string $path Dot-notated path to the nested key (e.g., 'settings.display')
     * @param array  $values Array of values to merge
     *
     * @return void
     *
     * @example
     * ```php
     * Option::mergeNested('my_array_option', 'settings.display', ['color' => 'red', 'size' => 'large']);
     * ```
     */
    public static function mergeNested(string $key, string $path, array $values): void
    {
        // Retrieve the current option as an array
        $array = self::get($key, []);

        // Ensure it is an array
        if (!is_array($array)) {
            $array = [];
        }

        // Split the path into keys
        $segments = explode('.', $path);

        // Reference to traverse the array
        $ref = &$array;

        // Traverse and create nested keys if necessary
        foreach ($segments as $segment) {
            if (!isset($ref[$segment]) || !is_array($ref[$segment])) {
                $ref[$segment] = [];
            }
            $ref = &$ref[$segment];
        }

        // Merge the new values into the nested array
        $ref = array_merge($ref, $values);

        // Save the updated option back
        self::set($key, $array);
    }

    /**
     * Delete a nested key from an array option using dot notation.
     *
     * If the nested key does not exist, nothing happens.
     *
     * @param string $key Option name
     * @param string $path Dot-notated path to the nested key (e.g., 'settings.display.color')
     *
     * @return void
     *
     * @example
     * ```php
     * Option::deleteNested('my_array_option', 'settings.display.color');
     * ```
     */
    public static function deleteNested(string $key, string $path): void
    {
        // Retrieve the current option as an array
        $array = self::get($key, []);

        // Ensure it is an array
        if (!is_array($array)) {
            return;
        }

        // Split the path into keys
        $segments = explode('.', $path);

        // Reference to traverse the array
        $ref = &$array;

        // Traverse to the parent of the key to delete
        foreach ($segments as $i => $segment) {
            if (!isset($ref[$segment])) {
                return; // Key does not exist, nothing to delete
            }

            // If last segment, unset the key
            if ($i === count($segments) - 1) {
                unset($ref[$segment]);
                break;
            }

            $ref = &$ref[$segment];
        }

        // Save the updated option back
        self::set($key, $array);
    }

    /**
     * Check if a nested key exists in an array option using dot notation.
     *
     * @param string $key Option name
     * @param string $path Dot-notated path to the nested key (e.g., 'settings.display.color')
     *
     * @return bool True if the nested key exists, false otherwise
     *
     * @example
     * ```php
     * if (Option::existsNested('my_array_option', 'settings.display.color')) {
     *     // Key exists
     * }
     * ```
     */
    public static function existsNested(string $key, string $path): bool
    {
        // Retrieve the option as an array
        $array = self::get($key, []);

        // Ensure it is an array
        if (!is_array($array)) {
            return false;
        }

        // Split the path into keys
        $segments = explode('.', $path);

        // Traverse the array
        foreach ($segments as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return false;
            }
            $array = $array[$segment];
        }

        // Key exists
        return true;
    }

    /**
     * Retrieve a nested value or set it to a default if it does not exist.
     *
     * Automatically creates missing nested paths and stores the default value.
     *
     * @param string $key Option name
     * @param string $path Dot-notated path to the nested key (e.g., 'settings.display.color')
     * @param mixed  $default Default value to set if the nested key does not exist
     *
     * @return mixed The existing or newly set value
     *
     * @example
     * ```php
     * $color = Option::rememberNested('my_array_option', 'settings.display.color', 'blue');
     * ```
     */
    public static function rememberNested(string $key, string $path, mixed $default): mixed
    {
        // Check if the nested key exists
        if (self::existsNested($key, $path)) {
            return self::getNested($key, $path);
        }

        // Set the default value
        self::setNested($key, $path, $default);

        // Return the default value
        return $default;
    }
}
