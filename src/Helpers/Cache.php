<?php

namespace MDMasudSikdar\WpKits\Helpers;

/**
 * Class Cache
 *
 * Centralized caching utility for WordPress using transients.
 *
 * Features:
 * ✅ Cache expensive queries or computations temporarily
 * ✅ Automatic computation on cache miss via callback
 * ✅ Safe cache key generation
 * ✅ Optional full cache clearing for plugin-specific keys
 *
 * Example usage:
 * ```php
 * use MDMasudSikdar\WpKits\Helpers\Cache;
 *
 * // Cache top-selling products for 1 hour
 * $topProducts = Cache::get(
 *     Cache::makeKey('top_selling_products_30d'),
 *     function() {
 *         return WooCommerce::getTopSellingProducts(30);
 *     },
 *     3600
 * );
 *
 * // Delete a specific cache key
 * Cache::delete(Cache::makeKey('top_selling_products_30d'));
 *
 * // Clear all plugin-specific cache
 * Cache::clearAll('myplugin_');
 * ```
 *
 * @package MDMasudSikdar\WpKits\Helpers
 */
class Cache
{
    /**
     * Retrieve a cached value or compute it if not present.
     *
     * @param string   $key        Unique cache key
     * @param callable $callback   Function to generate value if cache miss
     * @param int      $expiration Cache lifetime in seconds (default 1 hour)
     *
     * @return mixed Cached or computed value
     *
     * @example
     * ```php
     * $value = Cache::get('my_key', function() {
     *     return computeExpensiveValue();
     * }, 3600);
     * ```
     */
    public static function get(string $key, callable $callback, int $expiration = 3600)
    {
        // Attempt to retrieve value from WordPress transient
        $cached = get_transient($key);

        // Return cached value if exists
        if ($cached !== false) {
            return $cached;
        }

        // Cache miss: compute the value using the provided callback
        $value = $callback();

        // Store computed value in transient with expiration
        set_transient($key, $value, $expiration);

        // Return the computed value
        return $value;
    }

    /**
     * Delete a specific cached value by key.
     *
     * @param string $key Cache key to delete
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::delete('my_key');
     * ```
     */
    public static function delete(string $key): void
    {
        // Delete the transient using WordPress function
        delete_transient($key);
    }

    /**
     * Clear all plugin-specific cached values.
     *
     * Use a unique prefix to avoid deleting unrelated transients.
     *
     * @param string $prefix Optional prefix for identifying plugin transients
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::clearAll('myplugin_');
     * ```
     */
    public static function clearAll(string $prefix = 'myplugin_'): void
    {
        global $wpdb;

        // Delete all transients with the given prefix from the database
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                $wpdb->esc_like('_transient_' . $prefix) . '%'
            )
        );
    }

    /**
     * Generate a safe transient key.
     *
     * Ensures the key is lowercase, alphanumeric, and within transient key length limits.
     *
     * @param string $key Base key
     *
     * @return string Safe transient key
     *
     * @example
     * ```php
     * $safeKey = Cache::makeKey('Top_Selling_Products_30D');
     * ```
     */
    public static function makeKey(string $key): string
    {
        // Replace non-alphanumeric characters with underscores
        $key = strtolower(preg_replace('/[^a-z0-9_]/', '_', $key));

        // Prefix with plugin identifier and truncate to max 45 characters
        return 'myplugin_' . substr($key, 0, 45);
    }

    /**
     * Check if a cached value exists for the given key.
     *
     * @param string $key Cache key to check
     *
     * @return bool True if cache exists, false otherwise
     *
     * @example
     * ```php
     * if (Cache::has(Cache::makeKey('my_key'))) {
     *     // Do something knowing cache exists
     * }
     * ```
     */
    public static function has(string $key): bool
    {
        return get_transient($key) !== false;
    }

    /**
     * Retrieve a cached value if it exists; otherwise compute, cache, and return it.
     *
     * @param string   $key        Cache key
     * @param callable $callback   Function to generate value if cache miss
     * @param int      $expiration Cache lifetime in seconds (default 1 hour)
     *
     * @return mixed Cached or computed value
     */
    public static function remember(string $key, callable $callback, int $expiration = 3600): mixed
    {
        if (self::has($key)) {
            return get_transient($key);
        }

        return self::get($key, $callback, $expiration);
    }

    /**
     * Increment a numeric cached value by a given amount.
     *
     * @param string $key       Cache key
     * @param int    $amount    Amount to increment (default 1)
     * @param int    $expiration Expiration in seconds if key does not exist
     *
     * @return int New value
     */
    public static function increment(string $key, int $amount = 1, int $expiration = 3600): int
    {
        $value = self::get($key, function() { return 0; }, $expiration);

        $value += $amount;
        set_transient($key, $value, $expiration);

        return $value;
    }

    /**
     * Decrement a numeric cached value by a given amount.
     *
     * @param string $key       Cache key
     * @param int    $amount    Amount to decrement (default 1)
     * @param int    $expiration Expiration in seconds if key does not exist
     *
     * @return int New value
     */
    public static function decrement(string $key, int $amount = 1, int $expiration = 3600): int
    {
        return self::increment($key, -$amount, $expiration);
    }

    /**
     * Store a value in cache indefinitely.
     *
     * @param string $key   Cache key
     * @param mixed  $value Value to store
     *
     * @return void
     */
    public static function forever(string $key, $value): void
    {
        set_transient($key, $value, 0);
    }

    /**
     * Delete all cached transients, optionally filtered by prefix.
     *
     * @param string|null $prefix Optional prefix for filtering
     *
     * @return void
     */
    public static function deleteAll(?string $prefix = null): void
    {
        global $wpdb;

        $like = $prefix ? $wpdb->esc_like('_transient_' . $prefix) . '%' : '_transient_%';

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                $like
            )
        );
    }

    /**
     * Retrieve a cached value or compute and store it indefinitely.
     *
     * @param string   $key      Cache key
     * @param callable $callback Function to generate value if cache miss
     *
     * @return mixed Cached or computed value
     *
     * @example
     * ```php
     * $value = Cache::rememberForever(Cache::makeKey('my_forever_key'), function() {
     *     return computeExpensiveValue();
     * });
     * ```
     */
    public static function rememberForever(string $key, callable $callback)
    {
        if (self::has($key)) {
            return get_transient($key);
        }

        $value = $callback();
        self::forever($key, $value);

        return $value;
    }

    /**
     * Retrieve a cached value and immediately delete it.
     *
     * Useful for “one-time” cache retrieval.
     *
     * @param string $key Cache key
     *
     * @return mixed Cached value or false if not exists
     *
     * @example
     * ```php
     * $tempValue = Cache::pull(Cache::makeKey('temp_key'));
     * ```
     */
    public static function pull(string $key)
    {
        $value = get_transient($key);

        if ($value !== false) {
            self::delete($key);
        }

        return $value;
    }

    /**
     * Cache multiple key-value pairs at once.
     *
     * @param array $items      Key-value array to cache
     * @param int   $expiration Cache lifetime in seconds (default 1 hour)
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::cacheMultiple([
     *     Cache::makeKey('a') => 'Value A',
     *     Cache::makeKey('b') => 'Value B'
     * ], 1800);
     * ```
     */
    public static function cacheMultiple(array $items, int $expiration = 3600): void
    {
        foreach ($items as $key => $value) {
            set_transient($key, $value, $expiration);
        }
    }

    /**
     * Retrieve multiple cached values at once.
     *
     * Returns null for keys that do not exist.
     *
     * @param array $keys Array of cache keys
     *
     * @return array Key-value pairs of cached results
     *
     * @example
     * ```php
     * $values = Cache::getMultiple([
     *     Cache::makeKey('a'),
     *     Cache::makeKey('b')
     * ]);
     * ```
     */
    public static function getMultiple(array $keys): array
    {
        $results = [];

        foreach ($keys as $key) {
            $results[$key] = get_transient($key);
        }

        return $results;
    }

    /**
     * Delete multiple cache keys at once.
     *
     * @param array $keys Array of cache keys to delete
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::deleteMultiple([
     *     Cache::makeKey('a'),
     *     Cache::makeKey('b')
     * ]);
     * ```
     */
    public static function deleteMultiple(array $keys): void
    {
        foreach ($keys as $key) {
            self::delete($key);
        }
    }

    /**
     * Assign a cache key to a specific tag.
     *
     * Tags allow grouping of cache keys for bulk deletion.
     *
     * @param string $key Cache key
     * @param string $tag Tag name
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::tag(Cache::makeKey('product_123'), 'products');
     * ```
     */
    public static function tag(string $key, string $tag): void
    {
        $tagKey = 'tag_' . $tag;
        $keys = get_transient($tagKey) ?: [];
        if (!in_array($key, $keys, true)) {
            $keys[] = $key;
            set_transient($tagKey, $keys, 0); // store indefinitely
        }
    }

    /**
     * Retrieve all cache keys assigned to a specific tag.
     *
     * @param string $tag Tag name
     *
     * @return array Array of cache keys
     *
     * @example
     * ```php
     * $keys = Cache::getTaggedKeys('products');
     * ```
     */
    public static function getTaggedKeys(string $tag): array
    {
        $tagKey = 'tag_' . $tag;
        return get_transient($tagKey) ?: [];
    }

    /**
     * Delete all cache entries associated with a specific tag.
     *
     * @param string $tag Tag name
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::clearTag('products');
     * ```
     */
    public static function clearTag(string $tag): void
    {
        $keys = self::getTaggedKeys($tag);
        foreach ($keys as $key) {
            self::delete($key);
        }
        delete_transient('tag_' . $tag);
    }

    /**
     * Store a value and automatically assign it to one or more tags.
     *
     * @param string       $key        Cache key
     * @param mixed        $value      Value to store
     * @param int          $expiration Cache lifetime in seconds
     * @param string|array $tags       Tag or array of tags to associate
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::setWithTags(
     *     Cache::makeKey('product_123'),
     *     $productData,
     *     3600,
     *     ['products', 'featured']
     * );
     * ```
     */
    public static function setWithTags(string $key, mixed $value, int $expiration = 3600, string|array $tags = []): void
    {
        set_transient($key, $value, $expiration);

        foreach ((array)$tags as $tag) {
            self::tag($key, $tag);
        }
    }

    /**
     * Check if a cache key exists within a specific tag.
     *
     * @param string $key Cache key
     * @param string $tag Tag name
     *
     * @return bool True if key exists under tag, false otherwise
     *
     * @example
     * ```php
     * if (Cache::hasTag(Cache::makeKey('product_123'), 'products')) {
     *     // Do something
     * }
     * ```
     */
    public static function hasTag(string $key, string $tag): bool
    {
        $keys = self::getTaggedKeys($tag);
        return in_array($key, $keys, true);
    }

    /**
     * Clear all cache entries that match a specific prefix.
     *
     * @param string $prefix Prefix to filter cache keys
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::clearPrefix('myplugin_');
     * ```
     */
    public static function clearPrefix(string $prefix): void
    {
        global $wpdb;

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                $wpdb->esc_like('_transient_' . $prefix) . '%'
            )
        );
    }

    /**
     * Update the expiration time of a cached key.
     *
     * @param string $key        Cache key
     * @param int    $expiration New expiration time in seconds
     *
     * @return bool True on success, false if key does not exist
     *
     * @example
     * ```php
     * Cache::updateExpiration(Cache::makeKey('my_key'), 7200);
     * ```
     */
    public static function updateExpiration(string $key, int $expiration): bool
    {
        $value = get_transient($key);

        if ($value === false) {
            return false;
        }

        set_transient($key, $value, $expiration);
        return true;
    }

    /**
     * Retrieve all cache keys currently stored in the database.
     *
     * @return array Array of cache keys
     *
     * @example
     * ```php
     * $keys = Cache::allKeys();
     * ```
     */
    public static function allKeys(): array
    {
        global $wpdb;

        $results = $wpdb->get_col(
            "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'"
        );

        return array_map(fn($key) => preg_replace('/^_transient_/', '', $key), $results);
    }

    /**
     * Count the total number of cache entries in the database.
     *
     * @return int Number of cache entries
     *
     * @example
     * ```php
     * $total = Cache::count();
     * ```
     */
    public static function count(): int
    {
        global $wpdb;

        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'"
        );
    }

    /**
     * Check if the cache is empty.
     *
     * @return bool True if no cache entries exist, false otherwise
     *
     * @example
     * ```php
     * if (Cache::isEmpty()) {
     *     // Cache is empty
     * }
     * ```
     */
    public static function isEmpty(): bool
    {
        return self::count() === 0;
    }

    /**
     * Cache a value only if it does not already exist.
     *
     * @param string $key        Cache key
     * @param mixed  $value      Value to cache
     * @param int    $expiration Cache lifetime in seconds (default 1 hour)
     *
     * @return bool True if value was cached, false if key already exists
     *
     * @example
     * ```php
     * Cache::add(Cache::makeKey('my_key'), $value, 3600);
     * ```
     */
    public static function add(string $key, $value, int $expiration = 3600): bool
    {
        if (self::has($key)) {
            return false;
        }

        set_transient($key, $value, $expiration);
        return true;
    }

    /**
     * Retrieve a value from cache or execute a fallback if missing, without storing.
     *
     * @param string   $key      Cache key
     * @param callable $fallback Function to call if key is missing
     *
     * @return mixed Cached value or fallback result
     *
     * @example
     * ```php
     * $value = Cache::getOrFallback(Cache::makeKey('my_key'), function() {
     *     return computeFallbackValue();
     * });
     * ```
     */
    public static function getOrFallback(string $key, callable $fallback)
    {
        $value = get_transient($key);

        return $value !== false ? $value : $fallback();
    }

    /**
     * Cache a value lazily using a callable. Only executed when needed.
     *
     * @param string   $key        Cache key
     * @param callable $callback   Function to generate the value
     * @param int      $expiration Cache lifetime in seconds (default 1 hour)
     *
     * @return mixed Cached or computed value
     *
     * @example
     * ```php
     * $value = Cache::lazy(Cache::makeKey('my_lazy_key'), function() {
     *     return computeExpensiveValue();
     * }, 3600);
     * ```
     */
    public static function lazy(string $key, callable $callback, int $expiration = 3600): mixed
    {
        return self::get($key, $callback, $expiration);
    }

    /**
     * Retrieve a cached value or fallback to another cache key.
     *
     * @param string $key        Primary cache key
     * @param string $fallbackKey Fallback cache key if primary does not exist
     *
     * @return mixed Value from primary or fallback cache, false if neither exists
     *
     * @example
     * ```php
     * $value = Cache::getOrFallbackKey(Cache::makeKey('primary'), Cache::makeKey('secondary'));
     * ```
     */
    public static function getOrFallbackKey(string $key, string $fallbackKey): mixed
    {
        $value = get_transient($key);

        if ($value !== false) {
            return $value;
        }

        return get_transient($fallbackKey);
    }

    /**
     * Update the value of a cache key only if it exists.
     *
     * @param string $key   Cache key
     * @param mixed  $value New value
     *
     * @return bool True if key existed and was updated, false otherwise
     *
     * @example
     * ```php
     * Cache::updateIfExists(Cache::makeKey('my_key'), $newValue);
     * ```
     */
    public static function updateIfExists(string $key, mixed $value): bool
    {
        if (!self::has($key)) {
            return false;
        }

        $expiration = 3600; // default expiration, could be enhanced later
        set_transient($key, $value, $expiration);
        return true;
    }

    /**
     * Retrieve the remaining TTL (time-to-live) for a cache key.
     *
     * @param string $key Cache key
     *
     * @return int|false Seconds remaining, or false if key does not exist
     *
     * @example
     * ```php
     * $ttl = Cache::timeToLive(Cache::makeKey('my_key'));
     * ```
     */
    public static function timeToLive(string $key)
    {
        global $wpdb;

        $option_name = '_transient_timeout_' . $key;
        $timeout = $wpdb->get_var(
            $wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $option_name)
        );

        if ($timeout === null) {
            return false;
        }

        return max(0, (int)$timeout - time());
    }

    /**
     * Increment a cache value by a given amount, creating it if missing.
     *
     * @param string $key        Cache key
     * @param int    $amount     Amount to increment (default 1)
     * @param int    $expiration Expiration if key does not exist (default 1 hour)
     *
     * @return int New value
     *
     * @example
     * ```php
     * $count = Cache::incrementOrCreate(Cache::makeKey('views'), 1, 3600);
     * ```
     */
    public static function incrementOrCreate(string $key, int $amount = 1, int $expiration = 3600): int
    {
        $value = get_transient($key);

        if ($value === false) {
            $value = $amount;
        } else {
            $value += $amount;
        }

        set_transient($key, $value, $expiration);
        return $value;
    }

    /**
     * Decrement a cache value by a given amount, creating it if missing.
     *
     * @param string $key        Cache key
     * @param int    $amount     Amount to decrement (default 1)
     * @param int    $expiration Expiration if key does not exist (default 1 hour)
     *
     * @return int New value
     *
     * @example
     * ```php
     * $count = Cache::decrementOrCreate(Cache::makeKey('downloads'), 1, 3600);
     * ```
     */
    public static function decrementOrCreate(string $key, int $amount = 1, int $expiration = 3600): int
    {
        return self::incrementOrCreate($key, -$amount, $expiration);
    }

    /**
     * Retrieve all cache keys associated with multiple tags.
     *
     * @param array $tags Array of tag names
     *
     * @return array Array of unique cache keys
     *
     * @example
     * ```php
     * $keys = Cache::getKeysByTags(['products', 'featured']);
     * ```
     */
    public static function getKeysByTags(array $tags): array
    {
        $allKeys = [];

        foreach ($tags as $tag) {
            $allKeys = array_merge($allKeys, self::getTaggedKeys($tag));
        }

        return array_unique($allKeys);
    }

    /**
     * Clear all cache entries associated with multiple tags.
     *
     * @param array $tags Array of tag names
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::clearTags(['products', 'featured']);
     * ```
     */
    public static function clearTags(array $tags): void
    {
        foreach ($tags as $tag) {
            self::clearTag($tag);
        }
    }

    /**
     * Compute and cache multiple values lazily using callables.
     *
     * Each key in the array should map to a callable function that computes its value.
     *
     * @param array $keyCallbackPairs Array of ['key' => callable] pairs
     * @param int   $expiration       Cache lifetime in seconds (default 1 hour)
     *
     * @return array Array of cached or computed values
     *
     * @example
     * ```php
     * $values = Cache::lazyBatch([
     *     Cache::makeKey('a') => fn() => computeA(),
     *     Cache::makeKey('b') => fn() => computeB()
     * ], 3600);
     * ```
     */
    public static function lazyBatch(array $keyCallbackPairs, int $expiration = 3600): array
    {
        $results = [];
        foreach ($keyCallbackPairs as $key => $callback) {
            $results[$key] = self::get($key, $callback, $expiration);
        }
        return $results;
    }

    /**
     * Store a value only if a condition callback returns true.
     *
     * @param string   $key        Cache key
     * @param mixed    $value      Value to cache
     * @param callable $condition  Function that returns true to store, false otherwise
     * @param int      $expiration Cache lifetime in seconds (default 1 hour)
     *
     * @return bool True if value was stored, false otherwise
     *
     * @example
     * ```php
     * Cache::storeIf(Cache::makeKey('my_key'), $value, fn() => $value > 0, 3600);
     * ```
     */
    public static function storeIf(string $key, $value, callable $condition, int $expiration = 3600): bool
    {
        if ($condition()) {
            set_transient($key, $value, $expiration);
            return true;
        }
        return false;
    }

    /**
     * Retrieve a value from the first available key in a fallback hierarchy.
     *
     * @param array $keys Array of cache keys in priority order
     *
     * @return mixed Value from the first existing cache key, false if none exist
     *
     * @example
     * ```php
     * $value = Cache::getFirstAvailable([
     *     Cache::makeKey('primary'),
     *     Cache::makeKey('secondary'),
     *     Cache::makeKey('tertiary')
     * ]);
     * ```
     */
    public static function getFirstAvailable(array $keys)
    {
        foreach ($keys as $key) {
            $value = get_transient($key);
            if ($value !== false) {
                return $value;
            }
        }
        return false;
    }

    /**
     * Refresh the expiration of multiple cache keys at once.
     *
     * @param array $keys       Array of cache keys
     * @param int   $expiration New expiration time in seconds
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::refreshExpiration([
     *     Cache::makeKey('a'),
     *     Cache::makeKey('b')
     * ], 7200);
     * ```
     */
    public static function refreshExpiration(array $keys, int $expiration): void
    {
        foreach ($keys as $key) {
            $value = get_transient($key);
            if ($value !== false) {
                set_transient($key, $value, $expiration);
            }
        }
    }

    /**
     * Retrieve basic cache statistics: total keys and keys per tag.
     *
     * @return array ['total' => int, 'tags' => array]
     *
     * @example
     * ```php
     * $stats = Cache::stats();
     * ```
     */
    public static function stats(): array
    {
        global $wpdb;

        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'");

        // Retrieve tags and their counts
        $tagResults = $wpdb->get_results("SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE '_transient_tag_%'");
        $tags = [];
        foreach ($tagResults as $row) {
            $tagName = preg_replace('/^_transient_tag_/', '', $row->option_name);
            $keys = maybe_unserialize($row->option_value);
            $tags[$tagName] = is_array($keys) ? count($keys) : 0;
        }

        return ['total' => $total, 'tags' => $tags];
    }

    /**
     * Associate a cache key with one or more dependency keys.
     *
     * When a dependency changes, associated keys can be automatically invalidated.
     *
     * @param string       $key          Cache key
     * @param string|array $dependencies Dependency key or array of keys
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::dependsOn(Cache::makeKey('product_123_data'), Cache::makeKey('product_123'));
     * ```
     */
    public static function dependsOn(string $key, string|array $dependencies): void
    {
        $depKey = 'dependency_' . $key;
        $existing = get_transient($depKey) ?: [];
        $dependencies = (array)$dependencies;
        $merged = array_unique(array_merge($existing, $dependencies));
        set_transient($depKey, $merged, 0); // store indefinitely
    }

    /**
     * Invalidate cache keys that depend on a specific key.
     *
     * @param string $dependencyKey Dependency key whose dependents should be cleared
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::invalidateDependents(Cache::makeKey('product_123'));
     * ```
     */
    public static function invalidateDependents(string $dependencyKey): void
    {
        global $wpdb;

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
                $wpdb->esc_like('_transient_dependency_') . '%'
            )
        );

        foreach ($rows as $row) {
            $keys = maybe_unserialize($row->option_value);
            if (in_array($dependencyKey, (array)$keys, true)) {
                $key = preg_replace('/^_transient_dependency_/', '', $row->option_name);
                self::delete($key);
                self::delete($row->option_name); // remove dependency record
            }
        }
    }

    /**
     * Retrieve a cached value or automatically recompute if expired or missing.
     *
     * @param string   $key        Cache key
     * @param callable $callback   Function to compute value if missing
     * @param int      $expiration Cache lifetime in seconds
     *
     * @return mixed Cached or recomputed value
     *
     * @example
     * ```php
     * $data = Cache::getOrRecompute(Cache::makeKey('stats'), fn() => computeStats(), 3600);
     * ```
     */
    public static function getOrRecompute(string $key, callable $callback, int $expiration = 3600)
    {
        $value = get_transient($key);

        if ($value === false) {
            $value = $callback();
            set_transient($key, $value, $expiration);
        }

        return $value;
    }

    /**
     * Persist a cache key permanently by copying it to the options table.
     *
     * Useful for critical values that must survive transient cleanup.
     *
     * @param string $key Cache key to persist
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::persist(Cache::makeKey('critical_data'));
     * ```
     */
    public static function persist(string $key): void
    {
        $value = get_transient($key);
        if ($value !== false) {
            update_option('persistent_' . $key, $value, false);
        }
    }

    /**
     * Remove a persisted cache key from the options table.
     *
     * @param string $key Cache key to remove
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::unpersist(Cache::makeKey('critical_data'));
     * ```
     */
    public static function unpersist(string $key): void
    {
        delete_option('persistent_' . $key);
    }

    /**
     * Store a cache value under a parent key to allow hierarchical clearing.
     *
     * @param string $parentKey Parent cache key
     * @param string $key       Child cache key
     * @param mixed  $value     Value to store
     * @param int    $expiration Cache lifetime in seconds (default 1 hour)
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::storeUnderParent(Cache::makeKey('products'), Cache::makeKey('product_123'), $data, 3600);
     * ```
     */
    public static function storeUnderParent(string $parentKey, string $key, $value, int $expiration = 3600): void
    {
        set_transient($key, $value, $expiration);

        $children = get_transient('children_' . $parentKey) ?: [];
        if (!in_array($key, $children, true)) {
            $children[] = $key;
            set_transient('children_' . $parentKey, $children, 0);
        }
    }

    /**
     * Clear all cache keys under a parent key.
     *
     * @param string $parentKey Parent cache key
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::clearParent(Cache::makeKey('products'));
     * ```
     */
    public static function clearParent(string $parentKey): void
    {
        $children = get_transient('children_' . $parentKey) ?: [];
        foreach ($children as $childKey) {
            self::delete($childKey);
        }
        delete_transient('children_' . $parentKey);
    }

    /**
     * Schedule a cache key to be refreshed after a certain interval using a callback.
     *
     * @param string   $key        Cache key
     * @param callable $callback   Function to compute value
     * @param int      $interval   Interval in seconds to refresh cache
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::scheduleRefresh(Cache::makeKey('stats'), fn() => computeStats(), 3600);
     * ```
     */
    public static function scheduleRefresh(string $key, callable $callback, int $interval): void
    {
        $value = get_transient($key);
        if ($value === false) {
            $value = $callback();
        }
        set_transient($key, $value, $interval);
    }

    /**
     * Store a value only if another cache key exists.
     *
     * @param string $key        Cache key to store
     * @param mixed  $value      Value to store
     * @param string $existsKey  Cache key that must exist
     * @param int    $expiration Expiration in seconds
     *
     * @return bool True if stored, false otherwise
     *
     * @example
     * ```php
     * Cache::storeIfExists(Cache::makeKey('child'), $value, Cache::makeKey('parent'), 3600);
     * ```
     */
    public static function storeIfExists(string $key, $value, string $existsKey, int $expiration = 3600): bool
    {
        if (self::has($existsKey)) {
            set_transient($key, $value, $expiration);
            return true;
        }
        return false;
    }

    /**
     * Retrieve statistics for a specific tag: number of keys and total TTL remaining.
     *
     * @param string $tag Tag name
     *
     * @return array ['count' => int, 'ttl_sum' => int]
     *
     * @example
     * ```php
     * $tagStats = Cache::tagStats('products');
     * ```
     */
    public static function tagStats(string $tag): array
    {
        $keys = self::getTaggedKeys($tag);
        $ttlSum = 0;

        foreach ($keys as $key) {
            $ttl = self::timeToLive($key);
            $ttlSum += $ttl !== false ? $ttl : 0;
        }

        return ['count' => count($keys), 'ttl_sum' => $ttlSum];
    }

    /**
     * Set a cache key with a dependency chain.
     *
     * When a parent or dependency key is updated, all dependent keys are automatically invalidated.
     *
     * @param string       $key           Cache key
     * @param mixed        $value         Value to store
     * @param string|array $dependencies  Dependency key(s)
     * @param int          $expiration    Cache lifetime in seconds (default 1 hour)
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::setWithDependencies(Cache::makeKey('child_key'), $value, Cache::makeKey('parent_key'), 3600);
     * ```
     */
    public static function setWithDependencies(string $key, $value, string|array $dependencies, int $expiration = 3600): void
    {
        // Store the value in the cache
        set_transient($key, $value, $expiration);

        // Normalize dependencies into an array
        $dependencies = (array)$dependencies;

        // Loop through each dependency
        foreach ($dependencies as $dep) {
            // Fetch existing dependents for this dependency
            $dependents = get_transient('dependents_' . $dep) ?: [];

            // Add the current key to the dependents list if not already present
            if (!in_array($key, $dependents, true)) {
                $dependents[] = $key;
            }

            // Store the updated dependents list indefinitely
            set_transient('dependents_' . $dep, $dependents, 0);
        }
    }

    /**
     * Invalidate a cache key and all keys that depend on it.
     *
     * @param string $key Cache key to invalidate
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::invalidateWithDependencies(Cache::makeKey('parent_key'));
     * ```
     */
    public static function invalidateWithDependencies(string $key): void
    {
        // Delete the primary cache key
        self::delete($key);

        // Retrieve all keys that depend on this key
        $dependents = get_transient('dependents_' . $key) ?: [];

        // Loop through each dependent key
        foreach ($dependents as $depKey) {
            // Recursively delete dependent keys
            self::invalidateWithDependencies($depKey);
        }

        // Remove the dependents record
        delete_transient('dependents_' . $key);
    }

    /**
     * Refresh a cache key only if its value satisfies a condition.
     *
     * @param string   $key        Cache key
     * @param callable $callback   Function to recompute value
     * @param callable $condition  Function that returns true to refresh, false otherwise
     * @param int      $expiration Cache lifetime in seconds (default 1 hour)
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::refreshIf(Cache::makeKey('stats'), fn() => computeStats(), fn($val) => $val['count'] < 100, 3600);
     * ```
     */
    public static function refreshIf(string $key, callable $callback, callable $condition, int $expiration = 3600): void
    {
        // Get the current value of the cache key
        $value = get_transient($key);

        // Check if the value exists and satisfies the condition
        if ($value === false || $condition($value)) {
            // Recompute the value using the callback
            $value = $callback();

            // Store the new value in cache
            set_transient($key, $value, $expiration);
        }
    }

    /**
     * Atomically update a cache key with a callback function.
     *
     * Ensures the latest value is computed and stored in a thread-safe manner.
     *
     * @param string   $key        Cache key
     * @param callable $callback   Function that takes the old value and returns a new value
     * @param int      $expiration Cache lifetime in seconds (default 1 hour)
     *
     * @return mixed Updated value
     *
     * @example
     * ```php
     * $newValue = Cache::atomicUpdate(Cache::makeKey('counter'), fn($old) => ($old ?? 0) + 1, 3600);
     * ```
     */
    public static function atomicUpdate(string $key, callable $callback, int $expiration = 3600): mixed
    {
        // Fetch the current value from cache
        $oldValue = get_transient($key);

        // Compute the new value using the callback
        $newValue = $callback($oldValue);

        // Store the new value in cache
        set_transient($key, $newValue, $expiration);

        // Return the updated value
        return $newValue;
    }

    /**
     * Retrieve multiple cache keys and recompute missing ones using callbacks.
     *
     * @param array $keyCallbackPairs Array of ['key' => callable] pairs
     * @param int   $expiration       Cache lifetime in seconds (default 1 hour)
     *
     * @return array Array of cached or recomputed values
     *
     * @example
     * ```php
     * $results = Cache::getOrRecomputeBatch([
     *     Cache::makeKey('a') => fn() => computeA(),
     *     Cache::makeKey('b') => fn() => computeB()
     * ], 3600);
     * ```
     */
    public static function getOrRecomputeBatch(array $keyCallbackPairs, int $expiration = 3600): array
    {
        $results = [];

        // Loop through each key-callback pair
        foreach ($keyCallbackPairs as $key => $callback) {
            // Use getOrRecompute for each key
            $results[$key] = self::getOrRecompute($key, $callback, $expiration);
        }

        return $results;
    }

    /**
     * Assign multiple cache keys to a single hierarchical tag.
     *
     * Useful for grouping related keys under a parent tag for bulk operations.
     *
     * @param string $parentTag Parent tag name
     * @param array  $keys      Array of cache keys
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::tagMultipleUnderParent('products', [
     *     Cache::makeKey('product_1'),
     *     Cache::makeKey('product_2')
     * ]);
     * ```
     */
    public static function tagMultipleUnderParent(string $parentTag, array $keys): void
    {
        // Fetch existing children for the parent tag
        $children = get_transient('children_tag_' . $parentTag) ?: [];

        // Merge new keys with existing children
        $children = array_unique(array_merge($children, $keys));

        // Store updated children list indefinitely
        set_transient('children_tag_' . $parentTag, $children, 0);
    }

    /**
     * Clear all cache keys associated with a hierarchical parent tag.
     *
     * @param string $parentTag Parent tag name
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::clearParentTag('products');
     * ```
     */
    public static function clearParentTag(string $parentTag): void
    {
        // Retrieve all child keys under this parent tag
        $children = get_transient('children_tag_' . $parentTag) ?: [];

        // Delete each child cache key
        foreach ($children as $key) {
            self::delete($key);
        }

        // Remove the parent tag record
        delete_transient('children_tag_' . $parentTag);
    }

    /**
     * Schedule periodic cache refresh for a set of keys using callbacks.
     *
     * @param array $keyCallbackPairs Array of ['key' => callable] pairs
     * @param int   $interval         Interval in seconds to refresh each key
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::scheduleBatchRefresh([
     *     Cache::makeKey('stats') => fn() => computeStats(),
     *     Cache::makeKey('top_products') => fn() => getTopProducts()
     * ], 3600);
     * ```
     */
    public static function scheduleBatchRefresh(array $keyCallbackPairs, int $interval): void
    {
        // Loop through each key-callback pair
        foreach ($keyCallbackPairs as $key => $callback) {
            // Schedule refresh individually
            self::scheduleRefresh($key, $callback, $interval);
        }
    }

    /**
     * Compute the total number of cache keys under a specific tag.
     *
     * @param string $tag Tag name
     *
     * @return int Number of keys under the tag
     *
     * @example
     * ```php
     * $count = Cache::countKeysByTag('products');
     * ```
     */
    public static function countKeysByTag(string $tag): int
    {
        // Retrieve all keys associated with the tag
        $keys = self::getTaggedKeys($tag);

        // Return the total count
        return count($keys);
    }

    /**
     * Clear cache keys that satisfy a condition callback.
     *
     * @param callable $condition Function that accepts key and value and returns true to delete
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::clearIf(fn($key, $value) => strpos($key, 'temp_') === 0);
     * ```
     */
    public static function clearIf(callable $condition): void
    {
        global $wpdb;

        // Fetch all transient keys from database
        $results = $wpdb->get_results(
            "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'"
        );

        // Loop through each transient
        foreach ($results as $row) {
            $key = preg_replace('/^_transient_/', '', $row->option_name);
            $value = maybe_unserialize($row->option_value);

            // Delete if condition callback returns true
            if ($condition($key, $value)) {
                self::delete($key);
            }
        }
    }

    /**
     * Recompute all cache keys that depend on a specific parent key.
     *
     * Useful when parent data changes and all children must be refreshed.
     *
     * @param string   $parentKey  Parent cache key
     * @param callable $callback   Function to recompute each child key
     * @param int      $expiration Cache lifetime in seconds (default 1 hour)
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::recomputeChildren(Cache::makeKey('parent'), fn($key) => computeChild($key), 3600);
     * ```
     */
    public static function recomputeChildren(string $parentKey, callable $callback, int $expiration = 3600): void
    {
        // Retrieve all child keys under the parent
        $children = get_transient('children_' . $parentKey) ?: [];

        // Loop through each child key
        foreach ($children as $childKey) {
            // Recompute the value using callback
            $value = $callback($childKey);

            // Store the new value in cache
            set_transient($childKey, $value, $expiration);
        }
    }

    /**
     * Clear all cache keys that are older than a certain age (in seconds).
     *
     * @param int $age Maximum age in seconds
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::clearOlderThan(3600);
     * ```
     */
    public static function clearOlderThan(int $age): void
    {
        global $wpdb;
        $threshold = time() - $age;

        // Delete transient keys whose expiration is older than threshold
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s AND option_name IN (
                SELECT option_name FROM {$wpdb->options} WHERE CAST(option_value AS UNSIGNED) < %d
            )",
                '_transient_%',
                $threshold
            )
        );
    }

    /**
     * Refresh multiple cache keys conditionally using a callback.
     *
     * @param array    $keys       Array of cache keys
     * @param callable $callback   Function to recompute value
     * @param callable $condition  Function that receives current value and returns true to refresh
     * @param int      $expiration Cache lifetime in seconds (default 1 hour)
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::refreshMultipleIf(
     *     [Cache::makeKey('a'), Cache::makeKey('b')],
     *     fn($key) => computeValue($key),
     *     fn($value) => $value < 100,
     *     3600
     * );
     * ```
     */
    public static function refreshMultipleIf(array $keys, callable $callback, callable $condition, int $expiration = 3600): void
    {
        foreach ($keys as $key) {
            $value = get_transient($key);
            if ($value === false || $condition($value)) {
                set_transient($key, $callback($key), $expiration);
            }
        }
    }

    /**
     * Retrieve aggregated statistics for multiple tags.
     *
     * @param array $tags Array of tag names
     *
     * @return array ['total_keys' => int, 'tag_details' => array]
     *
     * @example
     * ```php
     * $stats = Cache::aggregateTagStats(['products', 'featured']);
     * ```
     */
    public static function aggregateTagStats(array $tags): array
    {
        $totalKeys = 0;
        $tagDetails = [];

        foreach ($tags as $tag) {
            $keys = self::getTaggedKeys($tag);
            $totalKeys += count($keys);
            $tagDetails[$tag] = count($keys);
        }

        return ['total_keys' => $totalKeys, 'tag_details' => $tagDetails];
    }

    /**
     * Store a cache key only if its value differs from the current cached value.
     *
     * @param string $key        Cache key
     * @param mixed  $value      Value to store
     * @param int    $expiration Cache lifetime in seconds (default 1 hour)
     *
     * @return bool True if value was stored, false if it was identical to existing
     *
     * @example
     * ```php
     * Cache::storeIfChanged(Cache::makeKey('config'), $newConfig, 3600);
     * ```
     */
    public static function storeIfChanged(string $key, $value, int $expiration = 3600): bool
    {
        // Retrieve current value from cache
        $current = get_transient($key);

        // Store only if value has changed
        if ($current !== $value) {
            set_transient($key, $value, $expiration);
            return true;
        }

        return false;
    }

    /**
     * Store a cache key with a version number.
     *
     * Useful for invalidating old versions without deleting keys manually.
     *
     * @param string $key        Base cache key
     * @param mixed  $value      Value to store
     * @param int    $version    Version number
     * @param int    $expiration Cache lifetime in seconds (default 1 hour)
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::setWithVersion(Cache::makeKey('config'), $data, 2, 3600);
     * ```
     */
    public static function setWithVersion(string $key, $value, int $version, int $expiration = 3600): void
    {
        // Append version number to key
        $versionedKey = "{$key}_v{$version}";

        // Store the value in cache with expiration
        set_transient($versionedKey, $value, $expiration);
    }

    /**
     * Retrieve a cache key for a specific version, falling back to the latest version if missing.
     *
     * @param string $key        Base cache key
     * @param int    $version    Version number
     * @param int    $latestVersion Latest version to fallback
     *
     * @return mixed Cached value or false if not found
     *
     * @example
     * ```php
     * $data = Cache::getWithVersion(Cache::makeKey('config'), 2, 3);
     * ```
     */
    public static function getWithVersion(string $key, int $version, int $latestVersion = null)
    {
        $versionedKey = "{$key}_v{$version}";
        $value = get_transient($versionedKey);

        // If not found and latestVersion is provided, fallback
        if ($value === false && $latestVersion !== null) {
            $fallbackKey = "{$key}_v{$latestVersion}";
            $value = get_transient($fallbackKey);
        }

        return $value;
    }

    /**
     * Schedule persistent recomputation of a cache key using a callback.
     *
     * This ensures the cache is refreshed periodically without manual triggers.
     *
     * @param string   $key        Cache key
     * @param callable $callback   Function to recompute value
     * @param int      $interval   Interval in seconds for refresh
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::schedulePersistent(Cache::makeKey('stats'), fn() => computeStats(), 3600);
     * ```
     */
    public static function schedulePersistent(string $key, callable $callback, int $interval): void
    {
        // Compute initial value if missing
        $value = get_transient($key);
        if ($value === false) {
            $value = $callback();
        }

        // Store value with interval expiration
        set_transient($key, $value, $interval);

        // Optionally, persist key for tracking (advanced persistent caching)
        update_option('persistent_' . $key, $value, false);
    }

    /**
     * Clear all persistent cached keys stored via schedulePersistent().
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::clearPersistent();
     * ```
     */
    public static function clearPersistent(): void
    {
        global $wpdb;

        // Get all options that start with persistent_
        $results = $wpdb->get_results(
            "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'persistent_%'"
        );

        // Loop through each persistent key and delete
        foreach ($results as $row) {
            delete_option($row->option_name);

            // Also delete corresponding transient if exists
            $key = str_replace('persistent_', '', $row->option_name);
            self::delete($key);
        }
    }

    /**
     * Update multiple cache keys atomically using a callback.
     *
     * @param array    $keys       Array of cache keys
     * @param callable $callback   Function that receives current value and returns updated value
     * @param int      $expiration Expiration in seconds (default 1 hour)
     *
     * @return array Updated key-value pairs
     *
     * @example
     * ```php
     * $updated = Cache::atomicUpdateMultiple(
     *     [Cache::makeKey('a'), Cache::makeKey('b')],
     *     fn($old) => ($old ?? 0) + 1,
     *     3600
     * );
     * ```
     */
    public static function atomicUpdateMultiple(array $keys, callable $callback, int $expiration = 3600): array
    {
        $updated = [];

        // Loop through each key
        foreach ($keys as $key) {
            $oldValue = get_transient($key);

            // Compute updated value
            $newValue = $callback($oldValue);

            // Store new value in cache
            set_transient($key, $newValue, $expiration);

            // Collect updated key-value pair
            $updated[$key] = $newValue;
        }

        return $updated;
    }

    /**
     * Store a cache key under a parent with versioning.
     *
     * Combines hierarchical storage and version control for advanced invalidation.
     *
     * @param string $parentKey Parent cache key
     * @param string $key       Child cache key
     * @param mixed  $value     Value to store
     * @param int    $version   Version number
     * @param int    $expiration Cache lifetime in seconds (default 1 hour)
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::storeChildWithVersion(Cache::makeKey('parent'), Cache::makeKey('child'), $data, 2, 3600);
     * ```
     */
    public static function storeChildWithVersion(string $parentKey, string $key, $value, int $version, int $expiration = 3600): void
    {
        // Append version to child key
        $versionedKey = "{$key}_v{$version}";

        // Store the versioned key in cache
        set_transient($versionedKey, $value, $expiration);

        // Track child keys under parent
        $children = get_transient('children_' . $parentKey) ?: [];
        if (!in_array($versionedKey, $children, true)) {
            $children[] = $versionedKey;
            set_transient('children_' . $parentKey, $children, 0);
        }
    }

    /**
     * Recompute all versioned child keys under a parent.
     *
     * @param string   $parentKey Parent cache key
     * @param callable $callback  Function to recompute each child
     * @param int      $expiration Cache lifetime in seconds (default 1 hour)
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::recomputeVersionedChildren(Cache::makeKey('parent'), fn($key) => computeChild($key), 3600);
     * ```
     */
    public static function recomputeVersionedChildren(string $parentKey, callable $callback, int $expiration = 3600): void
    {
        // Retrieve all children under parent
        $children = get_transient('children_' . $parentKey) ?: [];

        // Loop through each child
        foreach ($children as $childKey) {
            // Recompute value using callback
            $value = $callback($childKey);

            // Store updated value in cache
            set_transient($childKey, $value, $expiration);
        }
    }

    /**
     * Retrieve the sum of TTLs for all keys under a specific parent.
     *
     * @param string $parentKey Parent cache key
     *
     * @return int Total TTL in seconds
     *
     * @example
     * ```php
     * $ttlSum = Cache::parentTTL(Cache::makeKey('parent'));
     * ```
     */
    public static function parentTTL(string $parentKey): int
    {
        $children = get_transient('children_' . $parentKey) ?: [];
        $ttlSum = 0;

        // Sum remaining TTL for each child
        foreach ($children as $childKey) {
            $ttl = self::timeToLive($childKey);
            $ttlSum += $ttl !== false ? $ttl : 0;
        }

        return $ttlSum;
    }

    /**
     * Refresh all cache keys under a parent only if a condition is met.
     *
     * @param string   $parentKey Parent cache key
     * @param callable $callback  Function to recompute each child
     * @param callable $condition Function that receives current value and returns true to refresh
     * @param int      $expiration Cache lifetime in seconds (default 1 hour)
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::refreshParentIf(Cache::makeKey('parent'), fn($key) => computeChild($key), fn($val) => $val['count'] < 100, 3600);
     * ```
     */
    public static function refreshParentIf(string $parentKey, callable $callback, callable $condition, int $expiration = 3600): void
    {
        $children = get_transient('children_' . $parentKey) ?: [];

        // Loop through each child key
        foreach ($children as $childKey) {
            $value = get_transient($childKey);

            // Refresh only if condition passes
            if ($value === false || $condition($value)) {
                set_transient($childKey, $callback($childKey), $expiration);
            }
        }
    }

    /**
     * Clear all keys that belong to multiple parents.
     *
     * @param array $parentKeys Array of parent cache keys
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::clearMultipleParents([Cache::makeKey('parent1'), Cache::makeKey('parent2')]);
     * ```
     */
    public static function clearMultipleParents(array $parentKeys): void
    {
        // Loop through each parent key
        foreach ($parentKeys as $parentKey) {
            // Clear all children under this parent
            self::clearParent($parentKey);
        }
    }

    /**
     * Retrieve the first available cache key from a list of versions in fallback order.
     *
     * @param string $key         Base cache key
     * @param array  $versions    Array of version numbers in priority order
     *
     * @return mixed Cached value or false if none exist
     *
     * @example
     * ```php
     * $data = Cache::getFirstAvailableVersion(Cache::makeKey('config'), [3,2,1]);
     * ```
     */
    public static function getFirstAvailableVersion(string $key, array $versions)
    {
        // Loop through each version in priority order
        foreach ($versions as $version) {
            $value = get_transient("{$key}_v{$version}");
            if ($value !== false) {
                return $value;
            }
        }

        // Return false if no version found
        return false;
    }

    /**
     * Retrieve total number of versioned keys for a specific base key.
     *
     * @param string $key Base cache key
     *
     * @return int Number of versioned keys
     *
     * @example
     * ```php
     * $count = Cache::countVersions(Cache::makeKey('config'));
     * ```
     */
    public static function countVersions(string $key): int
    {
        global $wpdb;

        // Query all transient keys matching base key pattern with versions
        $pattern = $wpdb->esc_like($key) . '_v%';
        $count = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s", $pattern)
        );

        return $count;
    }

    /**
     * Retrieve analytics for a dependency key: how many keys depend on it.
     *
     * @param string $dependencyKey Dependency cache key
     *
     * @return int Number of dependent keys
     *
     * @example
     * ```php
     * $dependents = Cache::dependencyStats(Cache::makeKey('parent_key'));
     * ```
     */
    public static function dependencyStats(string $dependencyKey): int
    {
        $dependents = get_transient('dependents_' . $dependencyKey) ?: [];

        // Return number of keys that depend on this dependency
        return count($dependents);
    }

    /**
     * Clear all keys that are older than a specified TTL threshold.
     *
     * @param int $ttlThreshold Threshold in seconds
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::clearOlderThanTTL(1800);
     * ```
     */
    public static function clearOlderThanTTL(int $ttlThreshold): void
    {
        global $wpdb;
        $currentTime = time();

        // Fetch all transient timeouts
        $results = $wpdb->get_results(
            "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_%'"
        );

        // Loop through each timeout
        foreach ($results as $row) {
            $timeout = (int)$row->option_value;

            // Calculate remaining TTL
            $remaining = $timeout - $currentTime;

            // Delete key if TTL is below threshold
            if ($remaining < $ttlThreshold) {
                $key = preg_replace('/^_transient_timeout_/', '', $row->option_name);
                self::delete($key);
            }
        }
    }

    /**
     * Refresh all keys in a list conditionally based on their current TTL.
     *
     * @param array    $keys       Array of cache keys
     * @param callable $callback   Function to recompute value
     * @param int      $ttlThreshold Minimum TTL in seconds to trigger refresh
     * @param int      $expiration  Cache lifetime in seconds (default 1 hour)
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::refreshIfTTLBelow([Cache::makeKey('a'), Cache::makeKey('b')], fn($key) => compute($key), 600, 3600);
     * ```
     */
    public static function refreshIfTTLBelow(array $keys, callable $callback, int $ttlThreshold, int $expiration = 3600): void
    {
        foreach ($keys as $key) {
            $ttl = self::timeToLive($key);

            // Refresh only if TTL is below threshold or key is missing
            if ($ttl === false || $ttl < $ttlThreshold) {
                set_transient($key, $callback($key), $expiration);
            }
        }
    }

    /**
     * Recompute all keys that depend on a specific parent or dependency recursively.
     *
     * @param string   $parentKey  Parent or dependency key
     * @param callable $callback   Function to recompute each dependent key
     * @param int      $expiration Cache lifetime in seconds (default 1 hour)
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::recomputeDependents(Cache::makeKey('parent'), fn($key) => compute($key), 3600);
     * ```
     */
    public static function recomputeDependents(string $parentKey, callable $callback, int $expiration = 3600): void
    {
        // Get all dependent keys for this parent
        $dependents = get_transient('dependents_' . $parentKey) ?: [];

        // Loop through each dependent
        foreach ($dependents as $depKey) {
            // Recompute the value
            $value = $callback($depKey);

            // Store updated value
            set_transient($depKey, $value, $expiration);

            // Recursively recompute further dependents
            self::recomputeDependents($depKey, $callback, $expiration);
        }
    }

    /**
     * Schedule persistent batch recomputation for a list of keys.
     *
     * @param array    $keyCallbackPairs Array of ['key' => callable] pairs
     * @param int      $interval         Interval in seconds for refresh
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::schedulePersistentBatch([
     *     Cache::makeKey('stats') => fn() => computeStats(),
     *     Cache::makeKey('top_products') => fn() => getTopProducts()
     * ], 3600);
     * ```
     */
    public static function schedulePersistentBatch(array $keyCallbackPairs, int $interval): void
    {
        // Loop through each key-callback pair
        foreach ($keyCallbackPairs as $key => $callback) {
            // Schedule persistent refresh for each key
            self::schedulePersistent($key, $callback, $interval);
        }
    }

    /**
     * Compute the average remaining TTL of a set of cache keys.
     *
     * @param array $keys Array of cache keys
     *
     * @return float Average TTL in seconds
     *
     * @example
     * ```php
     * $avgTTL = Cache::averageTTL([Cache::makeKey('a'), Cache::makeKey('b')]);
     * ```
     */
    public static function averageTTL(array $keys): float
    {
        $ttlSum = 0;
        $count = 0;

        // Loop through each key
        foreach ($keys as $key) {
            $ttl = self::timeToLive($key);
            if ($ttl !== false) {
                $ttlSum += $ttl;
                $count++;
            }
        }

        // Calculate average
        return $count > 0 ? $ttlSum / $count : 0;
    }

    /**
     * Refresh all keys under multiple parents if their TTL is below a threshold.
     *
     * @param array $parentKeys   Array of parent cache keys
     * @param callable $callback  Function to recompute each child key
     * @param int $ttlThreshold   Minimum TTL to trigger refresh
     * @param int $expiration     Cache lifetime in seconds (default 1 hour)
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::refreshMultipleParentsIfTTLBelow([Cache::makeKey('parent1'), Cache::makeKey('parent2')], fn($key) => computeChild($key), 600, 3600);
     * ```
     */
    public static function refreshMultipleParentsIfTTLBelow(array $parentKeys, callable $callback, int $ttlThreshold, int $expiration = 3600): void
    {
        // Loop through each parent
        foreach ($parentKeys as $parentKey) {
            $children = get_transient('children_' . $parentKey) ?: [];

            // Loop through each child key
            foreach ($children as $childKey) {
                $ttl = self::timeToLive($childKey);

                // Refresh only if TTL below threshold
                if ($ttl === false || $ttl < $ttlThreshold) {
                    set_transient($childKey, $callback($childKey), $expiration);
                }
            }
        }
    }

    /**
     * Retrieve the minimum TTL among a set of cache keys.
     *
     * @param array $keys Array of cache keys
     *
     * @return int Minimum TTL in seconds, or 0 if all keys are missing
     *
     * @example
     * ```php
     * $minTTL = Cache::minTTL([Cache::makeKey('a'), Cache::makeKey('b')]);
     * ```
     */
    public static function minTTL(array $keys): int
    {
        $min = PHP_INT_MAX;
        $found = false;

        // Loop through each key
        foreach ($keys as $key) {
            $ttl = self::timeToLive($key);
            if ($ttl !== false) {
                $min = min($min, $ttl);
                $found = true;
            }
        }

        // Return minimum TTL or 0 if none found
        return $found ? $min : 0;
    }

    /**
     * Retrieve the key with the maximum TTL from a set of cache keys.
     *
     * @param array $keys Array of cache keys
     *
     * @return string|null Key with the maximum TTL, or null if all missing
     *
     * @example
     * ```php
     * $keyWithMaxTTL = Cache::maxTTLKey([Cache::makeKey('a'), Cache::makeKey('b')]);
     * ```
     */
    public static function maxTTLKey(array $keys): ?string
    {
        $maxTTL = -1;
        $maxKey = null;

        // Loop through each key
        foreach ($keys as $key) {
            $ttl = self::timeToLive($key);
            if ($ttl !== false && $ttl > $maxTTL) {
                $maxTTL = $ttl;
                $maxKey = $key;
            }
        }

        return $maxKey;
    }

    /**
     * Recompute multiple keys with a fallback hierarchy.
     *
     * If the primary key is missing, recompute fallback keys in order.
     *
     * @param array $keyCallbackPairs Array of ['key' => callable] pairs
     * @param int   $expiration       Cache lifetime in seconds (default 1 hour)
     *
     * @return array Array of computed values
     *
     * @example
     * ```php
     * $values = Cache::recomputeFallback([
     *     Cache::makeKey('primary') => fn() => computePrimary(),
     *     Cache::makeKey('secondary') => fn() => computeSecondary()
     * ], 3600);
     * ```
     */
    public static function recomputeFallback(array $keyCallbackPairs, int $expiration = 3600): array
    {
        $results = [];

        // Loop through each key-callback pair
        foreach ($keyCallbackPairs as $key => $callback) {
            $value = get_transient($key);

            // Recompute if missing
            if ($value === false) {
                $value = $callback();
                set_transient($key, $value, $expiration);
            }

            $results[$key] = $value;
        }

        return $results;
    }

    /**
     * Retrieve the remaining TTL for a specific versioned key.
     *
     * @param string $key     Base cache key
     * @param int    $version Version number
     *
     * @return int|false TTL in seconds, or false if key does not exist
     *
     * @example
     * ```php
     * $ttl = Cache::timeToLiveVersion(Cache::makeKey('config'), 2);
     * ```
     */
    public static function timeToLiveVersion(string $key, int $version)
    {
        $versionedKey = "{$key}_v{$version}";
        return self::timeToLive($versionedKey);
    }

    /**
     * Invalidate a chain of dependencies recursively.
     *
     * Useful when multiple dependent keys must be cleared after a change in a root key.
     *
     * @param string $rootKey Root dependency key
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::invalidateDependencyChain(Cache::makeKey('root'));
     * ```
     */
    public static function invalidateDependencyChain(string $rootKey): void
    {
        // Delete the root key
        self::delete($rootKey);

        // Retrieve dependents of the root
        $dependents = get_transient('dependents_' . $rootKey) ?: [];

        // Recursively invalidate each dependent
        foreach ($dependents as $depKey) {
            self::invalidateDependencyChain($depKey);
        }

        // Remove the dependents record
        delete_transient('dependents_' . $rootKey);
    }

    /**
     * Aggregate TTL statistics for multiple versioned keys.
     *
     * Returns min, max, and average TTL among the provided keys.
     *
     * @param array $keys Array of base keys (assumes versioned keys stored)
     *
     * @return array ['min' => int, 'max' => int, 'average' => float]
     *
     * @example
     * ```php
     * $stats = Cache::versionedTTLStats([Cache::makeKey('config'), Cache::makeKey('settings')]);
     * ```
     */
    public static function versionedTTLStats(array $keys): array
    {
        $ttls = [];

        // Loop through each key
        foreach ($keys as $key) {
            // Retrieve all versioned keys
            $count = self::countVersions($key);
            for ($v = 1; $v <= $count; $v++) {
                $ttl = self::timeToLiveVersion($key, $v);
                if ($ttl !== false) {
                    $ttls[] = $ttl;
                }
            }
        }

        if (empty($ttls)) {
            return ['min' => 0, 'max' => 0, 'average' => 0];
        }

        // Calculate statistics
        return [
            'min' => min($ttls),
            'max' => max($ttls),
            'average' => array_sum($ttls) / count($ttls),
        ];
    }

    /**
     * Delete multiple cache keys if they satisfy a condition callback.
     *
     * @param array    $keys       Array of cache keys
     * @param callable $condition  Function that receives key and value and returns true to delete
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::deleteIf([Cache::makeKey('a'), Cache::makeKey('b')], fn($key, $value) => $value['expired'] ?? false);
     * ```
     */
    public static function deleteIf(array $keys, callable $condition): void
    {
        // Loop through each key
        foreach ($keys as $key) {
            $value = get_transient($key);

            // Delete if condition is true
            if ($value !== false && $condition($key, $value)) {
                self::delete($key);
            }
        }
    }

    /**
     * Retrieve aggregated statistics (count and TTL) for a tag across all versions.
     *
     * @param string $tag Tag name
     *
     * @return array ['total_keys' => int, 'ttl_sum' => int, 'average_ttl' => float]
     *
     * @example
     * ```php
     * $stats = Cache::tagVersionedStats('products');
     * ```
     */
    public static function tagVersionedStats(string $tag): array
    {
        $keys = self::getTaggedKeys($tag);
        $ttlSum = 0;
        $count = 0;

        // Loop through each key
        foreach ($keys as $key) {
            $versions = self::countVersions($key);
            for ($v = 1; $v <= $versions; $v++) {
                $ttl = self::timeToLiveVersion($key, $v);
                if ($ttl !== false) {
                    $ttlSum += $ttl;
                    $count++;
                }
            }
        }

        return [
            'total_keys' => $count,
            'ttl_sum' => $ttlSum,
            'average_ttl' => $count > 0 ? $ttlSum / $count : 0,
        ];
    }

    /**
     * Schedule a cache refresh for keys that have dependencies, ensuring dependent keys are updated.
     *
     * @param string   $key        Cache key
     * @param callable $callback   Function to recompute value
     * @param int      $interval   Interval in seconds to refresh
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::scheduleDependencyRefresh(Cache::makeKey('parent'), fn() => computeParent(), 3600);
     * ```
     */
    public static function scheduleDependencyRefresh(string $key, callable $callback, int $interval): void
    {
        // Refresh the main key
        self::scheduleRefresh($key, $callback, $interval);

        // Retrieve dependent keys
        $dependents = get_transient('dependents_' . $key) ?: [];

        // Loop through each dependent key and schedule refresh
        foreach ($dependents as $depKey) {
            self::scheduleRefresh($depKey, fn() => $callback(), $interval);
        }
    }

    /**
     * Recompute a set of keys in batch and store versioned keys atomically.
     *
     * @param array $keyCallbackVersionPairs Array of ['key' => ['callback' => callable, 'version' => int]]
     * @param int   $expiration              Cache lifetime in seconds (default 1 hour)
     *
     * @return array Array of updated values keyed by versioned key names
     *
     * @example
     * ```php
     * $updated = Cache::batchRecomputeVersioned([
     *     Cache::makeKey('config') => ['callback' => fn() => computeConfig(), 'version' => 2]
     * ], 3600);
     * ```
     */
    public static function batchRecomputeVersioned(array $keyCallbackVersionPairs, int $expiration = 3600): array
    {
        $updated = [];

        // Loop through each key
        foreach ($keyCallbackVersionPairs as $key => $info) {
            $versionedKey = "{$key}_v{$info['version']}";

            // Recompute the value using callback
            $value = $info['callback']();

            // Store in cache
            set_transient($versionedKey, $value, $expiration);

            $updated[$versionedKey] = $value;
        }

        return $updated;
    }

    /**
     * Retrieve a list of keys with TTL below a threshold for analytics or refresh.
     *
     * @param array $keys Array of cache keys
     * @param int   $ttlThreshold Minimum TTL in seconds
     *
     * @return array Array of keys whose TTL is below the threshold
     *
     * @example
     * ```php
     * $keysToRefresh = Cache::keysWithTTLBelow([Cache::makeKey('a'), Cache::makeKey('b')], 600);
     * ```
     */
    public static function keysWithTTLBelow(array $keys, int $ttlThreshold): array
    {
        $result = [];

        // Loop through each key
        foreach ($keys as $key) {
            $ttl = self::timeToLive($key);

            // Include key if TTL is below threshold
            if ($ttl === false || $ttl < $ttlThreshold) {
                $result[] = $key;
            }
        }

        return $result;
    }

    /**
     * Retrieve all keys under a tag that have TTL below a given threshold.
     *
     * @param string $tag         Tag name
     * @param int    $ttlThreshold TTL threshold in seconds
     *
     * @return array Keys under the tag with TTL below threshold
     *
     * @example
     * ```php
     * $keys = Cache::tagKeysWithTTLBelow('products', 600);
     * ```
     */
    public static function tagKeysWithTTLBelow(string $tag, int $ttlThreshold): array
    {
        $keys = self::getTaggedKeys($tag);
        $result = [];

        // Loop through each key and check TTL
        foreach ($keys as $key) {
            $ttl = self::timeToLive($key);
            if ($ttl === false || $ttl < $ttlThreshold) {
                $result[] = $key;
            }
        }

        return $result;
    }

    /**
     * Recompute multiple versioned keys with a fallback order.
     *
     * Tries the highest version first, falling back to lower versions if missing.
     *
     * @param array $keyVersions Array of ['key' => maxVersion] pairs
     * @param callable $callback Function that takes key and version and returns value
     * @param int $expiration    Cache lifetime in seconds (default 1 hour)
     *
     * @return array Updated key-value pairs
     *
     * @example
     * ```php
     * $updated = Cache::recomputeVersionedFallback([
     *     Cache::makeKey('config') => 3
     * ], fn($key, $version) => computeVersion($key, $version), 3600);
     * ```
     */
    public static function recomputeVersionedFallback(array $keyVersions, callable $callback, int $expiration = 3600): array
    {
        $updated = [];

        // Loop through each key and max version
        foreach ($keyVersions as $key => $maxVersion) {
            for ($v = $maxVersion; $v >= 1; $v--) {
                $versionedKey = "{$key}_v{$v}";
                $value = get_transient($versionedKey);

                // Recompute if missing
                if ($value === false) {
                    $value = $callback($key, $v);
                    set_transient($versionedKey, $value, $expiration);
                }

                $updated[$versionedKey] = $value;
            }
        }

        return $updated;
    }

    /**
     * Get all keys under a parent whose TTL is below a threshold.
     *
     * Useful for deciding which children need refresh.
     *
     * @param string $parentKey  Parent cache key
     * @param int    $ttlThreshold Minimum TTL in seconds
     *
     * @return array Keys under parent below TTL threshold
     *
     * @example
     * ```php
     * $keys = Cache::parentKeysWithTTLBelow(Cache::makeKey('parent'), 600);
     * ```
     */
    public static function parentKeysWithTTLBelow(string $parentKey, int $ttlThreshold): array
    {
        $children = get_transient('children_' . $parentKey) ?: [];
        $result = [];

        // Loop through each child key
        foreach ($children as $childKey) {
            $ttl = self::timeToLive($childKey);
            if ($ttl === false || $ttl < $ttlThreshold) {
                $result[] = $childKey;
            }
        }

        return $result;
    }

    /**
     * Retrieve combined analytics for a parent: number of children and average TTL.
     *
     * @param string $parentKey Parent cache key
     *
     * @return array ['child_count' => int, 'average_ttl' => float]
     *
     * @example
     * ```php
     * $stats = Cache::parentAnalytics(Cache::makeKey('parent'));
     * ```
     */
    public static function parentAnalytics(string $parentKey): array
    {
        $children = get_transient('children_' . $parentKey) ?: [];
        $ttlSum = 0;
        $count = 0;

        // Loop through each child to sum TTLs
        foreach ($children as $childKey) {
            $ttl = self::timeToLive($childKey);
            if ($ttl !== false) {
                $ttlSum += $ttl;
                $count++;
            }
        }

        return [
            'child_count' => count($children),
            'average_ttl' => $count > 0 ? $ttlSum / $count : 0,
        ];
    }

    /**
     * Clear keys under multiple tags whose TTL is below a threshold.
     *
     * @param array $tags Array of tag names
     * @param int   $ttlThreshold TTL threshold in seconds
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::clearTagsIfTTLBelow(['products', 'featured'], 600);
     * ```
     */
    public static function clearTagsIfTTLBelow(array $tags, int $ttlThreshold): void
    {
        // Loop through each tag
        foreach ($tags as $tag) {
            $keys = self::tagKeysWithTTLBelow($tag, $ttlThreshold);

            // Delete keys below TTL
            foreach ($keys as $key) {
                self::delete($key);
            }
        }
    }

    /**
     * Retrieve the total number of dependent keys for multiple parent keys.
     *
     * @param array $parentKeys Array of parent cache keys
     *
     * @return int Total number of dependent keys
     *
     * @example
     * ```php
     * $totalDependents = Cache::multiParentDependencyCount([Cache::makeKey('parent1'), Cache::makeKey('parent2')]);
     * ```
     */
    public static function multiParentDependencyCount(array $parentKeys): int
    {
        $total = 0;

        // Loop through each parent
        foreach ($parentKeys as $parentKey) {
            $dependents = get_transient('dependents_' . $parentKey) ?: [];
            $total += count($dependents);
        }

        return $total;
    }

    /**
     * Schedule versioned keys to refresh periodically.
     *
     * @param array $keyVersionCallbackPairs Array of ['key' => ['version' => int, 'callback' => callable]]
     * @param int   $interval                 Refresh interval in seconds
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::scheduleVersionedRefresh([
     *     Cache::makeKey('config') => ['version' => 2, 'callback' => fn() => computeConfig()]
     * ], 3600);
     * ```
     */
    public static function scheduleVersionedRefresh(array $keyVersionCallbackPairs, int $interval): void
    {
        // Loop through each key-version pair
        foreach ($keyVersionCallbackPairs as $key => $info) {
            $versionedKey = "{$key}_v{$info['version']}";
            self::schedulePersistent($versionedKey, $info['callback'], $interval);
        }
    }

    /**
     * Recompute all children under multiple parents.
     *
     * @param array    $parentKeys Array of parent cache keys
     * @param callable $callback   Function to recompute each child
     * @param int      $expiration Cache lifetime in seconds (default 1 hour)
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::recomputeMultipleParents([Cache::makeKey('parent1'), Cache::makeKey('parent2')], fn($key) => computeChild($key), 3600);
     * ```
     */
    public static function recomputeMultipleParents(array $parentKeys, callable $callback, int $expiration = 3600): void
    {
        // Loop through each parent
        foreach ($parentKeys as $parentKey) {
            $children = get_transient('children_' . $parentKey) ?: [];

            // Loop through each child key
            foreach ($children as $childKey) {
                set_transient($childKey, $callback($childKey), $expiration);
            }
        }
    }

    /**
     * Retrieve the average TTL for all keys under multiple parents.
     *
     * @param array $parentKeys Array of parent cache keys
     *
     * @return float Average TTL in seconds
     *
     * @example
     * ```php
     * $avgTTL = Cache::averageTTLForParents([Cache::makeKey('parent1'), Cache::makeKey('parent2')]);
     * ```
     */
    public static function averageTTLForParents(array $parentKeys): float
    {
        $ttlSum = 0;
        $count = 0;

        // Loop through each parent
        foreach ($parentKeys as $parentKey) {
            $children = get_transient('children_' . $parentKey) ?: [];
            foreach ($children as $childKey) {
                $ttl = self::timeToLive($childKey);
                if ($ttl !== false) {
                    $ttlSum += $ttl;
                    $count++;
                }
            }
        }

        return $count > 0 ? $ttlSum / $count : 0;
    }

    /**
     * Clear all keys under multiple parents if their TTL is below a threshold.
     *
     * @param array $parentKeys   Array of parent cache keys
     * @param int   $ttlThreshold TTL threshold in seconds
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::clearMultipleParentsIfTTLBelow([Cache::makeKey('parent1'), Cache::makeKey('parent2')], 600);
     * ```
     */
    public static function clearMultipleParentsIfTTLBelow(array $parentKeys, int $ttlThreshold): void
    {
        // Loop through each parent key
        foreach ($parentKeys as $parentKey) {
            $children = get_transient('children_' . $parentKey) ?: [];

            // Delete children whose TTL is below threshold
            foreach ($children as $childKey) {
                $ttl = self::timeToLive($childKey);
                if ($ttl === false || $ttl < $ttlThreshold) {
                    self::delete($childKey);
                }
            }
        }
    }

    /**
     * Retrieve combined analytics for multiple tags: total keys and average TTL.
     *
     * @param array $tags Array of tag names
     *
     * @return array ['total_keys' => int, 'average_ttl' => float]
     *
     * @example
     * ```php
     * $stats = Cache::tagsAnalytics(['products', 'featured']);
     * ```
     */
    public static function tagsAnalytics(array $tags): array
    {
        $totalKeys = 0;
        $ttlSum = 0;

        // Loop through each tag
        foreach ($tags as $tag) {
            $keys = self::getTaggedKeys($tag);
            $totalKeys += count($keys);

            foreach ($keys as $key) {
                $ttl = self::timeToLive($key);
                if ($ttl !== false) {
                    $ttlSum += $ttl;
                }
            }
        }

        return [
            'total_keys' => $totalKeys,
            'average_ttl' => $totalKeys > 0 ? $ttlSum / $totalKeys : 0,
        ];
    }

    /**
     * Refresh all keys under multiple parents if any dependent has TTL below a threshold.
     *
     * @param array    $parentKeys Array of parent cache keys
     * @param callable $callback   Function to recompute each child
     * @param int      $ttlThreshold TTL threshold in seconds
     * @param int      $expiration Cache lifetime in seconds (default 1 hour)
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::refreshParentsIfDependentsTTLBelow([Cache::makeKey('parent1')], fn($key) => computeChild($key), 600, 3600);
     * ```
     */
    public static function refreshParentsIfDependentsTTLBelow(array $parentKeys, callable $callback, int $ttlThreshold, int $expiration = 3600): void
    {
        // Loop through each parent
        foreach ($parentKeys as $parentKey) {
            $children = get_transient('children_' . $parentKey) ?: [];

            // Check TTL of dependents
            $needsRefresh = false;
            foreach ($children as $childKey) {
                $ttl = self::timeToLive($childKey);
                if ($ttl === false || $ttl < $ttlThreshold) {
                    $needsRefresh = true;
                    break;
                }
            }

            // Refresh all children if needed
            if ($needsRefresh) {
                foreach ($children as $childKey) {
                    set_transient($childKey, $callback($childKey), $expiration);
                }
            }
        }
    }

    /**
     * Batch update multiple keys with versioning in a dependency-aware manner.
     *
     * @param array $keyVersionCallbackPairs Array of ['key' => ['version' => int, 'callback' => callable]]
     * @param int   $expiration Cache lifetime in seconds (default 1 hour)
     *
     * @return array Updated key-value pairs
     *
     * @example
     * ```php
     * $updated = Cache::batchVersionedUpdateDependencyAware([
     *     Cache::makeKey('config') => ['version' => 2, 'callback' => fn() => computeConfig()]
     * ], 3600);
     * ```
     */
    public static function batchVersionedUpdateDependencyAware(array $keyVersionCallbackPairs, int $expiration = 3600): array
    {
        $updated = [];

        // Loop through each key-version pair
        foreach ($keyVersionCallbackPairs as $key => $info) {
            $versionedKey = "{$key}_v{$info['version']}";
            $value = $info['callback']();

            // Store value
            set_transient($versionedKey, $value, $expiration);
            $updated[$versionedKey] = $value;

            // Recompute dependents recursively
            self::recomputeDependents($key, $info['callback'], $expiration);
        }

        return $updated;
    }

    /**
     * Retrieve all keys under a tag whose TTL is below a threshold, including versioned keys.
     *
     * @param string $tag         Tag name
     * @param int    $ttlThreshold TTL threshold in seconds
     *
     * @return array Keys with version suffixes below TTL
     *
     * @example
     * ```php
     * $keys = Cache::tagVersionedKeysTTLBelow('products', 600);
     * ```
     */
    public static function tagVersionedKeysTTLBelow(string $tag, int $ttlThreshold): array
    {
        $keys = self::getTaggedKeys($tag);
        $result = [];

        // Loop through each key
        foreach ($keys as $key) {
            $count = self::countVersions($key);
            for ($v = 1; $v <= $count; $v++) {
                $versionedKey = "{$key}_v{$v}";
                $ttl = self::timeToLive($versionedKey);
                if ($ttl === false || $ttl < $ttlThreshold) {
                    $result[] = $versionedKey;
                }
            }
        }

        return $result;
    }

    /**
     * Refresh all keys under multiple tags if their TTL is below a threshold.
     *
     * @param array    $tags       Array of tag names
     * @param callable $callback   Function to recompute each key
     * @param int      $ttlThreshold TTL threshold in seconds
     * @param int      $expiration Cache lifetime in seconds (default 1 hour)
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::refreshTagsIfTTLBelow(['products', 'featured'], fn($key) => compute($key), 600, 3600);
     * ```
     */
    public static function refreshTagsIfTTLBelow(array $tags, callable $callback, int $ttlThreshold, int $expiration = 3600): void
    {
        // Loop through each tag
        foreach ($tags as $tag) {
            $keys = self::tagVersionedKeysTTLBelow($tag, $ttlThreshold);

            // Refresh each key
            foreach ($keys as $key) {
                set_transient($key, $callback($key), $expiration);
            }
        }
    }

    /**
     * Invalidate a versioned dependency chain recursively.
     *
     * @param string $key     Base cache key
     * @param int    $version Version number to invalidate
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::invalidateVersionedDependencyChain(Cache::makeKey('config'), 2);
     * ```
     */
    public static function invalidateVersionedDependencyChain(string $key, int $version): void
    {
        $versionedKey = "{$key}_v{$version}";

        // Delete the versioned key
        self::delete($versionedKey);

        // Get dependents of the base key
        $dependents = get_transient('dependents_' . $key) ?: [];

        // Recursively invalidate dependents
        foreach ($dependents as $depKey) {
            self::invalidateVersionedDependencyChain($depKey, $version);
        }
    }

    /**
     * Refresh all keys under multiple parents and multiple tags if their TTL is below a threshold.
     *
     * @param array    $parents       Array of parent keys
     * @param array    $tags          Array of tag names
     * @param callable $callback      Function to recompute key
     * @param int      $ttlThreshold  TTL threshold in seconds
     * @param int      $expiration    Cache lifetime in seconds (default 1 hour)
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::refreshParentsAndTagsIfTTLBelow([Cache::makeKey('parent1')], ['products'], fn($key) => compute($key), 600, 3600);
     * ```
     */
    public static function refreshParentsAndTagsIfTTLBelow(array $parents, array $tags, callable $callback, int $ttlThreshold, int $expiration = 3600): void
    {
        // Refresh parents
        foreach ($parents as $parent) {
            $children = get_transient('children_' . $parent) ?: [];
            foreach ($children as $childKey) {
                $ttl = self::timeToLive($childKey);
                if ($ttl === false || $ttl < $ttlThreshold) {
                    set_transient($childKey, $callback($childKey), $expiration);
                }
            }
        }

        // Refresh tags
        self::refreshTagsIfTTLBelow($tags, $callback, $ttlThreshold, $expiration);
    }

    /**
     * Retrieve the minimum TTL among all children under multiple parents.
     *
     * @param array $parentKeys Array of parent cache keys
     *
     * @return int Minimum TTL in seconds, 0 if no children exist
     *
     * @example
     * ```php
     * $minTTL = Cache::minTTLForParents([Cache::makeKey('parent1'), Cache::makeKey('parent2')]);
     * ```
     */
    public static function minTTLForParents(array $parentKeys): int
    {
        $min = PHP_INT_MAX;
        $found = false;

        // Loop through each parent
        foreach ($parentKeys as $parentKey) {
            $children = get_transient('children_' . $parentKey) ?: [];
            foreach ($children as $childKey) {
                $ttl = self::timeToLive($childKey);
                if ($ttl !== false) {
                    $min = min($min, $ttl);
                    $found = true;
                }
            }
        }

        return $found ? $min : 0;
    }

    /**
     * Schedule periodic refresh for a set of keys with versioned fallbacks.
     *
     * @param array $keyVersionCallbackPairs Array of ['key' => ['version' => int, 'callback' => callable]]
     * @param int   $interval                 Interval in seconds for refresh
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::scheduleVersionedFallbackRefresh([
     *     Cache::makeKey('config') => ['version' => 2, 'callback' => fn() => computeConfig()]
     * ], 3600);
     * ```
     */
    public static function scheduleVersionedFallbackRefresh(array $keyVersionCallbackPairs, int $interval): void
    {
        // Loop through each key-version pair
        foreach ($keyVersionCallbackPairs as $key => $info) {
            $versionedKey = "{$key}_v{$info['version']}";
            self::schedulePersistent($versionedKey, $info['callback'], $interval);

            // Schedule fallback refresh for lower versions
            for ($v = $info['version'] - 1; $v >= 1; $v--) {
                $fallbackKey = "{$key}_v{$v}";
                self::schedulePersistent($fallbackKey, $info['callback'], $interval);
            }
        }
    }

    /**
     * Retrieve combined analytics for multiple parents and tags: total keys and average TTL.
     *
     * @param array $parentKeys Array of parent cache keys
     * @param array $tags       Array of tag names
     *
     * @return array ['total_keys' => int, 'average_ttl' => float]
     *
     * @example
     * ```php
     * $stats = Cache::combinedAnalytics([Cache::makeKey('parent1')], ['products']);
     * ```
     */
    public static function combinedAnalytics(array $parentKeys, array $tags): array
    {
        $totalKeys = 0;
        $ttlSum = 0;

        // Aggregate parent keys
        foreach ($parentKeys as $parentKey) {
            $children = get_transient('children_' . $parentKey) ?: [];
            $totalKeys += count($children);
            foreach ($children as $childKey) {
                $ttl = self::timeToLive($childKey);
                if ($ttl !== false) {
                    $ttlSum += $ttl;
                }
            }
        }

        // Aggregate tag keys
        foreach ($tags as $tag) {
            $tagKeys = self::getTaggedKeys($tag);
            $totalKeys += count($tagKeys);
            foreach ($tagKeys as $key) {
                $ttl = self::timeToLive($key);
                if ($ttl !== false) {
                    $ttlSum += $ttl;
                }
            }
        }

        return [
            'total_keys' => $totalKeys,
            'average_ttl' => $totalKeys > 0 ? $ttlSum / $totalKeys : 0,
        ];
    }

    /**
     * Recompute all children under multiple parents recursively.
     *
     * @param array    $parentKeys Array of parent cache keys
     * @param callable $callback   Function to recompute each child
     * @param int      $expiration Cache lifetime in seconds (default 1 hour)
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::recomputeAllChildrenRecursively([Cache::makeKey('parent1')], fn($key) => computeChild($key), 3600);
     * ```
     */
    public static function recomputeAllChildrenRecursively(array $parentKeys, callable $callback, int $expiration = 3600): void
    {
        foreach ($parentKeys as $parentKey) {
            $children = get_transient('children_' . $parentKey) ?: [];

            foreach ($children as $childKey) {
                set_transient($childKey, $callback($childKey), $expiration);

                // Recursively recompute children of this child
                self::recomputeAllChildrenRecursively([$childKey], $callback, $expiration);
            }
        }
    }

    /**
     * Retrieve minimum, maximum, and average TTL for all children under multiple parents.
     *
     * @param array $parentKeys Array of parent cache keys
     *
     * @return array ['min' => int, 'max' => int, 'average' => float]
     *
     * @example
     * ```php
     * $stats = Cache::ttlStatsForParents([Cache::makeKey('parent1'), Cache::makeKey('parent2')]);
     * ```
     */
    public static function ttlStatsForParents(array $parentKeys): array
    {
        $ttls = [];

        foreach ($parentKeys as $parentKey) {
            $children = get_transient('children_' . $parentKey) ?: [];
            foreach ($children as $childKey) {
                $ttl = self::timeToLive($childKey);
                if ($ttl !== false) {
                    $ttls[] = $ttl;
                }
            }
        }

        if (empty($ttls)) {
            return ['min' => 0, 'max' => 0, 'average' => 0];
        }

        return [
            'min' => min($ttls),
            'max' => max($ttls),
            'average' => array_sum($ttls) / count($ttls),
        ];
    }

    /**
     * Refresh all versioned keys under a tag if their TTL is below a threshold.
     *
     * @param string   $tag         Tag name
     * @param callable $callback    Function to recompute each key
     * @param int      $ttlThreshold TTL threshold in seconds
     * @param int      $expiration   Cache lifetime in seconds (default 1 hour)
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::refreshVersionedTagKeysIfTTLBelow('products', fn($key) => compute($key), 600, 3600);
     * ```
     */
    public static function refreshVersionedTagKeysIfTTLBelow(string $tag, callable $callback, int $ttlThreshold, int $expiration = 3600): void
    {
        $keys = self::getTaggedKeys($tag);

        foreach ($keys as $key) {
            $versionCount = self::countVersions($key);

            for ($v = 1; $v <= $versionCount; $v++) {
                $versionedKey = "{$key}_v{$v}";
                $ttl = self::timeToLive($versionedKey);

                if ($ttl === false || $ttl < $ttlThreshold) {
                    set_transient($versionedKey, $callback($versionedKey), $expiration);
                }
            }
        }
    }

    /**
     * Compute combined TTL analytics across multiple parents and tags.
     *
     * @param array $parentKeys Array of parent cache keys
     * @param array $tags       Array of tag names
     *
     * @return array ['min' => int, 'max' => int, 'average' => float]
     *
     * @example
     * ```php
     * $stats = Cache::combinedTTLStats([Cache::makeKey('parent1')], ['products']);
     * ```
     */
    public static function combinedTTLStats(array $parentKeys, array $tags): array
    {
        $ttls = [];

        // Parent keys
        foreach ($parentKeys as $parentKey) {
            $children = get_transient('children_' . $parentKey) ?: [];
            foreach ($children as $childKey) {
                $ttl = self::timeToLive($childKey);
                if ($ttl !== false) {
                    $ttls[] = $ttl;
                }
            }
        }

        // Tag keys
        foreach ($tags as $tag) {
            $keys = self::getTaggedKeys($tag);
            foreach ($keys as $key) {
                $ttl = self::timeToLive($key);
                if ($ttl !== false) {
                    $ttls[] = $ttl;
                }
            }
        }

        if (empty($ttls)) {
            return ['min' => 0, 'max' => 0, 'average' => 0];
        }

        return [
            'min' => min($ttls),
            'max' => max($ttls),
            'average' => array_sum($ttls) / count($ttls),
        ];
    }

    /**
     * Refresh multiple parents and tags recursively if TTL is below a threshold.
     *
     * @param array    $parentKeys  Array of parent cache keys
     * @param array    $tags        Array of tag names
     * @param callable $callback    Function to recompute key
     * @param int      $ttlThreshold TTL threshold in seconds
     * @param int      $expiration   Cache lifetime in seconds (default 1 hour)
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::refreshParentsAndTagsRecursivelyIfTTLBelow([Cache::makeKey('parent1')], ['products'], fn($key) => compute($key), 600, 3600);
     * ```
     */
    public static function refreshParentsAndTagsRecursivelyIfTTLBelow(array $parentKeys, array $tags, callable $callback, int $ttlThreshold, int $expiration = 3600): void
    {
        // Refresh parents recursively
        self::recomputeAllChildrenRecursively($parentKeys, $callback, $expiration);

        // Refresh tags recursively
        foreach ($tags as $tag) {
            self::refreshVersionedTagKeysIfTTLBelow($tag, $callback, $ttlThreshold, $expiration);
        }
    }

    /**
     * Count total versioned keys under a tag.
     *
     * @param string $tag Tag name
     *
     * @return int Total number of versioned keys
     *
     * @example
     * ```php
     * $count = Cache::countVersionedKeysForTag('products');
     * ```
     */
    public static function countVersionedKeysForTag(string $tag): int
    {
        $keys = self::getTaggedKeys($tag);
        $total = 0;

        // Loop through each key and count versions
        foreach ($keys as $key) {
            $total += self::countVersions($key);
        }

        return $total;
    }

    /**
     * Recompute multiple parents and associated tags in batch.
     *
     * @param array    $parents  Array of parent cache keys
     * @param array    $tags     Array of tag names
     * @param callable $callback Function to recompute each key
     * @param int      $expiration Cache lifetime in seconds (default 1 hour)
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::batchRecomputeParentsAndTags([Cache::makeKey('parent1')], ['products'], fn($key) => compute($key), 3600);
     * ```
     */
    public static function batchRecomputeParentsAndTags(array $parents, array $tags, callable $callback, int $expiration = 3600): void
    {
        // Recompute parents
        foreach ($parents as $parentKey) {
            $children = get_transient('children_' . $parentKey) ?: [];
            foreach ($children as $childKey) {
                set_transient($childKey, $callback($childKey), $expiration);
            }
        }

        // Recompute tags
        foreach ($tags as $tag) {
            $keys = self::getTaggedKeys($tag);
            foreach ($keys as $key) {
                set_transient($key, $callback($key), $expiration);
            }
        }
    }

    /**
     * Delete versioned keys under a tag whose TTL is below a threshold.
     *
     * @param string $tag         Tag name
     * @param int    $ttlThreshold TTL threshold in seconds
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::deleteVersionedKeysTTLBelow('products', 600);
     * ```
     */
    public static function deleteVersionedKeysTTLBelow(string $tag, int $ttlThreshold): void
    {
        $keys = self::getTaggedKeys($tag);

        foreach ($keys as $key) {
            $versions = self::countVersions($key);

            for ($v = 1; $v <= $versions; $v++) {
                $versionedKey = "{$key}_v{$v}";
                $ttl = self::timeToLive($versionedKey);

                if ($ttl === false || $ttl < $ttlThreshold) {
                    self::delete($versionedKey);
                }
            }
        }
    }

    /**
     * Compute average TTL for versioned keys under multiple tags.
     *
     * @param array $tags Array of tag names
     *
     * @return float Average TTL in seconds
     *
     * @example
     * ```php
     * $avgTTL = Cache::averageTTLForVersionedTags(['products', 'featured']);
     * ```
     */
    public static function averageTTLForVersionedTags(array $tags): float
    {
        $ttlSum = 0;
        $count = 0;

        foreach ($tags as $tag) {
            $keys = self::getTaggedKeys($tag);
            foreach ($keys as $key) {
                $versions = self::countVersions($key);
                for ($v = 1; $v <= $versions; $v++) {
                    $versionedKey = "{$key}_v{$v}";
                    $ttl = self::timeToLive($versionedKey);
                    if ($ttl !== false) {
                        $ttlSum += $ttl;
                        $count++;
                    }
                }
            }
        }

        return $count > 0 ? $ttlSum / $count : 0;
    }

    /**
     * Clear all children under multiple parents whose TTL is below a threshold.
     *
     * @param array $parentKeys   Array of parent cache keys
     * @param int   $ttlThreshold TTL threshold in seconds
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::clearChildrenTTLBelow([Cache::makeKey('parent1'), Cache::makeKey('parent2')], 600);
     * ```
     */
    public static function clearChildrenTTLBelow(array $parentKeys, int $ttlThreshold): void
    {
        foreach ($parentKeys as $parentKey) {
            $children = get_transient('children_' . $parentKey) ?: [];

            foreach ($children as $childKey) {
                $ttl = self::timeToLive($childKey);
                if ($ttl === false || $ttl < $ttlThreshold) {
                    self::delete($childKey);
                }
            }
        }
    }

    /**
     * Refresh all children under multiple parents if any dependent TTL is below a threshold.
     *
     * @param array    $parentKeys  Array of parent cache keys
     * @param callable $callback    Function to recompute each child
     * @param int      $ttlThreshold TTL threshold in seconds
     * @param int      $expiration   Cache lifetime in seconds (default 1 hour)
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::refreshChildrenIfDependentsTTLBelow([Cache::makeKey('parent1')], fn($key) => compute($key), 600, 3600);
     * ```
     */
    public static function refreshChildrenIfDependentsTTLBelow(array $parentKeys, callable $callback, int $ttlThreshold, int $expiration = 3600): void
    {
        foreach ($parentKeys as $parentKey) {
            $children = get_transient('children_' . $parentKey) ?: [];
            $needsRefresh = false;

            // Check if any child TTL is below threshold
            foreach ($children as $childKey) {
                $ttl = self::timeToLive($childKey);
                if ($ttl === false || $ttl < $ttlThreshold) {
                    $needsRefresh = true;
                    break;
                }
            }

            // Refresh all children if needed
            if ($needsRefresh) {
                foreach ($children as $childKey) {
                    set_transient($childKey, $callback($childKey), $expiration);
                }
            }
        }
    }

    /**
     * Retrieve TTL statistics (min, max, average) for versioned keys under multiple parents.
     *
     * @param array $parentKeys Array of parent cache keys
     *
     * @return array ['min' => int, 'max' => int, 'average' => float]
     *
     * @example
     * ```php
     * $stats = Cache::versionedTTLStatsForParents([Cache::makeKey('parent1')]);
     * ```
     */
    public static function versionedTTLStatsForParents(array $parentKeys): array
    {
        $ttls = [];

        foreach ($parentKeys as $parentKey) {
            $children = get_transient('children_' . $parentKey) ?: [];
            foreach ($children as $childKey) {
                $versions = self::countVersions($childKey);
                for ($v = 1; $v <= $versions; $v++) {
                    $versionedKey = "{$childKey}_v{$v}";
                    $ttl = self::timeToLive($versionedKey);
                    if ($ttl !== false) {
                        $ttls[] = $ttl;
                    }
                }
            }
        }

        if (empty($ttls)) {
            return ['min' => 0, 'max' => 0, 'average' => 0];
        }

        return [
            'min' => min($ttls),
            'max' => max($ttls),
            'average' => array_sum($ttls) / count($ttls),
        ];
    }

    /**
     * Recompute all versioned keys under multiple tags in batch.
     *
     * @param array    $tags       Array of tag names
     * @param callable $callback   Function to recompute each key
     * @param int      $expiration Cache lifetime in seconds (default 1 hour)
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::batchRecomputeVersionedTags(['products', 'featured'], fn($key) => compute($key), 3600);
     * ```
     */
    public static function batchRecomputeVersionedTags(array $tags, callable $callback, int $expiration = 3600): void
    {
        foreach ($tags as $tag) {
            $keys = self::getTaggedKeys($tag);
            foreach ($keys as $key) {
                $versions = self::countVersions($key);
                for ($v = 1; $v <= $versions; $v++) {
                    $versionedKey = "{$key}_v{$v}";
                    set_transient($versionedKey, $callback($versionedKey), $expiration);
                }
            }
        }
    }

    /**
     * Clear all versioned keys under multiple tags if TTL is below a threshold.
     *
     * @param array $tags         Array of tag names
     * @param int   $ttlThreshold TTL threshold in seconds
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::clearVersionedTagsTTLBelow(['products', 'featured'], 600);
     * ```
     */
    public static function clearVersionedTagsTTLBelow(array $tags, int $ttlThreshold): void
    {
        foreach ($tags as $tag) {
            $keys = self::getTaggedKeys($tag);
            foreach ($keys as $key) {
                $versions = self::countVersions($key);
                for ($v = 1; $v <= $versions; $v++) {
                    $versionedKey = "{$key}_v{$v}";
                    $ttl = self::timeToLive($versionedKey);
                    if ($ttl === false || $ttl < $ttlThreshold) {
                        self::delete($versionedKey);
                    }
                }
            }
        }
    }

    /**
     * Retrieve all children under multiple parents whose TTL is below a threshold.
     *
     * @param array $parentKeys   Array of parent cache keys
     * @param int   $ttlThreshold TTL threshold in seconds
     *
     * @return array List of child keys below TTL
     *
     * @example
     * ```php
     * $children = Cache::childrenTTLBelow([Cache::makeKey('parent1')], 600);
     * ```
     */
    public static function childrenTTLBelow(array $parentKeys, int $ttlThreshold): array
    {
        $result = [];

        foreach ($parentKeys as $parentKey) {
            $children = get_transient('children_' . $parentKey) ?: [];
            foreach ($children as $childKey) {
                $ttl = self::timeToLive($childKey);
                if ($ttl === false || $ttl < $ttlThreshold) {
                    $result[] = $childKey;
                }
            }
        }

        return $result;
    }

    /**
     * Retrieve all dependents of multiple parents recursively.
     *
     * @param array $parentKeys Array of parent cache keys
     *
     * @return array List of all dependent keys
     *
     * @example
     * ```php
     * $dependents = Cache::allDependents([Cache::makeKey('parent1'), Cache::makeKey('parent2')]);
     * ```
     */
    public static function allDependents(array $parentKeys): array
    {
        $result = [];

        foreach ($parentKeys as $parentKey) {
            $dependents = get_transient('dependents_' . $parentKey) ?: [];
            foreach ($dependents as $depKey) {
                $result[] = $depKey;

                // Recursively get dependents of this key
                $result = array_merge($result, self::allDependents([$depKey]));
            }
        }

        return array_unique($result);
    }

    /**
     * Schedule periodic refresh for multiple parents and their versioned children.
     *
     * @param array    $parentKeys Array of parent cache keys
     * @param callable $callback   Function to recompute each child
     * @param int      $interval   Refresh interval in seconds
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::scheduleParentChildrenRefresh([Cache::makeKey('parent1')], fn($key) => compute($key), 3600);
     * ```
     */
    public static function scheduleParentChildrenRefresh(array $parentKeys, callable $callback, int $interval): void
    {
        foreach ($parentKeys as $parentKey) {
            $children = get_transient('children_' . $parentKey) ?: [];
            foreach ($children as $childKey) {
                $versions = self::countVersions($childKey);
                for ($v = 1; $v <= $versions; $v++) {
                    $versionedKey = "{$childKey}_v{$v}";
                    self::schedulePersistent($versionedKey, $callback, $interval);
                }
            }
        }
    }

    /**
     * Retrieve all versioned keys under multiple parents and tags whose TTL is below a threshold.
     *
     * @param array $parentKeys   Array of parent cache keys
     * @param array $tags         Array of tag names
     * @param int   $ttlThreshold TTL threshold in seconds
     *
     * @return array List of keys below TTL
     *
     * @example
     * ```php
     * $keys = Cache::versionedKeysTTLBelow([Cache::makeKey('parent1')], ['products'], 600);
     * ```
     */
    public static function versionedKeysTTLBelow(array $parentKeys, array $tags, int $ttlThreshold): array
    {
        $result = [];

        // Check parent children
        foreach ($parentKeys as $parentKey) {
            $children = get_transient('children_' . $parentKey) ?: [];
            foreach ($children as $childKey) {
                $versions = self::countVersions($childKey);
                for ($v = 1; $v <= $versions; $v++) {
                    $versionedKey = "{$childKey}_v{$v}";
                    $ttl = self::timeToLive($versionedKey);
                    if ($ttl === false || $ttl < $ttlThreshold) {
                        $result[] = $versionedKey;
                    }
                }
            }
        }

        // Check tag keys
        foreach ($tags as $tag) {
            $keys = self::getTaggedKeys($tag);
            foreach ($keys as $key) {
                $versions = self::countVersions($key);
                for ($v = 1; $v <= $versions; $v++) {
                    $versionedKey = "{$key}_v{$v}";
                    $ttl = self::timeToLive($versionedKey);
                    if ($ttl === false || $ttl < $ttlThreshold) {
                        $result[] = $versionedKey;
                    }
                }
            }
        }

        return array_unique($result);
    }

    /**
     * Clear all versioned keys under multiple parents and tags whose TTL is below a threshold.
     *
     * @param array $parentKeys   Array of parent cache keys
     * @param array $tags         Array of tag names
     * @param int   $ttlThreshold TTL threshold in seconds
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::clearVersionedParentsAndTagsTTLBelow([Cache::makeKey('parent1')], ['products'], 600);
     * ```
     */
    public static function clearVersionedParentsAndTagsTTLBelow(array $parentKeys, array $tags, int $ttlThreshold): void
    {
        $keysToClear = self::versionedKeysTTLBelow($parentKeys, $tags, $ttlThreshold);

        foreach ($keysToClear as $key) {
            self::delete($key);
        }
    }

    /**
     * Retrieve all versioned keys under a tag along with their TTL for monitoring.
     *
     * @param string $tag Tag name
     *
     * @return array ['key' => ttl] mapping
     *
     * @example
     * ```php
     * $monitoring = Cache::tagVersionedKeysWithTTL('products');
     * ```
     */
    public static function tagVersionedKeysWithTTL(string $tag): array
    {
        $result = [];
        $keys = self::getTaggedKeys($tag);

        foreach ($keys as $key) {
            $versions = self::countVersions($key);
            for ($v = 1; $v <= $versions; $v++) {
                $versionedKey = "{$key}_v{$v}";
                $ttl = self::timeToLive($versionedKey);
                $result[$versionedKey] = $ttl !== false ? $ttl : 0;
            }
        }

        return $result;
    }

    /**
     * Recompute all dependents of multiple parents recursively.
     *
     * @param array    $parentKeys  Array of parent cache keys
     * @param callable $callback    Function to recompute each dependent
     * @param int      $expiration  Cache lifetime in seconds (default 1 hour)
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::recomputeDependentsRecursively([Cache::makeKey('parent1')], fn($key) => compute($key), 3600);
     * ```
     */
    public static function recomputeDependentsRecursively(array $parentKeys, callable $callback, int $expiration = 3600): void
    {
        foreach ($parentKeys as $parentKey) {
            $dependents = get_transient('dependents_' . $parentKey) ?: [];
            foreach ($dependents as $depKey) {
                set_transient($depKey, $callback($depKey), $expiration);

                // Recursively recompute dependents of this dependent
                self::recomputeDependentsRecursively([$depKey], $callback, $expiration);
            }
        }
    }

    /**
     * Schedule periodic refresh for all versioned keys under multiple parents and tags.
     *
     * @param array    $parentKeys Array of parent cache keys
     * @param array    $tags       Array of tag names
     * @param callable $callback   Function to recompute each key
     * @param int      $interval   Refresh interval in seconds
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::scheduleParentsAndTagsRefresh([Cache::makeKey('parent1')], ['products'], fn($key) => compute($key), 3600);
     * ```
     */
    public static function scheduleParentsAndTagsRefresh(array $parentKeys, array $tags, callable $callback, int $interval): void
    {
        // Schedule refresh for parent children
        self::scheduleParentChildrenRefresh($parentKeys, $callback, $interval);

        // Schedule refresh for tags
        foreach ($tags as $tag) {
            $keys = self::getTaggedKeys($tag);
            foreach ($keys as $key) {
                $versions = self::countVersions($key);
                for ($v = 1; $v <= $versions; $v++) {
                    $versionedKey = "{$key}_v{$v}";
                    self::schedulePersistent($versionedKey, $callback, $interval);
                }
            }
        }
    }

    /**
     * Retrieve hierarchical TTL analytics for parents, children, and tags.
     *
     * @param array $parentKeys Array of parent cache keys
     * @param array $tags       Array of tag names
     *
     * @return array ['min' => int, 'max' => int, 'average' => float]
     *
     * @example
     * ```php
     * $stats = Cache::hierarchicalTTLStats([Cache::makeKey('parent1')], ['products']);
     * ```
     */
    public static function hierarchicalTTLStats(array $parentKeys, array $tags): array
    {
        $ttls = [];

        // Parents and their children
        foreach ($parentKeys as $parentKey) {
            $children = get_transient('children_' . $parentKey) ?: [];
            foreach ($children as $childKey) {
                $ttl = self::timeToLive($childKey);
                if ($ttl !== false) {
                    $ttls[] = $ttl;
                }
            }
        }

        // Tags
        foreach ($tags as $tag) {
            $keys = self::getTaggedKeys($tag);
            foreach ($keys as $key) {
                $ttl = self::timeToLive($key);
                if ($ttl !== false) {
                    $ttls[] = $ttl;
                }
            }
        }

        if (empty($ttls)) {
            return ['min' => 0, 'max' => 0, 'average' => 0];
        }

        return [
            'min' => min($ttls),
            'max' => max($ttls),
            'average' => array_sum($ttls) / count($ttls),
        ];
    }

    /**
     * Clear all versioned keys under parents and tags whose TTL is below a threshold recursively.
     *
     * @param array $parentKeys   Array of parent cache keys
     * @param array $tags         Array of tag names
     * @param int   $ttlThreshold TTL threshold in seconds
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::clearVersionedHierarchyTTLBelow([Cache::makeKey('parent1')], ['products'], 600);
     * ```
     */
    public static function clearVersionedHierarchyTTLBelow(array $parentKeys, array $tags, int $ttlThreshold): void
    {
        $keysToClear = self::versionedKeysTTLBelow($parentKeys, $tags, $ttlThreshold);

        foreach ($keysToClear as $key) {
            self::delete($key);
        }
    }

    /**
     * Retrieve a flat list of all versioned keys under multiple parents and tags.
     *
     * @param array $parentKeys Array of parent cache keys
     * @param array $tags       Array of tag names
     *
     * @return array List of all versioned keys
     *
     * @example
     * ```php
     * $keys = Cache::allVersionedKeys([Cache::makeKey('parent1')], ['products']);
     * ```
     */
    public static function allVersionedKeys(array $parentKeys, array $tags): array
    {
        $result = [];

        // Parent children
        foreach ($parentKeys as $parentKey) {
            $children = get_transient('children_' . $parentKey) ?: [];
            foreach ($children as $childKey) {
                $versions = self::countVersions($childKey);
                for ($v = 1; $v <= $versions; $v++) {
                    $result[] = "{$childKey}_v{$v}";
                }
            }
        }

        // Tags
        foreach ($tags as $tag) {
            $keys = self::getTaggedKeys($tag);
            foreach ($keys as $key) {
                $versions = self::countVersions($key);
                for ($v = 1; $v <= $versions; $v++) {
                    $result[] = "{$key}_v{$v}";
                }
            }
        }

        return array_unique($result);
    }

    /**
     * Monitor all parents and their children for TTL below a threshold and return keys needing refresh.
     *
     * @param array $parentKeys   Array of parent cache keys
     * @param int   $ttlThreshold TTL threshold in seconds
     *
     * @return array List of keys requiring refresh
     *
     * @example
     * ```php
     * $keysToRefresh = Cache::monitorParentsTTL([Cache::makeKey('parent1')], 600);
     * ```
     */
    public static function monitorParentsTTL(array $parentKeys, int $ttlThreshold): array
    {
        $keys = [];

        foreach ($parentKeys as $parentKey) {
            $children = get_transient('children_' . $parentKey) ?: [];
            foreach ($children as $childKey) {
                $ttl = self::timeToLive($childKey);
                if ($ttl === false || $ttl < $ttlThreshold) {
                    $keys[] = $childKey;
                }
            }
        }

        return $keys;
    }

    /**
     * Schedule analytics-based refresh for versioned keys under multiple tags.
     *
     * @param array    $tags       Array of tag names
     * @param callable $callback   Function to recompute each key
     * @param int      $interval   Refresh interval in seconds
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::scheduleAnalyticsRefreshForTags(['products', 'featured'], fn($key) => compute($key), 3600);
     * ```
     */
    public static function scheduleAnalyticsRefreshForTags(array $tags, callable $callback, int $interval): void
    {
        foreach ($tags as $tag) {
            $keys = self::getTaggedKeys($tag);
            foreach ($keys as $key) {
                $versions = self::countVersions($key);
                for ($v = 1; $v <= $versions; $v++) {
                    $versionedKey = "{$key}_v{$v}";
                    self::schedulePersistent($versionedKey, $callback, $interval);
                }
            }
        }
    }

    /**
     * Recompute and refresh keys under parents and tags if average TTL drops below a threshold.
     *
     * @param array    $parentKeys  Array of parent cache keys
     * @param array    $tags        Array of tag names
     * @param callable $callback    Function to recompute each key
     * @param float    $avgTTLThreshold Average TTL threshold in seconds
     * @param int      $expiration   Cache lifetime in seconds (default 1 hour)
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::refreshIfAverageTTLBelow([Cache::makeKey('parent1')], ['products'], fn($key) => compute($key), 500, 3600);
     * ```
     */
    public static function refreshIfAverageTTLBelow(array $parentKeys, array $tags, callable $callback, float $avgTTLThreshold, int $expiration = 3600): void
    {
        $stats = self::hierarchicalTTLStats($parentKeys, $tags);
        if ($stats['average'] < $avgTTLThreshold) {
            foreach ($parentKeys as $parentKey) {
                $children = get_transient('children_' . $parentKey) ?: [];
                foreach ($children as $childKey) {
                    set_transient($childKey, $callback($childKey), $expiration);
                }
            }

            foreach ($tags as $tag) {
                $keys = self::getTaggedKeys($tag);
                foreach ($keys as $key) {
                    set_transient($key, $callback($key), $expiration);
                }
            }
        }
    }

    /**
     * Retrieve a map of all parents and tags with their respective average TTL.
     *
     * @param array $parentKeys Array of parent cache keys
     * @param array $tags       Array of tag names
     *
     * @return array ['parents' => ['parentKey' => avgTTL], 'tags' => ['tag' => avgTTL]]
     *
     * @example
     * ```php
     * $avgTTLs = Cache::mapAverageTTL([Cache::makeKey('parent1')], ['products']);
     * ```
     */
    public static function mapAverageTTL(array $parentKeys, array $tags): array
    {
        $map = ['parents' => [], 'tags' => []];

        foreach ($parentKeys as $parentKey) {
            $children = get_transient('children_' . $parentKey) ?: [];
            $ttls = [];
            foreach ($children as $childKey) {
                $ttl = self::timeToLive($childKey);
                if ($ttl !== false) $ttls[] = $ttl;
            }
            $map['parents'][$parentKey] = !empty($ttls) ? array_sum($ttls) / count($ttls) : 0;
        }

        foreach ($tags as $tag) {
            $keys = self::getTaggedKeys($tag);
            $ttls = [];
            foreach ($keys as $key) {
                $ttl = self::timeToLive($key);
                if ($ttl !== false) $ttls[] = $ttl;
            }
            $map['tags'][$tag] = !empty($ttls) ? array_sum($ttls) / count($ttls) : 0;
        }

        return $map;
    }

    /**
     * Clear versioned keys across parents and tags whose TTL falls below a computed percentile.
     *
     * @param array $parentKeys Array of parent cache keys
     * @param array $tags       Array of tag names
     * @param float $percentile Percentile threshold (0-100)
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::clearBelowPercentileTTL([Cache::makeKey('parent1')], ['products'], 20);
     * ```
     */
    public static function clearBelowPercentileTTL(array $parentKeys, array $tags, float $percentile): void
    {
        $keys = self::allVersionedKeys($parentKeys, $tags);
        $ttls = [];

        // Collect TTLs
        foreach ($keys as $key) {
            $ttl = self::timeToLive($key);
            if ($ttl !== false) $ttls[$key] = $ttl;
        }

        if (empty($ttls)) return;

        // Compute percentile
        sort($ttls);
        $index = max(0, (int)(count($ttls) * $percentile / 100) - 1);
        $threshold = $ttls[$index];

        // Delete keys below percentile
        foreach ($ttls as $key => $ttl) {
            if ($ttl <= $threshold) self::delete($key);
        }
    }

    /**
     * Promote a specific version of a key to the latest version.
     *
     * @param string $key     Base cache key
     * @param int    $version Version number to promote
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::promoteVersion(Cache::makeKey('config'), 2);
     * ```
     */
    public static function promoteVersion(string $key, int $version): void
    {
        $versionedKey = "{$key}_v{$version}";
        $latestKey = "{$key}_v" . self::latestVersion($key);

        // Get value of the version to promote
        $value = get_transient($versionedKey);
        if ($value !== false) {
            set_transient($latestKey, $value, self::defaultExpiration());
        }
    }

    /**
     * Recompute keys with fallback strategy across multiple versions.
     *
     * @param string   $key       Base cache key
     * @param callable $callback  Function to compute key
     * @param int      $maxVersion Maximum version to attempt
     *
     * @return mixed Computed value
     *
     * @example
     * ```php
     * $value = Cache::recomputeWithFallback(Cache::makeKey('config'), fn() => computeConfig(), 3);
     * ```
     */
    public static function recomputeWithFallback(string $key, callable $callback, int $maxVersion)
    {
        for ($v = $maxVersion; $v >= 1; $v--) {
            $versionedKey = "{$key}_v{$v}";
            $value = get_transient($versionedKey);
            if ($value !== false) return $value;
        }

        // Cache miss for all versions, compute and store latest
        $value = $callback();
        set_transient("{$key}_v{$maxVersion}", $value, self::defaultExpiration());
        return $value;
    }

    /**
     * Schedule cross-hierarchy refresh for parents and their dependents.
     *
     * @param array    $parentKeys Array of parent cache keys
     * @param callable $callback   Function to recompute each key
     * @param int      $interval   Refresh interval in seconds
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::scheduleCrossHierarchyRefresh([Cache::makeKey('parent1')], fn($key) => compute($key), 3600);
     * ```
     */
    public static function scheduleCrossHierarchyRefresh(array $parentKeys, callable $callback, int $interval): void
    {
        foreach ($parentKeys as $parentKey) {
            $keys = array_merge([$parentKey], self::allDependents([$parentKey]));
            foreach ($keys as $key) {
                $versions = self::countVersions($key);
                for ($v = 1; $v <= $versions; $v++) {
                    $versionedKey = "{$key}_v{$v}";
                    self::schedulePersistent($versionedKey, $callback, $interval);
                }
            }
        }
    }

    /**
     * Retrieve the latest version number for a given key.
     *
     * @param string $key Base cache key
     *
     * @return int Latest version number
     *
     * @example
     * ```php
     * $latest = Cache::latestVersion(Cache::makeKey('config'));
     * ```
     */
    public static function latestVersion(string $key): int
    {
        // Retrieve version info stored in transient
        $version = get_transient("version_{$key}");
        return $version !== false ? (int)$version : 1;
    }

    /**
     * Default expiration for cache keys (used when none specified).
     *
     * @return int Expiration time in seconds
     *
     * @example
     * ```php
     * $expiration = Cache::defaultExpiration();
     * ```
     */
    public static function defaultExpiration(): int
    {
        return 3600; // 1 hour default
    }

    /**
     * Archive old versions of a key, keeping only the latest N versions.
     *
     * @param string $key        Base cache key
     * @param int    $keepLatest Number of latest versions to retain
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::archiveOldVersions(Cache::makeKey('config'), 3);
     * ```
     */
    public static function archiveOldVersions(string $key, int $keepLatest): void
    {
        $latest = self::latestVersion($key);

        for ($v = 1; $v <= max(0, $latest - $keepLatest); $v++) {
            $versionedKey = "{$key}_v{$v}";
            self::delete($versionedKey);
        }
    }

    /**
     * Promote versioned keys based on analytics (e.g., most accessed) across parents and tags.
     *
     * @param array $parentKeys Array of parent cache keys
     * @param array $tags       Array of tag names
     * @param callable $analyticsCallback Callback to rank keys
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::promoteByAnalytics([Cache::makeKey('parent1')], ['products'], fn($key) => accessCount($key));
     * ```
     */
    public static function promoteByAnalytics(array $parentKeys, array $tags, callable $analyticsCallback): void
    {
        $keys = self::allVersionedKeys($parentKeys, $tags);

        // Rank keys by analytics metric
        uasort($keys, function ($a, $b) use ($analyticsCallback) {
            return $analyticsCallback($b) <=> $analyticsCallback($a);
        });

        // Promote top key
        if (!empty($keys)) {
            $topKey = reset($keys);
            self::promoteVersion($topKey, self::latestVersion($topKey));
        }
    }

    /**
     * Clear hierarchical versions older than a specific timestamp.
     *
     * @param array $parentKeys Array of parent cache keys
     * @param array $tags       Array of tag names
     * @param int   $timestamp  Unix timestamp; versions older than this will be cleared
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::clearVersionsOlderThan([Cache::makeKey('parent1')], ['products'], strtotime('-7 days'));
     * ```
     */
    public static function clearVersionsOlderThan(array $parentKeys, array $tags, int $timestamp): void
    {
        $keys = self::allVersionedKeys($parentKeys, $tags);

        foreach ($keys as $key) {
            $meta = get_transient("meta_{$key}");
            if ($meta && isset($meta['created']) && $meta['created'] < $timestamp) {
                self::delete($key);
            }
        }
    }

    /**
     * Tag a versioned key with metadata (e.g., creation timestamp, author, notes).
     *
     * @param string $key      Versioned cache key
     * @param array  $metadata Associative array of metadata
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::tagVersionedKey(Cache::makeKey('config') . '_v1', ['created' => time(), 'author' => 'admin']);
     * ```
     */
    public static function tagVersionedKey(string $key, array $metadata): void
    {
        set_transient("meta_{$key}", $metadata, self::defaultExpiration());
    }

    /**
     * Retrieve metadata for a versioned key.
     *
     * @param string $key Versioned cache key
     *
     * @return array|null Metadata or null if not found
     *
     * @example
     * ```php
     * $meta = Cache::getVersionedKeyMetadata(Cache::makeKey('config') . '_v1');
     * ```
     */
    public static function getVersionedKeyMetadata(string $key): ?array
    {
        $meta = get_transient("meta_{$key}");
        return $meta !== false ? $meta : null;
    }

    /**
     * Update TTL for a versioned key and optionally refresh metadata timestamp.
     *
     * @param string $key        Versioned cache key
     * @param int    $expiration New expiration in seconds
     * @param bool   $updateMeta Whether to update metadata timestamp
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::updateVersionTTL(Cache::makeKey('config') . '_v1', 7200, true);
     * ```
     */
    public static function updateVersionTTL(string $key, int $expiration, bool $updateMeta = false): void
    {
        $value = get_transient($key);
        if ($value !== false) {
            set_transient($key, $value, $expiration);

            if ($updateMeta) {
                $meta = self::getVersionedKeyMetadata($key) ?: [];
                $meta['updated'] = time();
                self::tagVersionedKey($key, $meta);
            }
        }
    }

    /**
     * Compute dependency impact score for parents and children.
     *
     * @param array $parentKeys Array of parent cache keys
     *
     * @return array ['parentKey' => score, ...] Higher score means more dependencies
     *
     * @example
     * ```php
     * $impact = Cache::computeDependencyImpact([Cache::makeKey('parent1')]);
     * ```
     */
    public static function computeDependencyImpact(array $parentKeys): array
    {
        $impact = [];
        foreach ($parentKeys as $parentKey) {
            $children = get_transient('children_' . $parentKey) ?: [];
            $impact[$parentKey] = count($children) + array_sum(array_map(fn($child) => count(get_transient('children_' . $child) ?: []), $children));
        }
        return $impact;
    }

    /**
     * Refresh versioned keys whose TTL is in the bottom percentile.
     *
     * @param array $parentKeys Array of parent cache keys
     * @param array $tags       Array of tag names
     * @param float $percentile Percentile (0-100)
     * @param callable $callback Function to recompute keys
     * @param int $expiration Cache lifetime in seconds (default 1 hour)
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::refreshBottomPercentileTTL([Cache::makeKey('parent1')], ['products'], 20, fn($key) => compute($key), 3600);
     * ```
     */
    public static function refreshBottomPercentileTTL(array $parentKeys, array $tags, float $percentile, callable $callback, int $expiration = 3600): void
    {
        $keys = self::versionedKeysTTLBelow($parentKeys, $tags, PHP_INT_MAX);
        $ttls = [];

        foreach ($keys as $key) {
            $ttl = self::timeToLive($key);
            if ($ttl !== false) $ttls[$key] = $ttl;
        }

        if (empty($ttls)) return;

        arsort($ttls);
        $thresholdIndex = max(0, (int)(count($ttls) * $percentile / 100) - 1);
        $threshold = array_values($ttls)[$thresholdIndex];

        foreach ($ttls as $key => $ttl) {
            if ($ttl <= $threshold) {
                set_transient($key, $callback($key), $expiration);
            }
        }
    }

    /**
     * Retrieve keys with TTL within a specific range.
     *
     * @param array $keys Array of cache keys
     * @param int $minTTL Minimum TTL in seconds
     * @param int $maxTTL Maximum TTL in seconds
     *
     * @return array Keys within TTL range
     *
     * @example
     * ```php
     * $keysInRange = Cache::keysWithinTTLRange(['key1', 'key2'], 300, 1200);
     * ```
     */
    public static function keysWithinTTLRange(array $keys, int $minTTL, int $maxTTL): array
    {
        $result = [];
        foreach ($keys as $key) {
            $ttl = self::timeToLive($key);
            if ($ttl !== false && $ttl >= $minTTL && $ttl <= $maxTTL) {
                $result[] = $key;
            }
        }
        return $result;
    }

    /**
     * Schedule refresh for versioned keys whose metadata indicates high access frequency.
     *
     * @param array $tags Array of tag names
     * @param callable $callback Function to recompute keys
     * @param int $interval Refresh interval in seconds
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::scheduleHighAccessRefresh(['products'], fn($key) => compute($key), 3600);
     * ```
     */
    public static function scheduleHighAccessRefresh(array $tags, callable $callback, int $interval): void
    {
        foreach ($tags as $tag) {
            $keys = self::getTaggedKeys($tag);
            foreach ($keys as $key) {
                $versions = self::countVersions($key);
                for ($v = 1; $v <= $versions; $v++) {
                    $versionedKey = "{$key}_v{$v}";
                    $meta = self::getVersionedKeyMetadata($versionedKey);
                    if ($meta && isset($meta['access_count']) && $meta['access_count'] > 100) {
                        self::schedulePersistent($versionedKey, $callback, $interval);
                    }
                }
            }
        }
    }

    /**
     * Promote the most frequently accessed version of a key to the latest version.
     *
     * @param string $key Base cache key
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::promoteMostAccessedVersion(Cache::makeKey('config'));
     * ```
     */
    public static function promoteMostAccessedVersion(string $key): void
    {
        $versions = self::countVersions($key);
        $maxAccess = -1;
        $topVersion = 1;

        // Iterate over versions to find the most accessed
        for ($v = 1; $v <= $versions; $v++) {
            $versionedKey = "{$key}_v{$v}";
            $meta = self::getVersionedKeyMetadata($versionedKey);
            $accessCount = $meta['access_count'] ?? 0;
            if ($accessCount > $maxAccess) {
                $maxAccess = $accessCount;
                $topVersion = $v;
            }
        }

        // Promote the top version
        self::promoteVersion($key, $topVersion);
    }

    /**
     * Recompute versioned keys using a fallback priority list of parents and tags.
     *
     * @param array    $parents Array of parent cache keys
     * @param array    $tags    Array of tag names
     * @param callable $callback Function to recompute keys
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::recomputeWithPriority([Cache::makeKey('parent1')], ['products'], fn($key) => compute($key));
     * ```
     */
    public static function recomputeWithPriority(array $parents, array $tags, callable $callback): void
    {
        // Recompute parent children first
        foreach ($parents as $parentKey) {
            $children = get_transient('children_' . $parentKey) ?: [];
            foreach ($children as $childKey) {
                set_transient($childKey, $callback($childKey), self::defaultExpiration());
            }
        }

        // Then recompute tagged keys
        foreach ($tags as $tag) {
            $keys = self::getTaggedKeys($tag);
            foreach ($keys as $key) {
                set_transient($key, $callback($key), self::defaultExpiration());
            }
        }
    }

    /**
     * Compute hierarchical TTL optimization recommendation.
     *
     * @param array $parentKeys Array of parent cache keys
     * @param array $tags       Array of tag names
     *
     * @return array Recommended TTL adjustments ['key' => newTTL]
     *
     * @example
     * ```php
     * $recommendations = Cache::computeTTLOptimization([Cache::makeKey('parent1')], ['products']);
     * ```
     */
    public static function computeTTLOptimization(array $parentKeys, array $tags): array
    {
        $recommendations = [];
        $allKeys = self::allVersionedKeys($parentKeys, $tags);

        foreach ($allKeys as $key) {
            $ttl = self::timeToLive($key);
            if ($ttl !== false) {
                // Suggest increase for low TTL keys, decrease for high TTL keys
                $recommendations[$key] = $ttl < 600 ? $ttl + 300 : $ttl - 300;
            }
        }

        return $recommendations;
    }

    /**
     * Refresh keys based on TTL optimization recommendations.
     *
     * @param array    $recommendations ['key' => newTTL]
     * @param callable $callback        Function to recompute key if needed
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::applyTTLOptimization($recommendations, fn($key) => compute($key));
     * ```
     */
    public static function applyTTLOptimization(array $recommendations, callable $callback): void
    {
        foreach ($recommendations as $key => $newTTL) {
            $value = get_transient($key);
            if ($value !== false) {
                set_transient($key, $value, $newTTL);
            } else {
                // Recompute if missing
                set_transient($key, $callback($key), $newTTL);
            }
        }
    }

    /**
     * Increment access count metadata for a versioned key.
     *
     * @param string $key Versioned cache key
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::incrementAccessCount(Cache::makeKey('config') . '_v1');
     * ```
     */
    public static function incrementAccessCount(string $key): void
    {
        $meta = self::getVersionedKeyMetadata($key) ?: [];
        $meta['access_count'] = ($meta['access_count'] ?? 0) + 1;
        self::tagVersionedKey($key, $meta);
    }

    /**
     * Compute dependency-weighted TTL for parents and children.
     *
     * @param array $parentKeys Array of parent cache keys
     *
     * @return array ['key' => weightedTTL]
     *
     * @example
     * ```php
     * $weightedTTLs = Cache::computeDependencyWeightedTTL([Cache::makeKey('parent1')]);
     * ```
     */
    public static function computeDependencyWeightedTTL(array $parentKeys): array
    {
        $weighted = [];
        foreach ($parentKeys as $parentKey) {
            $children = get_transient('children_' . $parentKey) ?: [];
            $parentTTL = self::timeToLive($parentKey) ?: 0;
            $childrenTTL = array_sum(array_map(fn($child) => self::timeToLive($child) ?: 0, $children));
            $weighted[$parentKey] = $parentTTL + $childrenTTL * 0.5; // weight children less
        }
        return $weighted;
    }

    /**
     * Automatically archive versions older than a retention period (days).
     *
     * @param array $parentKeys Array of parent cache keys
     * @param array $tags       Array of tag names
     * @param int   $days       Retention period in days
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::autoArchiveOldVersions([Cache::makeKey('parent1')], ['products'], 30);
     * ```
     */
    public static function autoArchiveOldVersions(array $parentKeys, array $tags, int $days): void
    {
        $threshold = time() - ($days * 86400);
        self::clearVersionsOlderThan($parentKeys, $tags, $threshold);
    }

    /**
     * Promote keys adaptively based on access frequency and TTL thresholds.
     *
     * @param array $keys       List of versioned keys
     * @param int   $minAccess  Minimum access count to consider promotion
     * @param int   $minTTL     Minimum TTL to consider promotion
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::adaptivePromotion(['key_v1', 'key_v2'], 50, 600);
     * ```
     */
    public static function adaptivePromotion(array $keys, int $minAccess, int $minTTL): void
    {
        foreach ($keys as $key) {
            $meta = self::getVersionedKeyMetadata($key);
            $ttl = self::timeToLive($key) ?: 0;
            $access = $meta['access_count'] ?? 0;
            if ($access >= $minAccess && $ttl >= $minTTL) {
                self::promoteVersion(explode('_v', $key)[0], (int)substr(strrchr($key, '_v'), 2));
            }
        }
    }

    /**
     * Clear versioned keys with low access count across parents and tags.
     *
     * @param array $parentKeys Array of parent cache keys
     * @param array $tags       Array of tag names
     * @param int   $threshold  Access count threshold
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::clearLowAccessVersions([Cache::makeKey('parent1')], ['products'], 10);
     * ```
     */
    public static function clearLowAccessVersions(array $parentKeys, array $tags, int $threshold): void
    {
        $keys = self::allVersionedKeys($parentKeys, $tags);
        foreach ($keys as $key) {
            $meta = self::getVersionedKeyMetadata($key);
            $access = $meta['access_count'] ?? 0;
            if ($access < $threshold) {
                self::delete($key);
            }
        }
    }

    /**
     * Compute average TTL per tag for analytics monitoring.
     *
     * @param array $tags Array of tag names
     *
     * @return array ['tag' => avgTTL]
     *
     * @example
     * ```php
     * $avgTTLs = Cache::averageTTLPerTag(['products', 'featured']);
     * ```
     */
    public static function averageTTLPerTag(array $tags): array
    {
        $result = [];
        foreach ($tags as $tag) {
            $keys = self::getTaggedKeys($tag);
            $ttls = array_map(fn($key) => self::timeToLive($key) ?: 0, $keys);
            $result[$tag] = !empty($ttls) ? array_sum($ttls) / count($ttls) : 0;
        }
        return $result;
    }

    /**
     * Alert if any parent or child TTL is below a threshold.
     *
     * @param array $parentKeys Array of parent cache keys
     * @param int   $threshold  TTL threshold in seconds
     *
     * @return array List of keys below threshold
     *
     * @example
     * ```php
     * $alerts = Cache::alertLowTTL([Cache::makeKey('parent1')], 300);
     * ```
     */
    public static function alertLowTTL(array $parentKeys, int $threshold): array
    {
        $alerts = [];
        foreach ($parentKeys as $parentKey) {
            $ttl = self::timeToLive($parentKey);
            if ($ttl !== false && $ttl < $threshold) {
                $alerts[] = $parentKey;
            }
            $children = get_transient('children_' . $parentKey) ?: [];
            foreach ($children as $childKey) {
                $childTTL = self::timeToLive($childKey);
                if ($childTTL !== false && $childTTL < $threshold) {
                    $alerts[] = $childKey;
                }
            }
        }
        return $alerts;
    }

    /**
     * Schedule periodic promotion for top-accessed keys per tag.
     *
     * @param array    $tags     Array of tag names
     * @param int      $topCount Number of top keys to promote
     * @param callable $callback Function to recompute promoted keys
     * @param int      $interval Refresh interval in seconds
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::scheduleTopAccessPromotion(['products'], 3, fn($key) => compute($key), 3600);
     * ```
     */
    public static function scheduleTopAccessPromotion(array $tags, int $topCount, callable $callback, int $interval): void
    {
        foreach ($tags as $tag) {
            $keys = self::getTaggedKeys($tag);
            usort($keys, fn($a, $b) => (self::getVersionedKeyMetadata($b)['access_count'] ?? 0) <=> (self::getVersionedKeyMetadata($a)['access_count'] ?? 0));
            $topKeys = array_slice($keys, 0, $topCount);
            foreach ($topKeys as $key) {
                $versions = self::countVersions($key);
                for ($v = 1; $v <= $versions; $v++) {
                    $versionedKey = "{$key}_v{$v}";
                    self::schedulePersistent($versionedKey, $callback, $interval);
                }
            }
        }
    }

    /**
     * Clear versioned keys whose TTL is in the top percentile (e.g., expired or near-expiry cleanup).
     *
     * @param array $parentKeys Array of parent cache keys
     * @param array $tags       Array of tag names
     * @param float $percentile Top percentile to clear
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::clearTopPercentileTTL([Cache::makeKey('parent1')], ['products'], 10);
     * ```
     */
    public static function clearTopPercentileTTL(array $parentKeys, array $tags, float $percentile): void
    {
        $keys = self::allVersionedKeys($parentKeys, $tags);
        $ttls = [];
        foreach ($keys as $key) {
            $ttl = self::timeToLive($key);
            if ($ttl !== false) $ttls[$key] = $ttl;
        }

        if (empty($ttls)) return;

        arsort($ttls);
        $thresholdIndex = max(0, (int)(count($ttls) * $percentile / 100) - 1);
        $threshold = array_values($ttls)[$thresholdIndex];

        foreach ($ttls as $key => $ttl) {
            if ($ttl >= $threshold) self::delete($key);
        }
    }

    /**
     * Retrieve hierarchical TTL stats per parent including min, max, and average for children.
     *
     * @param array $parentKeys Array of parent cache keys
     *
     * @return array ['parentKey' => ['min' => int, 'max' => int, 'average' => float]]
     *
     * @example
     * ```php
     * $stats = Cache::parentTTLStats([Cache::makeKey('parent1')]);
     * ```
     */
    public static function parentTTLStats(array $parentKeys): array
    {
        $stats = [];
        foreach ($parentKeys as $parentKey) {
            $children = get_transient('children_' . $parentKey) ?: [];
            $ttls = array_map(fn($child) => self::timeToLive($child) ?: 0, $children);
            if (!empty($ttls)) {
                $stats[$parentKey] = [
                    'min' => min($ttls),
                    'max' => max($ttls),
                    'average' => array_sum($ttls) / count($ttls),
                ];
            } else {
                $stats[$parentKey] = ['min' => 0, 'max' => 0, 'average' => 0];
            }
        }
        return $stats;
    }

    /**
     * Schedule refresh for keys with low TTL across multiple parents and tags.
     *
     * @param array    $parentKeys Array of parent cache keys
     * @param array    $tags       Array of tag names
     * @param int      $ttlThreshold TTL threshold in seconds
     * @param callable $callback   Function to recompute key
     * @param int      $interval   Refresh interval in seconds
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::scheduleLowTTLRefresh([Cache::makeKey('parent1')], ['products'], 300, fn($key) => compute($key), 3600);
     * ```
     */
    public static function scheduleLowTTLRefresh(array $parentKeys, array $tags, int $ttlThreshold, callable $callback, int $interval): void
    {
        $keys = array_merge(
            self::alertLowTTL($parentKeys, $ttlThreshold),
            self::versionedKeysTTLBelow($parentKeys, $tags, $ttlThreshold)
        );

        foreach ($keys as $key) {
            self::schedulePersistent($key, $callback, $interval);
        }
    }

    /**
     * Forecast TTL decay for versioned keys based on historical expiration patterns.
     *
     * @param array $keys Array of versioned cache keys
     *
     * @return array ['key' => forecastedTTL]
     *
     * @example
     * ```php
     * $forecast = Cache::forecastTTLDecay(['key_v1', 'key_v2']);
     * ```
     */
    public static function forecastTTLDecay(array $keys): array
    {
        $forecast = [];
        foreach ($keys as $key) {
            $meta = self::getVersionedKeyMetadata($key);
            $created = $meta['created'] ?? time();
            $expiration = $meta['expiration'] ?? self::defaultExpiration();
            $elapsed = time() - $created;
            $forecast[$key] = max(0, $expiration - $elapsed);
        }
        return $forecast;
    }

    /**
     * Compute aggregated analytics per tag including average TTL and access count.
     *
     * @param array $tags Array of tag names
     *
     * @return array ['tag' => ['avgTTL' => float, 'totalAccess' => int]]
     *
     * @example
     * ```php
     * $analytics = Cache::tagAnalytics(['products', 'featured']);
     * ```
     */
    public static function tagAnalytics(array $tags): array
    {
        $result = [];
        foreach ($tags as $tag) {
            $keys = self::getTaggedKeys($tag);
            $ttls = [];
            $access = 0;
            foreach ($keys as $key) {
                $ttls[] = self::timeToLive($key) ?: 0;
                $meta = self::getVersionedKeyMetadata($key);
                $access += $meta['access_count'] ?? 0;
            }
            $result[$tag] = [
                'avgTTL' => !empty($ttls) ? array_sum($ttls) / count($ttls) : 0,
                'totalAccess' => $access
            ];
        }
        return $result;
    }

    /**
     * Recompute all children of a parent if the parent TTL falls below a threshold.
     *
     * @param string   $parentKey   Parent cache key
     * @param int      $ttlThreshold TTL threshold in seconds
     * @param callable $callback     Function to recompute each child
     * @param int      $expiration   Cache lifetime in seconds (default 1 hour)
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::recomputeChildrenIfParentLowTTL(Cache::makeKey('parent1'), 300, fn($key) => compute($key), 3600);
     * ```
     */
    public static function recomputeChildrenIfParentLowTTL(string $parentKey, int $ttlThreshold, callable $callback, int $expiration = 3600): void
    {
        $parentTTL = self::timeToLive($parentKey);
        if ($parentTTL !== false && $parentTTL < $ttlThreshold) {
            $children = get_transient('children_' . $parentKey) ?: [];
            foreach ($children as $childKey) {
                set_transient($childKey, $callback($childKey), $expiration);
            }
        }
    }

    /**
     * Schedule refresh for keys predicted to expire soon based on TTL forecast.
     *
     * @param array    $keys      Versioned cache keys
     * @param int      $threshold TTL threshold in seconds
     * @param callable $callback  Function to recompute key
     * @param int      $interval  Refresh interval in seconds
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::scheduleForecastedExpiryRefresh(['key_v1', 'key_v2'], 300, fn($key) => compute($key), 3600);
     * ```
     */
    public static function scheduleForecastedExpiryRefresh(array $keys, int $threshold, callable $callback, int $interval): void
    {
        $forecast = self::forecastTTLDecay($keys);
        foreach ($forecast as $key => $ttl) {
            if ($ttl <= $threshold) {
                self::schedulePersistent($key, $callback, $interval);
            }
        }
    }

    /**
     * Retrieve hierarchical access statistics: parent + children + tag totals.
     *
     * @param array $parentKeys Array of parent cache keys
     * @param array $tags       Array of tag names
     *
     * @return array ['parents' => [...], 'tags' => [...]]
     *
     * @example
     * ```php
     * $stats = Cache::hierarchicalAccessStats([Cache::makeKey('parent1')], ['products']);
     * ```
     */
    public static function hierarchicalAccessStats(array $parentKeys, array $tags): array
    {
        $result = ['parents' => [], 'tags' => []];

        foreach ($parentKeys as $parentKey) {
            $children = get_transient('children_' . $parentKey) ?: [];
            $total = 0;
            foreach ($children as $childKey) {
                $meta = self::getVersionedKeyMetadata($childKey);
                $total += $meta['access_count'] ?? 0;
            }
            $result['parents'][$parentKey] = $total;
        }

        foreach ($tags as $tag) {
            $keys = self::getTaggedKeys($tag);
            $total = 0;
            foreach ($keys as $key) {
                $meta = self::getVersionedKeyMetadata($key);
                $total += $meta['access_count'] ?? 0;
            }
            $result['tags'][$tag] = $total;
        }

        return $result;
    }

    /**
     * Adaptively scale TTL for a versioned key based on access frequency.
     *
     * @param string $key Versioned cache key
     * @param int    $baseTTL Base TTL in seconds
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::adaptiveTTLScale(Cache::makeKey('config') . '_v1', 3600);
     * ```
     */
    public static function adaptiveTTLScale(string $key, int $baseTTL): void
    {
        $meta = self::getVersionedKeyMetadata($key) ?: [];
        $access = $meta['access_count'] ?? 0;

        // Scale TTL: increase by 10% per 50 accesses
        $scaledTTL = $baseTTL + intval(($access / 50) * 0.1 * $baseTTL);
        $value = get_transient($key);
        if ($value !== false) {
            set_transient($key, $value, $scaledTTL);
        }
    }

    /**
     * Recompute keys with multiple parent dependencies if any parent TTL falls below threshold.
     *
     * @param array    $keys        Array of child keys
     * @param array    $parentKeys  Array of parent keys
     * @param int      $ttlThreshold TTL threshold in seconds
     * @param callable $callback     Function to recompute key
     * @param int      $expiration   Cache lifetime in seconds (default 1 hour)
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::recomputeMultiParentDeps(['child1', 'child2'], [Cache::makeKey('parent1')], 300, fn($key) => compute($key), 3600);
     * ```
     */
    public static function recomputeMultiParentDeps(array $keys, array $parentKeys, int $ttlThreshold, callable $callback, int $expiration = 3600): void
    {
        $refresh = false;
        foreach ($parentKeys as $parentKey) {
            $ttl = self::timeToLive($parentKey);
            if ($ttl !== false && $ttl < $ttlThreshold) {
                $refresh = true;
                break;
            }
        }

        if ($refresh) {
            foreach ($keys as $key) {
                set_transient($key, $callback($key), $expiration);
            }
        }
    }

    /**
     * Schedule cross-tag priority recompute for high-impact keys.
     *
     * @param array    $tags       Array of tag names
     * @param callable $callback   Function to recompute keys
     * @param int      $interval   Refresh interval in seconds
     * @param int      $topN       Number of top keys per tag
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::scheduleCrossTagPriority(['products', 'featured'], fn($key) => compute($key), 3600, 5);
     * ```
     */
    public static function scheduleCrossTagPriority(array $tags, callable $callback, int $interval, int $topN = 5): void
    {
        foreach ($tags as $tag) {
            $keys = self::getTaggedKeys($tag);

            // Sort by access count
            usort($keys, fn($a, $b) => (self::getVersionedKeyMetadata($b)['access_count'] ?? 0) <=> (self::getVersionedKeyMetadata($a)['access_count'] ?? 0));

            $topKeys = array_slice($keys, 0, $topN);
            foreach ($topKeys as $key) {
                $versions = self::countVersions($key);
                for ($v = 1; $v <= $versions; $v++) {
                    $versionedKey = "{$key}_v{$v}";
                    self::schedulePersistent($versionedKey, $callback, $interval);
                }
            }
        }
    }

    /**
     * Compute combined TTL and access weight for each key to prioritize recompute.
     *
     * @param array $keys Array of versioned cache keys
     *
     * @return array ['key' => weight] Higher weight means higher recompute priority
     *
     * @example
     * ```php
     * $weights = Cache::computeTTLAccessWeight(['key_v1', 'key_v2']);
     * ```
     */
    public static function computeTTLAccessWeight(array $keys): array
    {
        $weights = [];
        foreach ($keys as $key) {
            $ttl = self::timeToLive($key) ?: 0;
            $meta = self::getVersionedKeyMetadata($key);
            $access = $meta['access_count'] ?? 0;
            $weights[$key] = $access / max(1, $ttl); // higher access and lower TTL = higher weight
        }
        return $weights;
    }

    /**
     * Recompute top weighted keys based on TTL and access.
     *
     * @param array    $keys       Array of versioned keys
     * @param int      $topN       Number of top keys to recompute
     * @param callable $callback   Function to recompute key
     * @param int      $expiration Cache lifetime in seconds (default 1 hour)
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::recomputeTopWeightedKeys(['key_v1', 'key_v2'], 3, fn($key) => compute($key), 3600);
     * ```
     */
    public static function recomputeTopWeightedKeys(array $keys, int $topN, callable $callback, int $expiration = 3600): void
    {
        $weights = self::computeTTLAccessWeight($keys);
        arsort($weights);
        $topKeys = array_slice(array_keys($weights), 0, $topN);
        foreach ($topKeys as $key) {
            set_transient($key, $callback($key), $expiration);
        }
    }

    /**
     * Predictively adjust TTL for versioned keys based on historical access patterns.
     *
     * @param string $key Versioned cache key
     * @param int    $baseTTL Base TTL in seconds
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::predictiveTTLAdjust(Cache::makeKey('config') . '_v1', 3600);
     * ```
     */
    public static function predictiveTTLAdjust(string $key, int $baseTTL): void
    {
        $meta = self::getVersionedKeyMetadata($key) ?: [];
        $accessHistory = $meta['access_history'] ?? [];
        $factor = 1 + (count($accessHistory) / 100); // increase TTL by access frequency
        $newTTL = intval($baseTTL * $factor);

        $value = get_transient($key);
        if ($value !== false) {
            set_transient($key, $value, $newTTL);
        }
    }

    /**
     * Cleanup versioned keys with predicted low value based on TTL and access weight.
     *
     * @param array $keys Array of versioned keys
     * @param float $weightThreshold Threshold below which keys are deleted
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::cleanupLowValueKeys(['key_v1', 'key_v2'], 0.05);
     * ```
     */
    public static function cleanupLowValueKeys(array $keys, float $weightThreshold): void
    {
        $weights = self::computeTTLAccessWeight($keys);
        foreach ($weights as $key => $weight) {
            if ($weight < $weightThreshold) {
                self::delete($key);
            }
        }
    }

    /**
     * Schedule analytics-driven refresh for keys with high predicted usage.
     *
     * @param array    $keys      Versioned cache keys
     * @param callable $callback  Function to recompute key
     * @param int      $interval  Refresh interval in seconds
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::scheduleHighPredictedUsageRefresh(['key_v1', 'key_v2'], fn($key) => compute($key), 3600);
     * ```
     */
    public static function scheduleHighPredictedUsageRefresh(array $keys, callable $callback, int $interval): void
    {
        foreach ($keys as $key) {
            $meta = self::getVersionedKeyMetadata($key);
            $accessHistory = $meta['access_history'] ?? [];
            if (count($accessHistory) > 50) { // threshold for high predicted usage
                self::schedulePersistent($key, $callback, $interval);
            }
        }
    }

    /**
     * Compute hierarchical decay score for each parent based on children TTL and access.
     *
     * @param array $parentKeys Array of parent cache keys
     *
     * @return array ['parentKey' => decayScore]
     *
     * @example
     * ```php
     * $decayScores = Cache::computeHierarchicalDecay([Cache::makeKey('parent1')]);
     * ```
     */
    public static function computeHierarchicalDecay(array $parentKeys): array
    {
        $decay = [];
        foreach ($parentKeys as $parentKey) {
            $children = get_transient('children_' . $parentKey) ?: [];
            $score = 0;
            foreach ($children as $childKey) {
                $ttl = self::timeToLive($childKey) ?: 0;
                $meta = self::getVersionedKeyMetadata($childKey);
                $access = $meta['access_count'] ?? 0;
                $score += max(0, 1 - ($ttl / max(1, $access))); // decay increases if TTL low relative to access
            }
            $decay[$parentKey] = $score;
        }
        return $decay;
    }

    /**
     * Cleanup low-priority hierarchical keys based on decay score threshold.
     *
     * @param array $parentKeys Array of parent cache keys
     * @param float $threshold Decay score threshold
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::cleanupLowPriorityHierarchy([Cache::makeKey('parent1')], 0.5);
     * ```
     */
    public static function cleanupLowPriorityHierarchy(array $parentKeys, float $threshold): void
    {
        $decayScores = self::computeHierarchicalDecay($parentKeys);
        foreach ($decayScores as $parentKey => $score) {
            if ($score > $threshold) {
                $children = get_transient('children_' . $parentKey) ?: [];
                foreach ($children as $childKey) {
                    self::delete($childKey);
                }
            }
        }
    }

    /**
     * Schedule adaptive refresh for hierarchical keys based on decay and TTL.
     *
     * @param array    $parentKeys Array of parent cache keys
     * @param callable $callback   Function to recompute key
     * @param int      $interval   Refresh interval in seconds
     * @param float    $decayThreshold Decay score threshold
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::scheduleAdaptiveHierarchyRefresh([Cache::makeKey('parent1')], fn($key) => compute($key), 3600, 0.5);
     * ```
     */
    public static function scheduleAdaptiveHierarchyRefresh(array $parentKeys, callable $callback, int $interval, float $decayThreshold): void
    {
        $decayScores = self::computeHierarchicalDecay($parentKeys);
        foreach ($decayScores as $parentKey => $score) {
            if ($score > $decayThreshold) {
                $children = get_transient('children_' . $parentKey) ?: [];
                foreach ($children as $childKey) {
                    self::schedulePersistent($childKey, $callback, $interval);
                }
            }
        }
    }

    /**
     * Scale TTL across all keys of a specific tag based on average access frequency.
     *
     * @param string $tag       Tag name
     * @param int    $baseTTL   Base TTL in seconds
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::scaleTagTTL('products', 3600);
     * ```
     */
    public static function scaleTagTTL(string $tag, int $baseTTL): void
    {
        $keys = self::getTaggedKeys($tag);
        foreach ($keys as $key) {
            $meta = self::getVersionedKeyMetadata($key) ?: [];
            $access = $meta['access_count'] ?? 0;
            $factor = 1 + ($access / 100); // increase TTL proportionally to access
            $value = get_transient($key);
            if ($value !== false) {
                set_transient($key, $value, intval($baseTTL * $factor));
            }
        }
    }

    /**
     * Promote keys adaptively based on combined TTL, access, and tag analytics.
     *
     * @param array $tags Array of tag names
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::promoteAnalyticsDriven(['products', 'featured']);
     * ```
     */
    public static function promoteAnalyticsDriven(array $tags): void
    {
        foreach ($tags as $tag) {
            $keys = self::getTaggedKeys($tag);
            $weights = self::computeTTLAccessWeight($keys);
            arsort($weights);
            if (!empty($weights)) {
                $topKey = array_key_first($weights);
                self::promoteMostAccessedVersion($topKey);
            }
        }
    }

    /**
     * Clear hierarchical keys with low TTL or low access frequency for cleanup optimization.
     *
     * @param array $parentKeys Array of parent cache keys
     * @param int   $ttlThreshold TTL threshold
     * @param int   $accessThreshold Access count threshold
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::clearLowTTLLowAccess([Cache::makeKey('parent1')], 300, 10);
     * ```
     */
    public static function clearLowTTLLowAccess(array $parentKeys, int $ttlThreshold, int $accessThreshold): void
    {
        foreach ($parentKeys as $parentKey) {
            $children = get_transient('children_' . $parentKey) ?: [];
            foreach ($children as $childKey) {
                $ttl = self::timeToLive($childKey) ?: 0;
                $meta = self::getVersionedKeyMetadata($childKey);
                $access = $meta['access_count'] ?? 0;
                if ($ttl < $ttlThreshold || $access < $accessThreshold) {
                    self::delete($childKey);
                }
            }
        }
    }

    /**
     * Retrieve cross-tag TTL statistics including min, max, and average for monitoring.
     *
     * @param array $tags Array of tag names
     *
     * @return array ['tag' => ['min' => int, 'max' => int, 'average' => float]]
     *
     * @example
     * ```php
     * $tagStats = Cache::crossTagTTLStats(['products', 'featured']);
     * ```
     */
    public static function crossTagTTLStats(array $tags): array
    {
        $result = [];
        foreach ($tags as $tag) {
            $keys = self::getTaggedKeys($tag);
            $ttls = array_map(fn($key) => self::timeToLive($key) ?: 0, $keys);
            if (!empty($ttls)) {
                $result[$tag] = [
                    'min' => min($ttls),
                    'max' => max($ttls),
                    'average' => array_sum($ttls) / count($ttls)
                ];
            } else {
                $result[$tag] = ['min' => 0, 'max' => 0, 'average' => 0];
            }
        }
        return $result;
    }

    /**
     * Predictively scale hierarchical TTL based on historical decay and access.
     *
     * @param array $parentKeys Array of parent cache keys
     * @param int   $baseTTL    Base TTL in seconds
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::predictiveHierarchyTTLScale([Cache::makeKey('parent1')], 3600);
     * ```
     */
    public static function predictiveHierarchyTTLScale(array $parentKeys, int $baseTTL): void
    {
        foreach ($parentKeys as $parentKey) {
            $children = get_transient('children_' . $parentKey) ?: [];
            foreach ($children as $childKey) {
                $meta = self::getVersionedKeyMetadata($childKey) ?: [];
                $access = $meta['access_count'] ?? 0;
                $decay = self::computeHierarchicalDecay([$parentKey])[$parentKey] ?? 1;
                $scaledTTL = intval($baseTTL * (1 + $access / 100) / max(1, $decay));
                $value = get_transient($childKey);
                if ($value !== false) {
                    set_transient($childKey, $value, $scaledTTL);
                }
            }
        }
    }

    /**
     * Recompute top-priority keys per tag based on combined TTL and access weight.
     *
     * @param array    $tags       Array of tag names
     * @param int      $topN       Number of top keys per tag to recompute
     * @param callable $callback   Function to recompute key
     * @param int      $expiration Cache lifetime in seconds (default 1 hour)
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::recomputeTopPriorityPerTag(['products', 'featured'], 5, fn($key) => compute($key), 3600);
     * ```
     */
    public static function recomputeTopPriorityPerTag(array $tags, int $topN, callable $callback, int $expiration = 3600): void
    {
        foreach ($tags as $tag) {
            $keys = self::getTaggedKeys($tag);
            $weights = self::computeTTLAccessWeight($keys);
            arsort($weights);
            $topKeys = array_slice(array_keys($weights), 0, $topN);
            foreach ($topKeys as $key) {
                set_transient($key, $callback($key), $expiration);
            }
        }
    }

    /**
     * Schedule refresh for top-weighted keys across all tags.
     *
     * @param array    $tags      Array of tag names
     * @param callable $callback  Function to recompute key
     * @param int      $interval  Refresh interval in seconds
     * @param int      $topN      Number of top-weighted keys per tag
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::scheduleTopWeightedRefresh(['products', 'featured'], fn($key) => compute($key), 3600, 3);
     * ```
     */
    public static function scheduleTopWeightedRefresh(array $tags, callable $callback, int $interval, int $topN = 3): void
    {
        foreach ($tags as $tag) {
            $keys = self::getTaggedKeys($tag);
            $weights = self::computeTTLAccessWeight($keys);
            arsort($weights);
            $topKeys = array_slice(array_keys($weights), 0, $topN);
            foreach ($topKeys as $key) {
                self::schedulePersistent($key, $callback, $interval);
            }
        }
    }

    /**
     * Compute tag-priority list based on aggregated TTL and access analytics.
     *
     * @param array $tags Array of tag names
     *
     * @return array ['tag' => priorityScore] Higher score = higher priority
     *
     * @example
     * ```php
     * $priority = Cache::computeTagPriority(['products', 'featured']);
     * ```
     */
    public static function computeTagPriority(array $tags): array
    {
        $priority = [];
        foreach ($tags as $tag) {
            $analytics = self::tagAnalytics([$tag])[$tag] ?? ['avgTTL' => 0, 'totalAccess' => 0];
            $priority[$tag] = $analytics['totalAccess'] / max(1, $analytics['avgTTL']); // higher access + lower TTL = higher priority
        }
        arsort($priority);
        return $priority;
    }

    /**
     * Schedule recompute based on tag priority for high-impact tags.
     *
     * @param array    $tags      Array of tag names
     * @param callable $callback  Function to recompute keys
     * @param int      $interval  Refresh interval in seconds
     * @param int      $topTags   Number of top-priority tags to process
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::scheduleTagPriorityRecompute(['products', 'featured'], fn($key) => compute($key), 3600, 2);
     * ```
     */
    public static function scheduleTagPriorityRecompute(array $tags, callable $callback, int $interval, int $topTags = 2): void
    {
        $priority = self::computeTagPriority($tags);
        $topPriorityTags = array_slice(array_keys($priority), 0, $topTags);

        foreach ($topPriorityTags as $tag) {
            $keys = self::getTaggedKeys($tag);
            foreach ($keys as $key) {
                self::schedulePersistent($key, $callback, $interval);
            }
        }
    }

    /**
     * Schedule predictive refresh for hierarchical keys based on TTL forecast and access patterns.
     *
     * @param array    $parentKeys Array of parent cache keys
     * @param callable $callback   Function to recompute key
     * @param int      $interval   Refresh interval in seconds
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::schedulePredictiveHierarchyRefresh([Cache::makeKey('parent1')], fn($key) => compute($key), 3600);
     * ```
     */
    public static function schedulePredictiveHierarchyRefresh(array $parentKeys, callable $callback, int $interval): void
    {
        foreach ($parentKeys as $parentKey) {
            $children = get_transient('children_' . $parentKey) ?: [];
            foreach ($children as $childKey) {
                $forecastTTL = self::forecastTTLDecay([$childKey])[$childKey] ?? 0;
                if ($forecastTTL < $interval) {
                    self::schedulePersistent($childKey, $callback, $interval);
                }
            }
        }
    }

    /**
     * Cleanup keys across multiple tags that are predicted to have low impact (low TTL or low access).
     *
     * @param array $tags Array of tag names
     * @param float $weightThreshold Threshold below which keys are deleted
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::cleanupLowImpactTags(['products', 'featured'], 0.05);
     * ```
     */
    public static function cleanupLowImpactTags(array $tags, float $weightThreshold): void
    {
        foreach ($tags as $tag) {
            $keys = self::getTaggedKeys($tag);
            self::cleanupLowValueKeys($keys, $weightThreshold);
        }
    }

    /**
     * Scale TTL adaptively for hierarchical keys across multiple parent levels.
     *
     * @param array $parentKeys Array of parent cache keys
     * @param int   $baseTTL    Base TTL in seconds
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::adaptiveHierarchyTTLScale([Cache::makeKey('parent1')], 3600);
     * ```
     */
    public static function adaptiveHierarchyTTLScale(array $parentKeys, int $baseTTL): void
    {
        foreach ($parentKeys as $parentKey) {
            $children = get_transient('children_' . $parentKey) ?: [];
            foreach ($children as $childKey) {
                $meta = self::getVersionedKeyMetadata($childKey) ?: [];
                $access = $meta['access_count'] ?? 0;
                $factor = 1 + ($access / 100);
                $value = get_transient($childKey);
                if ($value !== false) {
                    set_transient($childKey, $value, intval($baseTTL * $factor));
                }
            }
        }
    }

    /**
     * Compute combined TTL and access analytics across multiple tags for prioritization.
     *
     * @param array $tags Array of tag names
     *
     * @return array ['tag' => ['avgTTL' => float, 'totalAccess' => int, 'priorityScore' => float]]
     *
     * @example
     * ```php
     * $analytics = Cache::crossTagAnalytics(['products', 'featured']);
     * ```
     */
    public static function crossTagAnalytics(array $tags): array
    {
        $result = [];
        foreach ($tags as $tag) {
            $analytics = self::tagAnalytics([$tag])[$tag] ?? ['avgTTL' => 0, 'totalAccess' => 0];
            $priorityScore = $analytics['totalAccess'] / max(1, $analytics['avgTTL']);
            $result[$tag] = array_merge($analytics, ['priorityScore' => $priorityScore]);
        }
        return $result;
    }

    /**
     * Schedule recompute for keys in top-priority tags based on cross-tag analytics.
     *
     * @param array    $tags      Array of tag names
     * @param callable $callback  Function to recompute key
     * @param int      $interval  Refresh interval in seconds
     * @param int      $topTags   Number of top-priority tags to process
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::scheduleTopAnalyticsTagsRecompute(['products', 'featured'], fn($key) => compute($key), 3600, 2);
     * ```
     */
    public static function scheduleTopAnalyticsTagsRecompute(array $tags, callable $callback, int $interval, int $topTags = 2): void
    {
        $analytics = self::crossTagAnalytics($tags);
        uasort($analytics, fn($a, $b) => $b['priorityScore'] <=> $a['priorityScore']);
        $topAnalyticsTags = array_slice(array_keys($analytics), 0, $topTags);

        foreach ($topAnalyticsTags as $tag) {
            $keys = self::getTaggedKeys($tag);
            foreach ($keys as $key) {
                self::schedulePersistent($key, $callback, $interval);
            }
        }
    }

    /**
     * Cleanup versioned keys predicted to expire soon based on TTL forecast and access patterns.
     *
     * @param array $keys Array of versioned cache keys
     * @param int   $threshold TTL threshold in seconds
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::cleanupForecastedExpiries(['key_v1', 'key_v2'], 300);
     * ```
     */
    public static function cleanupForecastedExpiries(array $keys, int $threshold): void
    {
        $forecast = self::forecastTTLDecay($keys);
        foreach ($forecast as $key => $ttl) {
            if ($ttl <= $threshold) {
                self::delete($key);
            }
        }
    }

    /**
     * Schedule recompute for multiple tags based on top combined TTL-access weights.
     *
     * @param array    $tags      Array of tag names
     * @param callable $callback  Function to recompute key
     * @param int      $interval  Refresh interval in seconds
     * @param int      $topN      Number of top-weighted keys per tag
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::scheduleMultiTagTopWeighted(['products', 'featured'], fn($key) => compute($key), 3600, 3);
     * ```
     */
    public static function scheduleMultiTagTopWeighted(array $tags, callable $callback, int $interval, int $topN = 3): void
    {
        foreach ($tags as $tag) {
            $keys = self::getTaggedKeys($tag);
            $weights = self::computeTTLAccessWeight($keys);
            arsort($weights);
            $topKeys = array_slice(array_keys($weights), 0, $topN);
            foreach ($topKeys as $key) {
                self::schedulePersistent($key, $callback, $interval);
            }
        }
    }

    /**
     * Promote hierarchical keys adaptively based on access frequency and TTL decay.
     *
     * @param array $parentKeys Array of parent cache keys
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::promoteAdaptiveHierarchy([Cache::makeKey('parent1')]);
     * ```
     */
    public static function promoteAdaptiveHierarchy(array $parentKeys): void
    {
        foreach ($parentKeys as $parentKey) {
            $children = get_transient('children_' . $parentKey) ?: [];
            foreach ($children as $childKey) {
                $meta = self::getVersionedKeyMetadata($childKey) ?: [];
                $ttl = self::timeToLive($childKey) ?: 0;
                $access = $meta['access_count'] ?? 0;
                if ($access > 50 && $ttl < 3600) { // arbitrary threshold
                    self::promoteVersion(explode('_v', $childKey)[0], (int)substr(strrchr($childKey, '_v'), 2));
                }
            }
        }
    }

    /**
     * Compute hierarchical key health score combining TTL, access, and decay.
     *
     * @param array $parentKeys Array of parent cache keys
     *
     * @return array ['parentKey' => healthScore] Higher score = healthier key
     *
     * @example
     * ```php
     * $health = Cache::computeHierarchicalHealth([Cache::makeKey('parent1')]);
     * ```
     */
    public static function computeHierarchicalHealth(array $parentKeys): array
    {
        $health = [];
        foreach ($parentKeys as $parentKey) {
            $children = get_transient('children_' . $parentKey) ?: [];
            $score = 0;
            foreach ($children as $childKey) {
                $ttl = self::timeToLive($childKey) ?: 0;
                $meta = self::getVersionedKeyMetadata($childKey) ?: [];
                $access = $meta['access_count'] ?? 0;
                $decay = self::computeHierarchicalDecay([$parentKey])[$parentKey] ?? 1;
                $score += ($ttl * ($access + 1)) / max(1, $decay);
            }
            $health[$parentKey] = $score;
        }
        return $health;
    }

    /**
     * Cleanup hierarchical keys with lowest health score below a threshold.
     *
     * @param array $parentKeys Array of parent cache keys
     * @param float $threshold  Health score threshold
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::cleanupLowHealthHierarchy([Cache::makeKey('parent1')], 1000);
     * ```
     */
    public static function cleanupLowHealthHierarchy(array $parentKeys, float $threshold): void
    {
        $healthScores = self::computeHierarchicalHealth($parentKeys);
        foreach ($healthScores as $parentKey => $score) {
            if ($score < $threshold) {
                $children = get_transient('children_' . $parentKey) ?: [];
                foreach ($children as $childKey) {
                    self::delete($childKey);
                }
            }
        }
    }

    /**
     * Predictively scale TTL across multiple hierarchical levels based on historical decay and access.
     *
     * @param array $parentKeys Array of parent cache keys
     * @param int   $baseTTL    Base TTL in seconds
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::predictiveMultiLevelTTLScale([Cache::makeKey('parent1')], 3600);
     * ```
     */
    public static function predictiveMultiLevelTTLScale(array $parentKeys, int $baseTTL): void
    {
        foreach ($parentKeys as $parentKey) {
            $children = get_transient('children_' . $parentKey) ?: [];
            foreach ($children as $childKey) {
                $meta = self::getVersionedKeyMetadata($childKey) ?: [];
                $access = $meta['access_count'] ?? 0;
                $decay = self::computeHierarchicalDecay([$parentKey])[$parentKey] ?? 1;
                $scaledTTL = intval($baseTTL * (1 + $access / 100) / max(1, $decay));
                $value = get_transient($childKey);
                if ($value !== false) {
                    set_transient($childKey, $value, $scaledTTL);
                }
            }
        }
    }

    /**
     * Promote top keys across multiple tags based on combined TTL and access weight.
     *
     * @param array    $tags      Array of tag names
     * @param int      $topN      Number of top keys per tag to promote
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::promoteTopKeysAcrossTags(['products', 'featured'], 3);
     * ```
     */
    public static function promoteTopKeysAcrossTags(array $tags, int $topN): void
    {
        foreach ($tags as $tag) {
            $keys = self::getTaggedKeys($tag);
            $weights = self::computeTTLAccessWeight($keys);
            arsort($weights);
            $topKeys = array_slice(array_keys($weights), 0, $topN);
            foreach ($topKeys as $key) {
                self::promoteMostAccessedVersion($key);
            }
        }
    }

    /**
     * Schedule adaptive refresh for hierarchical keys based on TTL forecast, decay, and access.
     *
     * @param array    $parentKeys Array of parent cache keys
     * @param callable $callback   Function to recompute key
     * @param int      $interval   Refresh interval in seconds
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::scheduleAdaptiveHierarchyRefreshAll([Cache::makeKey('parent1')], fn($key) => compute($key), 3600);
     * ```
     */
    public static function scheduleAdaptiveHierarchyRefreshAll(array $parentKeys, callable $callback, int $interval): void
    {
        foreach ($parentKeys as $parentKey) {
            $children = get_transient('children_' . $parentKey) ?: [];
            foreach ($children as $childKey) {
                $forecastTTL = self::forecastTTLDecay([$childKey])[$childKey] ?? 0;
                $decay = self::computeHierarchicalDecay([$parentKey])[$parentKey] ?? 1;
                $meta = self::getVersionedKeyMetadata($childKey) ?: [];
                $access = $meta['access_count'] ?? 0;

                // Schedule if low TTL or high decay relative to access
                if ($forecastTTL < $interval || $decay / max(1, $access) > 1) {
                    self::schedulePersistent($childKey, $callback, $interval);
                }
            }
        }
    }

    /**
     * Compute cross-tag priority for scheduling recompute based on analytics-driven score.
     *
     * @param array $tags Array of tag names
     *
     * @return array ['tag' => priorityScore] Higher = higher recompute priority
     *
     * @example
     * ```php
     * $priority = Cache::computeCrossTagPriority(['products', 'featured']);
     * ```
     */
    public static function computeCrossTagPriority(array $tags): array
    {
        $priority = [];
        foreach ($tags as $tag) {
            $analytics = self::crossTagAnalytics([$tag])[$tag] ?? ['avgTTL' => 0, 'totalAccess' => 0, 'priorityScore' => 0];
            $priority[$tag] = $analytics['priorityScore'];
        }
        arsort($priority);
        return $priority;
    }

    /**
     * Schedule recompute for top cross-tag priority keys based on computed analytics.
     *
     * @param array    $tags      Array of tag names
     * @param callable $callback  Function to recompute key
     * @param int      $interval  Refresh interval in seconds
     * @param int      $topTags   Number of top-priority tags to process
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::scheduleTopCrossTagRecompute(['products', 'featured'], fn($key) => compute($key), 3600, 2);
     * ```
     */
    public static function scheduleTopCrossTagRecompute(array $tags, callable $callback, int $interval, int $topTags = 2): void
    {
        $priority = self::computeCrossTagPriority($tags);
        $topPriorityTags = array_slice(array_keys($priority), 0, $topTags);

        foreach ($topPriorityTags as $tag) {
            $keys = self::getTaggedKeys($tag);
            foreach ($keys as $key) {
                self::schedulePersistent($key, $callback, $interval);
            }
        }
    }

    /**
     * Cleanup keys predicted to expire soon across multiple hierarchical levels.
     *
     * @param array $parentKeys Array of parent cache keys
     * @param int   $threshold  TTL threshold in seconds
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::cleanupPredictiveHierarchyExpiries([Cache::makeKey('parent1')], 300);
     * ```
     */
    public static function cleanupPredictiveHierarchyExpiries(array $parentKeys, int $threshold): void
    {
        foreach ($parentKeys as $parentKey) {
            $children = get_transient('children_' . $parentKey) ?: [];
            $forecast = self::forecastTTLDecay($children);
            foreach ($forecast as $childKey => $ttl) {
                if ($ttl <= $threshold) {
                    self::delete($childKey);
                }
            }
        }
    }

    /**
     * Promote top keys adaptively per tag based on combined TTL-access weight and historical decay.
     *
     * @param array $tags Array of tag names
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::promoteTopWeightedPerTag(['products', 'featured']);
     * ```
     */
    public static function promoteTopWeightedPerTag(array $tags): void
    {
        foreach ($tags as $tag) {
            $keys = self::getTaggedKeys($tag);
            $weights = self::computeTTLAccessWeight($keys);
            arsort($weights);
            if (!empty($weights)) {
                $topKey = array_key_first($weights);
                self::promoteMostAccessedVersion($topKey);
            }
        }
    }

    /**
     * Schedule adaptive recompute for keys based on weighted TTL and access metrics.
     *
     * @param array    $keys      Array of versioned cache keys
     * @param callable $callback  Function to recompute key
     * @param int      $interval  Refresh interval in seconds
     * @param float    $weightThreshold Threshold weight to trigger recompute
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::scheduleWeightedTTLAccessRecompute(['key_v1', 'key_v2'], fn($key) => compute($key), 3600, 0.05);
     * ```
     */
    public static function scheduleWeightedTTLAccessRecompute(array $keys, callable $callback, int $interval, float $weightThreshold): void
    {
        $weights = self::computeTTLAccessWeight($keys);
        foreach ($weights as $key => $weight) {
            if ($weight >= $weightThreshold) {
                self::schedulePersistent($key, $callback, $interval);
            }
        }
    }

    /**
     * Compute cross-tag weighted metrics for TTL and access frequency for monitoring.
     *
     * @param array $tags Array of tag names
     *
     * @return array ['tag' => ['weightedScore' => float, 'avgTTL' => float, 'totalAccess' => int]]
     *
     * @example
     * ```php
     * $metrics = Cache::computeCrossTagWeightedMetrics(['products', 'featured']);
     * ```
     */
    public static function computeCrossTagWeightedMetrics(array $tags): array
    {
        $result = [];
        foreach ($tags as $tag) {
            $keys = self::getTaggedKeys($tag);
            $weights = self::computeTTLAccessWeight($keys);
            $totalWeight = array_sum($weights);
            $analytics = self::tagAnalytics([$tag])[$tag] ?? ['avgTTL' => 0, 'totalAccess' => 0];
            $result[$tag] = array_merge($analytics, ['weightedScore' => $totalWeight]);
        }
        return $result;
    }

    /**
     * Schedule recompute for top keys across tags based on cross-tag weighted metrics.
     *
     * @param array    $tags      Array of tag names
     * @param callable $callback  Function to recompute key
     * @param int      $interval  Refresh interval in seconds
     * @param int      $topN      Number of top-weighted keys per tag
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::scheduleTopWeightedCrossTagRecompute(['products', 'featured'], fn($key) => compute($key), 3600, 3);
     * ```
     */
    public static function scheduleTopWeightedCrossTagRecompute(array $tags, callable $callback, int $interval, int $topN = 3): void
    {
        foreach ($tags as $tag) {
            self::computeCrossTagWeightedMetrics([$tag])[$tag] ?? [];
            $keys = self::getTaggedKeys($tag);
            $weights = self::computeTTLAccessWeight($keys);
            arsort($weights);
            $topKeys = array_slice(array_keys($weights), 0, $topN);
            foreach ($topKeys as $key) {
                self::schedulePersistent($key, $callback, $interval);
            }
        }
    }

    /**
     * Promote versioned keys predicted to have high TTL decay but high access frequency.
     *
     * @param array $keys Array of versioned cache keys
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::promotePredictiveDecayKeys(['key_v1', 'key_v2']);
     * ```
     */
    public static function promotePredictiveDecayKeys(array $keys): void
    {
        $forecast = self::forecastTTLDecay($keys);
        foreach ($forecast as $key => $ttl) {
            $meta = self::getVersionedKeyMetadata($key) ?: [];
            $access = $meta['access_count'] ?? 0;
            if ($ttl < 3600 && $access > 50) { // low TTL, high access
                self::promoteMostAccessedVersion($key);
            }
        }
    }

    /**
     * Schedule adaptive refresh for hierarchical keys based on decay, access, and TTL.
     *
     * @param array    $parentKeys Array of parent cache keys
     * @param callable $callback   Function to recompute key
     * @param int      $interval   Refresh interval in seconds
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::scheduleAdaptiveHierarchyRefreshPredictive([Cache::makeKey('parent1')], fn($key) => compute($key), 3600);
     * ```
     */
    public static function scheduleAdaptiveHierarchyRefreshPredictive(array $parentKeys, callable $callback, int $interval): void
    {
        foreach ($parentKeys as $parentKey) {
            $children = get_transient('children_' . $parentKey) ?: [];
            foreach ($children as $childKey) {
                $ttl = self::timeToLive($childKey) ?: 0;
                $meta = self::getVersionedKeyMetadata($childKey) ?: [];
                $access = $meta['access_count'] ?? 0;
                $decay = self::computeHierarchicalDecay([$parentKey])[$parentKey] ?? 1;
                if ($ttl < $interval || $decay / max(1, $access) > 1) {
                    self::schedulePersistent($childKey, $callback, $interval);
                }
            }
        }
    }

    /**
     * Cleanup low-priority keys across tags based on combined TTL-access weighted score.
     *
     * @param array $tags Array of tag names
     * @param float $threshold Threshold below which keys are deleted
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::cleanupLowPriorityKeys(['products', 'featured'], 0.05);
     * ```
     */
    public static function cleanupLowPriorityKeys(array $tags, float $threshold): void
    {
        foreach ($tags as $tag) {
            $keys = self::getTaggedKeys($tag);
            self::cleanupLowValueKeys($keys, $threshold);
        }
    }

    /**
     * Compute hierarchical predictive score for each parent key combining TTL, access, and decay.
     *
     * @param array $parentKeys Array of parent cache keys
     *
     * @return array ['parentKey' => predictiveScore]
     *
     * @example
     * ```php
     * $scores = Cache::computeHierarchicalPredictiveScore([Cache::makeKey('parent1')]);
     * ```
     */
    public static function computeHierarchicalPredictiveScore(array $parentKeys): array
    {
        $scores = [];
        foreach ($parentKeys as $parentKey) {
            $children = get_transient('children_' . $parentKey) ?: [];
            $score = 0;
            foreach ($children as $childKey) {
                $ttl = self::timeToLive($childKey) ?: 0;
                $meta = self::getVersionedKeyMetadata($childKey) ?: [];
                $access = $meta['access_count'] ?? 0;
                $decay = self::computeHierarchicalDecay([$parentKey])[$parentKey] ?? 1;
                $score += ($access + 1) / max(1, $ttl * $decay);
            }
            $scores[$parentKey] = $score;
        }
        return $scores;
    }

    /**
     * Cleanup hierarchical keys with predictive score below threshold.
     *
     * @param array $parentKeys Array of parent cache keys
     * @param float $threshold  Predictive score threshold
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::cleanupLowPredictiveHierarchy([Cache::makeKey('parent1')], 0.01);
     * ```
     */
    public static function cleanupLowPredictiveHierarchy(array $parentKeys, float $threshold): void
    {
        $scores = self::computeHierarchicalPredictiveScore($parentKeys);
        foreach ($scores as $parentKey => $score) {
            if ($score < $threshold) {
                $children = get_transient('children_' . $parentKey) ?: [];
                foreach ($children as $childKey) {
                    self::delete($childKey);
                }
            }
        }
    }

    /**
     * Schedule predictive recompute for top keys across multiple tags based on TTL and access metrics.
     *
     * @param array    $tags      Array of tag names
     * @param callable $callback  Function to recompute key
     * @param int      $interval  Refresh interval in seconds
     * @param int      $topN      Number of top keys per tag
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::schedulePredictiveCrossTagRecompute(['products', 'featured'], fn($key) => compute($key), 3600, 3);
     * ```
     */
    public static function schedulePredictiveCrossTagRecompute(array $tags, callable $callback, int $interval, int $topN = 3): void
    {
        foreach ($tags as $tag) {
            $keys = self::getTaggedKeys($tag);
            $weights = self::computeTTLAccessWeight($keys);
            arsort($weights);
            $topKeys = array_slice(array_keys($weights), 0, $topN);
            foreach ($topKeys as $key) {
                self::schedulePersistent($key, $callback, $interval);
            }
        }
    }

    /**
     * Cleanup multi-level hierarchical keys that are predicted to expire soon based on TTL forecast.
     *
     * @param array $parentKeys Array of parent cache keys
     * @param int   $threshold  TTL threshold in seconds
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::cleanupMultiLevelExpiring([Cache::makeKey('parent1')], 300);
     * ```
     */
    public static function cleanupMultiLevelExpiring(array $parentKeys, int $threshold): void
    {
        foreach ($parentKeys as $parentKey) {
            $children = get_transient('children_' . $parentKey) ?: [];
            $forecast = self::forecastTTLDecay($children);
            foreach ($forecast as $childKey => $ttl) {
                if ($ttl <= $threshold) {
                    self::delete($childKey);
                }
            }
        }
    }

    /**
     * Schedule analytics-driven refresh for hierarchical keys based on access frequency and decay score.
     *
     * @param array    $parentKeys Array of parent cache keys
     * @param callable $callback   Function to recompute key
     * @param int      $interval   Refresh interval in seconds
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::scheduleAnalyticsHierarchyRefresh([Cache::makeKey('parent1')], fn($key) => compute($key), 3600);
     * ```
     */
    public static function scheduleAnalyticsHierarchyRefresh(array $parentKeys, callable $callback, int $interval): void
    {
        $scores = self::computeHierarchicalHealth($parentKeys);
        foreach ($scores as $parentKey => $score) {
            if ($score < 5000) { // threshold for refresh
                $children = get_transient('children_' . $parentKey) ?: [];
                foreach ($children as $childKey) {
                    self::schedulePersistent($childKey, $callback, $interval);
                }
            }
        }
    }

    /**
     * Compute cross-tag predictive weight combining TTL, access, and decay analytics.
     *
     * @param array $tags Array of tag names
     *
     * @return array ['tag' => predictiveWeight] Higher weight = higher priority
     *
     * @example
     * ```php
     * $weights = Cache::computeCrossTagPredictiveWeight(['products', 'featured']);
     * ```
     */
    public static function computeCrossTagPredictiveWeight(array $tags): array
    {
        $weights = [];
        foreach ($tags as $tag) {
            $keys = self::getTaggedKeys($tag);
            $weights[$tag] = array_sum(self::computeTTLAccessWeight($keys));
        }
        arsort($weights);
        return $weights;
    }

    /**
     * Schedule top cross-tag predictive recompute based on computed weights.
     *
     * @param array    $tags      Array of tag names
     * @param callable $callback  Function to recompute key
     * @param int      $interval  Refresh interval in seconds
     * @param int      $topTags   Number of top tags to process
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::scheduleTopCrossTagPredictiveRecompute(['products', 'featured'], fn($key) => compute($key), 3600, 2);
     * ```
     */
    public static function scheduleTopCrossTagPredictiveRecompute(array $tags, callable $callback, int $interval, int $topTags = 2): void
    {
        $weights = self::computeCrossTagPredictiveWeight($tags);
        $topTagsKeys = array_slice(array_keys($weights), 0, $topTags);
        foreach ($topTagsKeys as $tag) {
            $keys = self::getTaggedKeys($tag);
            foreach ($keys as $key) {
                self::schedulePersistent($key, $callback, $interval);
            }
        }
    }

    /**
     * Promote hierarchical keys adaptively based on access frequency and decay score.
     *
     * @param array $parentKeys Array of parent cache keys
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::promoteAdaptiveHierarchyKeys([Cache::makeKey('parent1')]);
     * ```
     */
    public static function promoteAdaptiveHierarchyKeys(array $parentKeys): void
    {
        foreach ($parentKeys as $parentKey) {
            $children = get_transient('children_' . $parentKey) ?: [];
            foreach ($children as $childKey) {
                $meta = self::getVersionedKeyMetadata($childKey) ?: [];
                $access = $meta['access_count'] ?? 0;
                $decay = self::computeHierarchicalDecay([$parentKey])[$parentKey] ?? 1;

                // Promote if high access relative to decay
                if ($access / max(1, $decay) > 50) {
                    self::promoteMostAccessedVersion($childKey);
                }
            }
        }
    }

    /**
     * Cleanup keys across multiple tags with low TTL-access weighted score.
     *
     * @param array $tags Array of tag names
     * @param float $threshold Threshold below which keys are deleted
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::cleanupWeightedLowValueKeys(['products', 'featured'], 0.05);
     * ```
     */
    public static function cleanupWeightedLowValueKeys(array $tags, float $threshold): void
    {
        foreach ($tags as $tag) {
            $keys = self::getTaggedKeys($tag);
            self::cleanupLowValueKeys($keys, $threshold);
        }
    }

    /**
     * Schedule predictive refresh for hierarchical keys based on TTL-access weighted score.
     *
     * @param array    $parentKeys Array of parent cache keys
     * @param callable $callback   Function to recompute key
     * @param int      $interval   Refresh interval in seconds
     * @param float    $weightThreshold Threshold weight to trigger refresh
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::scheduleWeightedHierarchyRefresh([Cache::makeKey('parent1')], fn($key) => compute($key), 3600, 0.05);
     * ```
     */
    public static function scheduleWeightedHierarchyRefresh(array $parentKeys, callable $callback, int $interval, float $weightThreshold): void
    {
        foreach ($parentKeys as $parentKey) {
            $children = get_transient('children_' . $parentKey) ?: [];
            $weights = self::computeTTLAccessWeight($children);
            foreach ($weights as $childKey => $weight) {
                if ($weight >= $weightThreshold) {
                    self::schedulePersistent($childKey, $callback, $interval);
                }
            }
        }
    }

    /**
     * Compute predictive multi-tag weight combining TTL, access, and decay for monitoring.
     *
     * @param array $tags Array of tag names
     *
     * @return array ['tag' => predictiveWeight]
     *
     * @example
     * ```php
     * $weights = Cache::computeMultiTagPredictiveWeight(['products', 'featured']);
     * ```
     */
    public static function computeMultiTagPredictiveWeight(array $tags): array
    {
        $weights = [];
        foreach ($tags as $tag) {
            $keys = self::getTaggedKeys($tag);
            $weights[$tag] = array_sum(self::computeTTLAccessWeight($keys));
        }
        arsort($weights);
        return $weights;
    }

    /**
     * Schedule top multi-tag predictive refresh based on computed weighted scores.
     *
     * @param array    $tags      Array of tag names
     * @param callable $callback  Function to recompute key
     * @param int      $interval  Refresh interval in seconds
     * @param int      $topTags   Number of top tags to process
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::scheduleTopMultiTagPredictiveRefresh(['products', 'featured'], fn($key) => compute($key), 3600, 2);
     * ```
     */
    public static function scheduleTopMultiTagPredictiveRefresh(array $tags, callable $callback, int $interval, int $topTags = 2): void
    {
        $weights = self::computeMultiTagPredictiveWeight($tags);
        $topTagsKeys = array_slice(array_keys($weights), 0, $topTags);
        foreach ($topTagsKeys as $tag) {
            $keys = self::getTaggedKeys($tag);
            foreach ($keys as $key) {
                self::schedulePersistent($key, $callback, $interval);
            }
        }
    }

    /**
     * Promote keys predicted to have high TTL decay but high access frequency across hierarchy.
     *
     * @param array $keys Array of versioned cache keys
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::promoteHighDecayHighAccessKeys(['key_v1', 'key_v2']);
     * ```
     */
    public static function promoteHighDecayHighAccessKeys(array $keys): void
    {
        $forecast = self::forecastTTLDecay($keys);
        foreach ($forecast as $key => $ttl) {
            $meta = self::getVersionedKeyMetadata($key) ?: [];
            $access = $meta['access_count'] ?? 0;
            if ($ttl < 3600 && $access > 100) { // low TTL, high access threshold
                self::promoteMostAccessedVersion($key);
            }
        }
    }

    /**
     * Cleanup hierarchical keys with low combined TTL-access-decay score.
     *
     * @param array $parentKeys Array of parent cache keys
     * @param float $threshold  Threshold below which keys are deleted
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::cleanupWeightedLowHierarchy([Cache::makeKey('parent1')], 0.05);
     * ```
     */
    public static function cleanupWeightedLowHierarchy(array $parentKeys, float $threshold): void
    {
        foreach ($parentKeys as $parentKey) {
            $children = get_transient('children_' . $parentKey) ?: [];
            $weights = self::computeTTLAccessWeight($children);
            foreach ($weights as $childKey => $weight) {
                if ($weight < $threshold) {
                    self::delete($childKey);
                }
            }
        }
    }

    /**
     * Schedule analytics-driven refresh for top hierarchical keys based on decay and access metrics.
     *
     * @param array    $parentKeys Array of parent cache keys
     * @param callable $callback   Function to recompute key
     * @param int      $interval   Refresh interval in seconds
     * @param int      $topN       Number of top keys to refresh per parent
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::scheduleAnalyticsTopHierarchyRefresh([Cache::makeKey('parent1')], fn($key) => compute($key), 3600, 3);
     * ```
     */
    public static function scheduleAnalyticsTopHierarchyRefresh(array $parentKeys, callable $callback, int $interval, int $topN = 3): void
    {
        foreach ($parentKeys as $parentKey) {
            $children = get_transient('children_' . $parentKey) ?: [];
            $weights = self::computeTTLAccessWeight($children);
            arsort($weights);
            $topKeys = array_slice(array_keys($weights), 0, $topN);
            foreach ($topKeys as $key) {
                self::schedulePersistent($key, $callback, $interval);
            }
        }
    }

    /**
     * Compute cross-tag analytics combining TTL, access frequency, and decay metrics.
     *
     * @param array $tags Array of tag names
     *
     * @return array ['tag' => ['weightedScore' => float, 'avgTTL' => float, 'totalAccess' => int]]
     *
     * @example
     * ```php
     * $analytics = Cache::computeCrossTagAnalytics(['products', 'featured']);
     * ```
     */
    public static function computeCrossTagAnalytics(array $tags): array
    {
        $result = [];
        foreach ($tags as $tag) {
            $keys = self::getTaggedKeys($tag);
            $weights = self::computeTTLAccessWeight($keys);
            $totalWeight = array_sum($weights);
            $analytics = self::tagAnalytics([$tag])[$tag] ?? ['avgTTL' => 0, 'totalAccess' => 0];
            $result[$tag] = array_merge($analytics, ['weightedScore' => $totalWeight]);
        }
        return $result;
    }

    /**
     * Schedule top cross-tag analytics-driven recompute based on computed weighted scores.
     *
     * @param array    $tags      Array of tag names
     * @param callable $callback  Function to recompute key
     * @param int      $interval  Refresh interval in seconds
     * @param int      $topTags   Number of top tags to process
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::scheduleTopCrossTagAnalyticsRecompute(['products', 'featured'], fn($key) => compute($key), 3600, 2);
     * ```
     */
    public static function scheduleTopCrossTagAnalyticsRecompute(array $tags, callable $callback, int $interval, int $topTags = 2): void
    {
        $analytics = self::computeCrossTagAnalytics($tags);
        uasort($analytics, fn($a, $b) => $b['weightedScore'] <=> $a['weightedScore']);
        $topTagsKeys = array_slice(array_keys($analytics), 0, $topTags);
        foreach ($topTagsKeys as $tag) {
            $keys = self::getTaggedKeys($tag);
            foreach ($keys as $key) {
                self::schedulePersistent($key, $callback, $interval);
            }
        }
    }

    /**
     * Compute TTL decay over a given time window for hierarchical keys.
     *
     * @param array $keys Array of versioned cache keys
     * @param int   $windowSeconds Time window in seconds to analyze decay
     *
     * @return array ['key' => decayRate] Higher = faster decay
     *
     * @example
     * ```php
     * $decayRates = Cache::computeTimeWindowDecay(['key_v1', 'key_v2'], 3600);
     * ```
     */
    public static function computeTimeWindowDecay(array $keys, int $windowSeconds): array
    {
        $decayRates = [];
        foreach ($keys as $key) {
            $ttl = self::timeToLive($key) ?: 0;
            $decayRates[$key] = max(0, ($windowSeconds - $ttl) / max(1, $windowSeconds));
        }
        return $decayRates;
    }

    /**
     * Preload predictive keys into cache before they expire based on TTL-access forecast.
     *
     * @param array    $keys      Array of versioned cache keys
     * @param callable $callback  Function to recompute key
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::preloadPredictiveKeys(['key_v1', 'key_v2'], fn($key) => compute($key));
     * ```
     */
    public static function preloadPredictiveKeys(array $keys, callable $callback): void
    {
        foreach ($keys as $key) {
            $ttl = self::timeToLive($key) ?: 0;
            if ($ttl < 600) { // preload if TTL < 10 minutes
                $value = $callback($key);
                set_transient($key, $value, 3600);
            }
        }
    }

    /**
     * Promote auto-versioned key to latest version if access frequency is high.
     *
     * @param string $baseKey Base key without version suffix
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::promoteAutoVersionKey('products_top_selling');
     * ```
     */
    public static function promoteAutoVersionKey(string $baseKey): void
    {
        $versions = self::getAllVersions($baseKey);
        $topVersion = null;
        $highestAccess = 0;
        foreach ($versions as $version => $meta) {
            $access = $meta['access_count'] ?? 0;
            if ($access > $highestAccess) {
                $highestAccess = $access;
                $topVersion = $version;
            }
        }
        if ($topVersion) {
            self::promoteVersion($baseKey, $topVersion);
        }
    }

    /**
     * Schedule preloading of hierarchical keys based on predicted access and TTL.
     *
     * @param array    $parentKeys Array of parent cache keys
     * @param callable $callback   Function to recompute key
     * @param int      $thresholdTTL TTL threshold in seconds for preloading
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::schedulePredictiveHierarchyPreload([Cache::makeKey('parent1')], fn($key) => compute($key), 600);
     * ```
     */
    public static function schedulePredictiveHierarchyPreload(array $parentKeys, callable $callback, int $thresholdTTL = 600): void
    {
        foreach ($parentKeys as $parentKey) {
            $children = get_transient('children_' . $parentKey) ?: [];
            foreach ($children as $childKey) {
                $ttl = self::timeToLive($childKey) ?: 0;
                if ($ttl < $thresholdTTL) {
                    $value = $callback($childKey);
                    set_transient($childKey, $value, 3600);
                }
            }
        }
    }

    /**
     * Evict keys predicted to expire soon with low access frequency.
     *
     * @param array $keys Array of versioned cache keys
     * @param int   $ttlThreshold TTL threshold in seconds to consider eviction
     * @param int   $accessThreshold Access count below which keys are evicted
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::evictLowAccessPredictiveKeys(['key_v1', 'key_v2'], 600, 10);
     * ```
     */
    public static function evictLowAccessPredictiveKeys(array $keys, int $ttlThreshold = 600, int $accessThreshold = 10): void
    {
        foreach ($keys as $key) {
            $ttl = self::timeToLive($key) ?: 0;
            $meta = self::getVersionedKeyMetadata($key) ?: [];
            $access = $meta['access_count'] ?? 0;
            if ($ttl < $ttlThreshold && $access < $accessThreshold) {
                self::delete($key);
            }
        }
    }

    /**
     * Adjust TTL of keys adaptively based on analytics of access frequency and decay trends.
     *
     * @param array $keys Array of versioned cache keys
     * @param int   $baseTTL Base TTL in seconds
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::adjustTTLAnalyticsBased(['key_v1', 'key_v2'], 3600);
     * ```
     */
    public static function adjustTTLAnalyticsBased(array $keys, int $baseTTL = 3600): void
    {
        foreach ($keys as $key) {
            $meta = self::getVersionedKeyMetadata($key) ?: [];
            $access = $meta['access_count'] ?? 0;
            $decay = self::forecastTTLDecay([$key])[$key] ?? 1;
            $adjustedTTL = intval($baseTTL * (1 + $access / 100) / max(1, $decay));
            $value = get_transient($key);
            if ($value !== false) {
                set_transient($key, $value, $adjustedTTL);
            }
        }
    }

    /**
     * Preload top keys across multiple tags predicted to be accessed soon.
     *
     * @param array    $tags      Array of tag names
     * @param callable $callback  Function to recompute key
     * @param int      $topN      Number of top keys per tag to preload
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::preloadTopPredictiveKeysAcrossTags(['products', 'featured'], fn($key) => compute($key), 3);
     * ```
     */
    public static function preloadTopPredictiveKeysAcrossTags(array $tags, callable $callback, int $topN = 3): void
    {
        foreach ($tags as $tag) {
            $keys = self::getTaggedKeys($tag);
            $weights = self::computeTTLAccessWeight($keys);
            arsort($weights);
            $topKeys = array_slice(array_keys($weights), 0, $topN);
            foreach ($topKeys as $key) {
                $value = $callback($key);
                set_transient($key, $value, 3600);
            }
        }
    }

    /**
     * Compute predictive eviction score for keys based on TTL decay and access frequency.
     *
     * @param array $keys Array of versioned cache keys
     *
     * @return array ['key' => evictionScore] Higher = more likely to keep, lower = candidate for eviction
     *
     * @example
     * ```php
     * $scores = Cache::computePredictiveEvictionScore(['key_v1', 'key_v2']);
     * ```
     */
    public static function computePredictiveEvictionScore(array $keys): array
    {
        $scores = [];
        foreach ($keys as $key) {
            $ttl = self::timeToLive($key) ?: 0;
            $meta = self::getVersionedKeyMetadata($key) ?: [];
            $access = $meta['access_count'] ?? 0;
            $decay = self::forecastTTLDecay([$key])[$key] ?? 1;
            $scores[$key] = ($access + 1) / max(1, $ttl * $decay);
        }
        return $scores;
    }

    /**
     * Evict bottom keys across multiple tags based on predictive eviction score.
     *
     * @param array $tags Array of tag names
     * @param float $threshold Score below which keys are evicted
     *
     * @return void
     *
     * @example
     * ```php
     * Cache::evictBottomKeysPredictive(['products', 'featured'], 0.05);
     * ```
     */
    public static function evictBottomKeysPredictive(array $tags, float $threshold = 0.05): void
    {
        foreach ($tags as $tag) {
            $keys = self::getTaggedKeys($tag);
            $scores = self::computePredictiveEvictionScore($keys);
            foreach ($scores as $key => $score) {
                if ($score < $threshold) {
                    self::delete($key);
                }
            }
        }
    }
}
