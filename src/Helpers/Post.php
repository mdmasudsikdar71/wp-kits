<?php

namespace MDMasudSikdar\WpKits\Helpers;

use WP_Post;

/**
 * Class Post
 *
 * Advanced helper for WordPress posts.
 *
 * Features:
 * ✅ Safe get/set post meta with default fallback
 * ✅ Bulk meta operations
 * ✅ Query posts by type, status, taxonomy, or custom args
 * ✅ Retrieve single posts safely
 * ✅ Fully static and reusable across plugins
 *
 * Example usage:
 * ```php
 * use MDMasudSikdar\WpKits\Helpers\Post;
 *
 * $meta = Post::getMeta(123, 'my_key', 'default');
 * Post::setMeta(123, 'my_key', 'value');
 * Post::bulkSetMeta([123,124], 'key', 'value');
 * $posts = Post::getByType('post', 'publish');
 * $single = Post::get(123);
 * ```
 *
 * @package MDMasudSikdar\WpKits\Helpers
 */
class Post
{
    /**
     * Get post meta safely with default fallback.
     *
     * @param int $postId
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     *
     * @example
     * ```php
     * $value = Post::getMeta(123, 'my_key', 'default');
     * ```
     */
    public static function getMeta(int $postId, string $key, mixed $default = null): mixed
    {
        // Retrieve post meta
        $value = get_post_meta($postId, $key, true);

        // Return default if empty
        return $value !== '' ? $value : $default;
    }

    /**
     * Set post meta safely.
     *
     * @param int $postId
     * @param string $key
     * @param mixed $value
     *
     * @return void
     *
     * @example
     * ```php
     * Post::setMeta(123, 'my_key', 'value');
     * ```
     */
    public static function setMeta(int $postId, string $key, mixed $value): void
    {
        // Update post meta
        update_post_meta($postId, $key, $value);
    }

    /**
     * Bulk set post meta for multiple posts.
     *
     * @param int[] $postIds
     * @param string $key
     * @param mixed $value
     *
     * @return void
     *
     * @example
     * ```php
     * Post::bulkSetMeta([123,124], 'key', 'value');
     * ```
     */
    public static function bulkSetMeta(array $postIds, string $key, mixed $value): void
    {
        // Loop through each post
        foreach ($postIds as $postId) {
            self::setMeta($postId, $key, $value);
        }
    }

    /**
     * Get posts by type and status.
     *
     * @param string $type Post type
     * @param string|string[] $status Post status(es)
     * @param int $limit Number of posts to retrieve
     *
     * @return WP_Post[]
     *
     * @example
     * ```php
     * $posts = Post::getByType('post', 'publish', 10);
     * ```
     */
    public static function getByType(string $type, string|array $status = 'publish', int $limit = 10): array
    {
        return get_posts([
            'post_type'   => $type,
            'post_status' => $status,
            'numberposts' => $limit,
        ]);
    }

    /**
     * Get a single post safely by ID.
     *
     * @param int $postId
     *
     * @return WP_Post|null
     *
     * @example
     * ```php
     * $post = Post::get(123);
     * ```
     */
    public static function get(int $postId): ?WP_Post
    {
        // Retrieve WP_Post object
        $post = get_post($postId);

        // Return post if exists, else null
        return $post ?: null;
    }

    /**
     * Get posts by taxonomy term.
     *
     * @param string $taxonomy
     * @param string|int $term
     * @param string $type Post type
     * @param int $limit Number of posts
     *
     * @return WP_Post[]
     *
     * @example
     * ```php
     * $posts = Post::getByTerm('category', 'news', 'post', 5);
     * ```
     */
    public static function getByTerm(string $taxonomy, string|int $term, string $type = 'post', int $limit = 10): array
    {
        return get_posts([
            'post_type'      => $type,
            'posts_per_page' => $limit,
            'tax_query'      => [
                [
                    'taxonomy' => $taxonomy,
                    'field'    => is_int($term) ? 'term_id' : 'slug',
                    'terms'    => $term,
                ]
            ],
        ]);
    }

    /**
     * Delete post meta safely.
     *
     * @param int $postId
     * @param string $key
     *
     * @return void
     *
     * @example
     * ```php
     * Post::deleteMeta(123, 'my_key');
     * ```
     */
    public static function deleteMeta(int $postId, string $key): void
    {
        delete_post_meta($postId, $key);
    }

    /**
     * Increment a numeric post meta value safely.
     *
     * If the meta does not exist, it will be initialized to the default before incrementing.
     *
     * @param int $postId Post ID
     * @param string $key Meta key
     * @param int|float $amount Amount to increment (default 1)
     * @param int|float $default Default value if meta does not exist (default 0)
     *
     * @return int|float The new meta value after increment
     *
     * @example
     * ```php
     * $newCount = Post::incrementMeta(123, 'views', 1, 0); // Increments 'views' by 1
     * ```
     */
    public static function incrementMeta(int $postId, string $key, int|float $amount = 1, int|float $default = 0): int|float
    {
        // Get the current meta value
        $current = self::getMeta($postId, $key, $default);

        // Ensure it's numeric
        if (!is_numeric($current)) {
            $current = $default;
        }

        // Increment the value
        $newValue = $current + $amount;

        // Save the updated meta
        self::setMeta($postId, $key, $newValue);

        return $newValue;
    }

    /**
     * Decrement a numeric post meta value safely.
     *
     * If the meta does not exist, it will be initialized to the default before decrementing.
     *
     * @param int $postId Post ID
     * @param string $key Meta key
     * @param int|float $amount Amount to decrement (default 1)
     * @param int|float $default Default value if meta does not exist (default 0)
     *
     * @return int|float The new meta value after decrement
     *
     * @example
     * ```php
     * $newCount = Post::decrementMeta(123, 'views', 1, 10); // Decrements 'views' by 1, starting from 10 if missing
     * ```
     */
    public static function decrementMeta(int $postId, string $key, int|float $amount = 1, int|float $default = 0): int|float
    {
        // Get the current meta value
        $current = self::getMeta($postId, $key, $default);

        // Ensure it's numeric
        if (!is_numeric($current)) {
            $current = $default;
        }

        // Decrement the value
        $newValue = $current - $amount;

        // Save the updated meta
        self::setMeta($postId, $key, $newValue);

        return $newValue;
    }

    /**
     * Get a nested value from post meta using dot notation.
     *
     * If the meta or nested key does not exist, returns the default value.
     *
     * @param int $postId Post ID
     * @param string $key Meta key that stores an array
     * @param string $path Dot-notated path to the nested value (e.g., 'settings.display.color')
     * @param mixed $default Default value if the nested key does not exist
     *
     * @return mixed The nested value or default
     *
     * @example
     * ```php
     * $color = Post::getMetaNested(123, 'my_settings', 'display.color', 'blue');
     * ```
     */
    public static function getMetaNested(int $postId, string $key, string $path, mixed $default = null): mixed
    {
        // Retrieve the meta value as an array
        $array = self::getMeta($postId, $key, []);

        // Ensure it is an array
        if (!is_array($array)) {
            return $default;
        }

        // Split the path into segments
        $segments = explode('.', $path);

        // Traverse the array
        foreach ($segments as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }
            $array = $array[$segment];
        }

        // Return the found nested value
        return $array;
    }

    /**
     * Set a nested value in post meta using dot notation.
     *
     * Automatically creates missing nested arrays if necessary.
     *
     * @param int $postId Post ID
     * @param string $key Meta key that stores an array
     * @param string $path Dot-notated path to the nested value (e.g., 'settings.display.color')
     * @param mixed $value Value to set at the nested key
     *
     * @return void
     *
     * @example
     * ```php
     * Post::setMetaNested(123, 'my_settings', 'display.color', 'red');
     * ```
     */
    public static function setMetaNested(int $postId, string $key, string $path, mixed $value): void
    {
        // Retrieve the current meta value as an array
        $array = self::getMeta($postId, $key, []);

        // Ensure it is an array
        if (!is_array($array)) {
            $array = [];
        }

        // Split the path into segments
        $segments = explode('.', $path);

        // Reference to traverse the array
        $ref = &$array;

        // Traverse and create nested arrays if necessary
        foreach ($segments as $segment) {
            if (!isset($ref[$segment]) || !is_array($ref[$segment])) {
                $ref[$segment] = [];
            }
            $ref = &$ref[$segment];
        }

        // Set the final value
        $ref = $value;

        // Save the updated meta back to the post
        self::setMeta($postId, $key, $array);
    }

    /**
     * Delete a nested key from post meta using dot notation.
     *
     * If the nested key does not exist, nothing happens.
     *
     * @param int $postId Post ID
     * @param string $key Meta key that stores an array
     * @param string $path Dot-notated path to the nested key (e.g., 'settings.display.color')
     *
     * @return void
     *
     * @example
     * ```php
     * Post::deleteMetaNested(123, 'my_settings', 'display.color');
     * ```
     */
    public static function deleteMetaNested(int $postId, string $key, string $path): void
    {
        // Retrieve the current meta value as an array
        $array = self::getMeta($postId, $key, []);

        // Ensure it is an array
        if (!is_array($array)) {
            return;
        }

        // Split the path into segments
        $segments = explode('.', $path);

        // Reference to traverse the array
        $ref = &$array;

        // Traverse to the parent of the key to delete
        foreach ($segments as $i => $segment) {
            if (!isset($ref[$segment])) {
                return; // Key does not exist
            }

            // If last segment, unset the key
            if ($i === count($segments) - 1) {
                unset($ref[$segment]);
                break;
            }

            $ref = &$ref[$segment];
        }

        // Save the updated meta back to the post
        self::setMeta($postId, $key, $array);
    }

    /**
     * Check if a nested key exists in post meta using dot notation.
     *
     * @param int $postId Post ID
     * @param string $key Meta key that stores an array
     * @param string $path Dot-notated path to the nested key (e.g., 'settings.display.color')
     *
     * @return bool True if the nested key exists, false otherwise
     *
     * @example
     * ```php
     * if (Post::existsMetaNested(123, 'my_settings', 'display.color')) {
     *     // Nested key exists
     * }
     * ```
     */
    public static function existsMetaNested(int $postId, string $key, string $path): bool
    {
        // Retrieve the meta value as an array
        $array = self::getMeta($postId, $key, []);

        // Ensure it is an array
        if (!is_array($array)) {
            return false;
        }

        // Split the path into segments
        $segments = explode('.', $path);

        // Traverse the array
        foreach ($segments as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return false;
            }
            $array = $array[$segment];
        }

        // Nested key exists
        return true;
    }

    /**
     * Retrieve a nested value from post meta or set it to a default if it does not exist.
     *
     * Automatically creates missing nested paths and stores the default value.
     *
     * @param int $postId Post ID
     * @param string $key Meta key that stores an array
     * @param string $path Dot-notated path to the nested key (e.g., 'settings.display.color')
     * @param mixed $default Default value to set if the nested key does not exist
     *
     * @return mixed The existing or newly set value
     *
     * @example
     * ```php
     * $color = Post::rememberMetaNested(123, 'my_settings', 'display.color', 'blue');
     * ```
     */
    public static function rememberMetaNested(int $postId, string $key, string $path, mixed $default): mixed
    {
        // Check if the nested key exists
        if (self::existsMetaNested($postId, $key, $path)) {
            return self::getMetaNested($postId, $key, $path);
        }

        // Set the default value
        self::setMetaNested($postId, $key, $path, $default);

        // Return the default value
        return $default;
    }

    /**
     * Increment a numeric nested post meta value safely.
     *
     * Automatically creates missing nested arrays and initializes the value if it does not exist.
     *
     * @param int $postId Post ID
     * @param string $key Meta key that stores an array
     * @param string $path Dot-notated path to the nested numeric value (e.g., 'stats.views')
     * @param int|float $amount Amount to increment (default 1)
     * @param int|float $default Default value if the nested key does not exist (default 0)
     *
     * @return int|float The new nested value after increment
     *
     * @example
     * ```php
     * $newViews = Post::incrementMetaNested(123, 'stats', 'views', 1, 0);
     * ```
     */
    public static function incrementMetaNested(int $postId, string $key, string $path, int|float $amount = 1, int|float $default = 0): int|float
    {
        // Get current value, or default if missing
        $current = self::rememberMetaNested($postId, $key, $path, $default);

        // Ensure numeric
        if (!is_numeric($current)) {
            $current = $default;
        }

        // Increment value
        $newValue = $current + $amount;

        // Set updated value
        self::setMetaNested($postId, $key, $path, $newValue);

        return $newValue;
    }

    /**
     * Decrement a numeric nested post meta value safely.
     *
     * Automatically creates missing nested arrays and initializes the value if it does not exist.
     *
     * @param int $postId Post ID
     * @param string $key Meta key that stores an array
     * @param string $path Dot-notated path to the nested numeric value (e.g., 'stats.views')
     * @param int|float $amount Amount to decrement (default 1)
     * @param int|float $default Default value if the nested key does not exist (default 0)
     *
     * @return int|float The new nested value after decrement
     *
     * @example
     * ```php
     * $newViews = Post::decrementMetaNested(123, 'stats', 'views', 1, 10);
     * ```
     */
    public static function decrementMetaNested(int $postId, string $key, string $path, int|float $amount = 1, int|float $default = 0): int|float
    {
        // Get current value, or default if missing
        $current = self::rememberMetaNested($postId, $key, $path, $default);

        // Ensure numeric
        if (!is_numeric($current)) {
            $current = $default;
        }

        // Decrement value
        $newValue = $current - $amount;

        // Set updated value
        self::setMetaNested($postId, $key, $path, $newValue);

        return $newValue;
    }

    /**
     * Retrieve a meta value from the first post of a given taxonomy term or set a default if missing.
     *
     * Automatically queries posts by taxonomy term and returns the meta of the first post found.
     *
     * @param string $taxonomy Taxonomy name (e.g., 'category')
     * @param string|int $term Term slug or ID
     * @param string $metaKey Meta key to retrieve
     * @param mixed $default Default value to set if meta is missing
     * @param string $postType Post type to query (default 'post')
     *
     * @return mixed Meta value from the first matching post, or the default
     *
     * @example
     * ```php
     * $featured = Post::rememberMetaByTerm('category', 'news', 'featured', false, 'post');
     * ```
     */
    public static function rememberMetaByTerm(string $taxonomy, string|int $term, string $metaKey, mixed $default, string $postType = 'post'): mixed
    {
        // Query posts by taxonomy term, only need one
        $posts = self::getByTerm($taxonomy, $term, $postType, 1);

        // Return default if no posts found
        if (empty($posts)) {
            return $default;
        }

        // Get first post ID
        $postId = $posts[0]->ID;

        // Use rememberMetaNested for nested arrays, fallback to getMeta otherwise
        if (str_contains($metaKey, '.')) {
            return self::rememberMetaNested($postId, explode('.', $metaKey)[0], $metaKey, $default);
        }

        // If simple meta key
        return self::rememberMetaNested($postId, $metaKey, '', $default);
    }

    /**
     * Retrieve or set a meta value for multiple posts of a given post type.
     *
     * Automatically queries posts and sets default meta values if missing.
     *
     * @param string $postType Post type to query
     * @param string $metaKey Meta key to retrieve or set
     * @param mixed $default Default value to set if meta is missing
     * @param string|array $status Post status(es) to filter (default 'publish')
     * @param int $limit Number of posts to process (default 10)
     *
     * @return array Associative array of post ID => meta value
     *
     * @example
     * ```php
     * $metaValues = Post::bulkRememberMetaByType('post', 'views', 0, 'publish', 20);
     * ```
     */
    public static function bulkRememberMetaByType(string $postType, string $metaKey, mixed $default, string|array $status = 'publish', int $limit = 10): array
    {
        $results = [];

        // Get posts by type and status
        $posts = self::getByType($postType, $status, $limit);

        // Loop through each post
        foreach ($posts as $post) {
            $postId = $post->ID;

            // Use nested remember if metaKey is dot-notated
            if (str_contains($metaKey, '.')) {
                $results[$postId] = self::rememberMetaNested($postId, explode('.', $metaKey)[0], $metaKey, $default);
            } else {
                $results[$postId] = self::rememberMetaNested($postId, $metaKey, '', $default);
            }
        }

        return $results;
    }

    /**
     * Increment a numeric meta value for multiple posts of a given post type.
     *
     * Automatically queries posts and increments the meta value, creating it if missing.
     *
     * @param string $postType Post type to query
     * @param string $metaKey Meta key to increment (supports dot notation for nested arrays)
     * @param int|float $amount Amount to increment (default 1)
     * @param int|float $default Default value if meta is missing (default 0)
     * @param string|array $status Post status(es) to filter (default 'publish')
     * @param int $limit Number of posts to process (default 10)
     *
     * @return array Associative array of post ID => new meta value
     *
     * @example
     * ```php
     * $newViews = Post::incrementMetaByType('post', 'stats.views', 1, 0, 'publish', 20);
     * ```
     */
    public static function incrementMetaByType(string $postType, string $metaKey, int|float $amount = 1, int|float $default = 0, string|array $status = 'publish', int $limit = 10): array
    {
        $results = [];

        // Get posts by type and status
        $posts = self::getByType($postType, $status, $limit);

        // Loop through each post
        foreach ($posts as $post) {
            $postId = $post->ID;

            // Use nested increment if metaKey is dot-notated
            if (str_contains($metaKey, '.')) {
                $results[$postId] = self::incrementMetaNested($postId, explode('.', $metaKey)[0], $metaKey, $amount, $default);
            } else {
                $results[$postId] = self::incrementMetaNested($postId, $metaKey, '', $amount, $default);
            }
        }

        return $results;
    }

    /**
     * Decrement a numeric meta value for multiple posts of a given post type.
     *
     * Automatically queries posts and decrements the meta value, creating it if missing.
     *
     * @param string $postType Post type to query
     * @param string $metaKey Meta key to decrement (supports dot notation for nested arrays)
     * @param int|float $amount Amount to decrement (default 1)
     * @param int|float $default Default value if meta is missing (default 0)
     * @param string|array $status Post status(es) to filter (default 'publish')
     * @param int $limit Number of posts to process (default 10)
     *
     * @return array Associative array of post ID => new meta value
     *
     * @example
     * ```php
     * $newViews = Post::decrementMetaByType('post', 'stats.views', 1, 10, 'publish', 20);
     * ```
     */
    public static function decrementMetaByType(string $postType, string $metaKey, int|float $amount = 1, int|float $default = 0, string|array $status = 'publish', int $limit = 10): array
    {
        $results = [];

        // Get posts by type and status
        $posts = self::getByType($postType, $status, $limit);

        // Loop through each post
        foreach ($posts as $post) {
            $postId = $post->ID;

            // Use nested decrement if metaKey is dot-notated
            if (str_contains($metaKey, '.')) {
                $results[$postId] = self::decrementMetaNested($postId, explode('.', $metaKey)[0], $metaKey, $amount, $default);
            } else {
                $results[$postId] = self::decrementMetaNested($postId, $metaKey, '', $amount, $default);
            }
        }

        return $results;
    }

    /**
     * Get posts filtered by meta key and value.
     *
     * Supports comparison operators and limits the number of posts returned.
     *
     * @param string $metaKey Meta key to filter by
     * @param mixed $value Value to compare
     * @param string $compare Comparison operator (default '=')
     * @param string $postType Post type to query (default 'post')
     * @param string|array $status Post status(es) to filter (default 'publish')
     * @param int $limit Number of posts to retrieve (default 10)
     *
     * @return \WP_Post[] Array of WP_Post objects
     *
     * @example
     * ```php
     * $posts = Post::getByMeta('featured', true, '=', 'post', 'publish', 5);
     * $highViews = Post::getByMeta('stats.views', 100, '>=', 'post', 'publish', 10);
     * ```
     */
    public static function getByMeta(string $metaKey, mixed $value, string $compare = '=', string $postType = 'post', string|array $status = 'publish', int $limit = 10): array
    {
        // Handle nested meta keys
        if (str_contains($metaKey, '.')) {
            $topKey = explode('.', $metaKey)[0];
            $allPosts = self::getByType($postType, $status, $limit * 5); // Get extra posts in case nested value
            $filtered = [];

            foreach ($allPosts as $post) {
                $metaValue = self::getMetaNested($post->ID, $topKey, $metaKey);
                if (self::compareValues($metaValue, $value, $compare)) {
                    $filtered[] = $post;
                }
                if (count($filtered) >= $limit) break;
            }

            return $filtered;
        }

        // Simple meta query for flat keys
        return get_posts([
            'post_type'   => $postType,
            'post_status' => $status,
            'numberposts' => $limit,
            'meta_key'    => $metaKey,
            'meta_value'  => $value,
            'meta_compare'=> $compare,
        ]);
    }

    /**
     * Compare two values using a given operator.
     *
     * @param mixed $a
     * @param mixed $b
     * @param string $operator
     * @return bool
     *
     * @internal
     */
    protected static function compareValues(mixed $a, mixed $b, string $operator): bool
    {
        return match ($operator) {
            '=', '==' => $a == $b,
            '==='     => $a === $b,
            '!=', '<>' => $a != $b,
            '!=='     => $a !== $b,
            '>'       => $a > $b,
            '>='      => $a >= $b,
            '<'       => $a < $b,
            '<='      => $a <= $b,
            default   => $a == $b,
        };
    }

    /**
     * Bulk update a meta key for posts filtered by another meta key/value.
     *
     * Supports nested meta keys via dot notation.
     *
     * @param string $filterMetaKey Meta key to filter posts
     * @param mixed $filterValue Value to match for filtering
     * @param string $updateMetaKey Meta key to update
     * @param mixed $updateValue Value to set
     * @param string $compare Comparison operator for filtering (default '=')
     * @param string $postType Post type to query (default 'post')
     * @param string|array $status Post status(es) to filter (default 'publish')
     * @param int $limit Number of posts to process (default 10)
     *
     * @return int Number of posts updated
     *
     * @example
     * ```php
     * $count = Post::bulkUpdateMetaByMeta('stats.views', '>=', 'stats.priority', 1, '>=', 'post', 'publish', 20);
     * ```
     */
    public static function bulkUpdateMetaByMeta(string $filterMetaKey, mixed $filterValue, string $updateMetaKey, mixed $updateValue, string $compare = '=', string $postType = 'post', string|array $status = 'publish', int $limit = 10): int
    {
        $updatedCount = 0;

        // Get posts filtered by meta
        $posts = self::getByMeta($filterMetaKey, $filterValue, $compare, $postType, $status, $limit);

        // Loop through each post and update meta
        foreach ($posts as $post) {
            $postId = $post->ID;

            // Determine nested vs flat for updateMetaKey
            if (str_contains($updateMetaKey, '.')) {
                self::setMetaNested($postId, explode('.', $updateMetaKey)[0], $updateMetaKey, $updateValue);
            } else {
                self::setMeta($postId, $updateMetaKey, $updateValue);
            }

            $updatedCount++;
        }

        return $updatedCount;
    }

    /**
     * Bulk increment a numeric meta value for posts filtered by another meta key/value.
     *
     * Supports nested meta keys via dot notation.
     *
     * @param string $filterMetaKey Meta key to filter posts
     * @param mixed $filterValue Value to match for filtering
     * @param string $incrementMetaKey Meta key to increment
     * @param int|float $amount Amount to increment (default 1)
     * @param int|float $default Default value if meta is missing (default 0)
     * @param string $compare Comparison operator for filtering (default '=')
     * @param string $postType Post type to query (default 'post')
     * @param string|array $status Post status(es) to filter (default 'publish')
     * @param int $limit Number of posts to process (default 10)
     *
     * @return array Associative array of post ID => new meta value
     *
     * @example
     * ```php
     * $newValues = Post::bulkIncrementMetaByMeta('stats.views', 100, 'stats.priority', 1, 0, '>=', 'post', 'publish', 20);
     * ```
     */
    public static function bulkIncrementMetaByMeta(string $filterMetaKey, mixed $filterValue, string $incrementMetaKey, int|float $amount = 1, int|float $default = 0, string $compare = '=', string $postType = 'post', string|array $status = 'publish', int $limit = 10): array
    {
        $results = [];

        // Get posts filtered by meta
        $posts = self::getByMeta($filterMetaKey, $filterValue, $compare, $postType, $status, $limit);

        // Loop through each post and increment meta
        foreach ($posts as $post) {
            $postId = $post->ID;

            // Handle nested meta
            if (str_contains($incrementMetaKey, '.')) {
                $results[$postId] = self::incrementMetaNested($postId, explode('.', $incrementMetaKey)[0], $incrementMetaKey, $amount, $default);
            } else {
                $results[$postId] = self::incrementMetaNested($postId, $incrementMetaKey, '', $amount, $default);
            }
        }

        return $results;
    }

    /**
     * Bulk decrement a numeric meta value for posts filtered by another meta key/value.
     *
     * Supports nested meta keys via dot notation.
     *
     * @param string $filterMetaKey Meta key to filter posts
     * @param mixed $filterValue Value to match for filtering
     * @param string $decrementMetaKey Meta key to decrement
     * @param int|float $amount Amount to decrement (default 1)
     * @param int|float $default Default value if meta is missing (default 0)
     * @param string $compare Comparison operator for filtering (default '=')
     * @param string $postType Post type to query (default 'post')
     * @param string|array $status Post status(es) to filter (default 'publish')
     * @param int $limit Number of posts to process (default 10)
     *
     * @return array Associative array of post ID => new meta value
     *
     * @example
     * ```php
     * $newValues = Post::bulkDecrementMetaByMeta('stats.views', 100, 'stats.priority', 1, 10, '>=', 'post', 'publish', 20);
     * ```
     */
    public static function bulkDecrementMetaByMeta(string $filterMetaKey, mixed $filterValue, string $decrementMetaKey, int|float $amount = 1, int|float $default = 0, string $compare = '=', string $postType = 'post', string|array $status = 'publish', int $limit = 10): array
    {
        $results = [];

        // Get posts filtered by meta
        $posts = self::getByMeta($filterMetaKey, $filterValue, $compare, $postType, $status, $limit);

        // Loop through each post and decrement meta
        foreach ($posts as $post) {
            $postId = $post->ID;

            // Handle nested meta
            if (str_contains($decrementMetaKey, '.')) {
                $results[$postId] = self::decrementMetaNested($postId, explode('.', $decrementMetaKey)[0], $decrementMetaKey, $amount, $default);
            } else {
                $results[$postId] = self::decrementMetaNested($postId, $decrementMetaKey, '', $amount, $default);
            }
        }

        return $results;
    }

    /**
     * Fetch meta for posts filtered by another meta key/value, or initialize if missing.
     *
     * Automatically sets the default value if the meta is missing.
     * Supports nested meta keys via dot notation.
     *
     * @param string $filterMetaKey Meta key to filter posts
     * @param mixed $filterValue Value to match for filtering
     * @param string $targetMetaKey Meta key to retrieve or set
     * @param mixed $default Default value to set if meta is missing
     * @param string $compare Comparison operator for filtering (default '=')
     * @param string $postType Post type to query (default 'post')
     * @param string|array $status Post status(es) to filter (default 'publish')
     * @param int $limit Number of posts to process (default 10)
     *
     * @return array Associative array of post ID => meta value (existing or default)
     *
     * @example
     * ```php
     * $values = Post::rememberMetaByMeta('stats.views', '>=', 'stats.priority', 1, '>=', 'post', 'publish', 20);
     * ```
     */
    public static function rememberMetaByMeta(
        string $filterMetaKey,
        mixed $filterValue,
        string $targetMetaKey,
        mixed $default,
        string $compare = '=',
        string $postType = 'post',
        string|array $status = 'publish',
        int $limit = 10
    ): array {
        $results = [];

        // Get posts filtered by meta
        $posts = self::getByMeta($filterMetaKey, $filterValue, $compare, $postType, $status, $limit);

        // Loop through each post
        foreach ($posts as $post) {
            $postId = $post->ID;

            // Handle nested meta keys
            if (str_contains($targetMetaKey, '.')) {
                $value = self::getMetaNested($postId, explode('.', $targetMetaKey)[0], $targetMetaKey, $default);
                // If value missing, set default
                if ($value === null) {
                    self::setMetaNested($postId, explode('.', $targetMetaKey)[0], $targetMetaKey, $default);
                    $value = $default;
                }
            } else {
                $value = self::getMeta($postId, $targetMetaKey, $default);
                // If value missing, set default
                if ($value === null) {
                    self::setMeta($postId, $targetMetaKey, $default);
                    $value = $default;
                }
            }

            $results[$postId] = $value;
        }

        return $results;
    }

    /**
     * Fetch meta for all posts of a given type, or initialize if missing.
     *
     * Automatically sets the default value if the meta is missing.
     * Supports nested meta keys via dot notation.
     *
     * @param string $postType Post type to query
     * @param string $metaKey Meta key to retrieve or set
     * @param mixed $default Default value to set if meta is missing
     * @param string|array $status Post status(es) to filter (default 'publish')
     * @param int $limit Number of posts to process (default 10)
     *
     * @return array Associative array of post ID => meta value (existing or default)
     *
     * @example
     * ```php
     * $values = Post::rememberMetaByType('post', 'stats.priority', 1, 'publish', 20);
     * ```
     */
    public static function rememberMetaByType(string $postType, string $metaKey, mixed $default, string|array $status = 'publish', int $limit = 10): array
    {
        $results = [];

        // Get posts of the specified type
        $posts = self::getByType($postType, $status, $limit);

        // Loop through each post
        foreach ($posts as $post) {
            $postId = $post->ID;

            // Handle nested meta keys
            if (str_contains($metaKey, '.')) {
                $value = self::getMetaNested($postId, explode('.', $metaKey)[0], $metaKey, $default);
                // If value missing, set default
                if ($value === null) {
                    self::setMetaNested($postId, explode('.', $metaKey)[0], $metaKey, $default);
                    $value = $default;
                }
            } else {
                $value = self::getMeta($postId, $metaKey, $default);
                // If value missing, set default
                if ($value === null) {
                    self::setMeta($postId, $metaKey, $default);
                    $value = $default;
                }
            }

            $results[$postId] = $value;
        }

        return $results;
    }

    /**
     * Bulk delete a meta key for posts filtered by another meta key/value.
     *
     * Supports nested meta keys via dot notation.
     *
     * @param string $filterMetaKey Meta key to filter posts
     * @param mixed $filterValue Value to match for filtering
     * @param string $deleteMetaKey Meta key to delete
     * @param string $compare Comparison operator for filtering (default '=')
     * @param string $postType Post type to query (default 'post')
     * @param string|array $status Post status(es) to filter (default 'publish')
     * @param int $limit Number of posts to process (default 10)
     *
     * @return int Number of posts deleted
     *
     * @example
     * ```php
     * $count = Post::bulkDeleteMetaByMeta('stats.views', '>=', 'stats.priority', '>=', 'post', 'publish', 20);
     * ```
     */
    public static function bulkDeleteMetaByMeta(string $filterMetaKey, mixed $filterValue, string $deleteMetaKey, string $compare = '=', string $postType = 'post', string|array $status = 'publish', int $limit = 10): int
    {
        $deletedCount = 0;

        // Get posts filtered by meta
        $posts = self::getByMeta($filterMetaKey, $filterValue, $compare, $postType, $status, $limit);

        // Loop through each post and delete meta
        foreach ($posts as $post) {
            $postId = $post->ID;

            // Handle nested meta
            if (str_contains($deleteMetaKey, '.')) {
                self::deleteMetaNested($postId, explode('.', $deleteMetaKey)[0], $deleteMetaKey);
            } else {
                self::deleteMeta($postId, $deleteMetaKey);
            }

            $deletedCount++;
        }

        return $deletedCount;
    }

    /**
     * Fetch post meta with transient caching, or initialize and cache if missing.
     *
     * Uses WordPress transients to cache meta values for performance.
     * Supports nested meta keys via dot notation.
     *
     * @param int $postId Post ID
     * @param string $metaKey Meta key to retrieve
     * @param mixed $default Default value if meta is missing
     * @param int $expiration Transient expiration in seconds (default 3600 = 1 hour)
     *
     * @return mixed Meta value (existing or default)
     *
     * @example
     * ```php
     * $value = Post::rememberMetaTransient(123, 'stats.views', 0, 600);
     * ```
     */
    public static function rememberMetaTransient(int $postId, string $metaKey, mixed $default = null, int $expiration = 3600): mixed
    {
        // Generate a unique transient key per post and meta key
        $transientKey = 'post_meta_' . $postId . '_' . md5($metaKey);

        // Attempt to get value from transient
        $cached = get_transient($transientKey);
        if ($cached !== false) {
            return $cached;
        }

        // Fetch meta value (handles nested meta)
        if (str_contains($metaKey, '.')) {
            $value = self::getMetaNested($postId, explode('.', $metaKey)[0], $metaKey, $default);
        } else {
            $value = self::getMeta($postId, $metaKey, $default);
        }

        // If missing, set default
        if ($value === null) {
            if (str_contains($metaKey, '.')) {
                self::setMetaNested($postId, explode('.', $metaKey)[0], $metaKey, $default);
            } else {
                self::setMeta($postId, $metaKey, $default);
            }
            $value = $default;
        }

        // Cache value in transient
        set_transient($transientKey, $value, $expiration);

        return $value;
    }

    /**
     * Clear cached post meta transient.
     *
     * Removes the transient cache for a specific post meta key.
     * Supports nested meta keys via dot notation.
     *
     * @param int $postId Post ID
     * @param string $metaKey Meta key to clear from transient cache
     *
     * @return void
     *
     * @example
     * ```php
     * Post::forgetMetaTransient(123, 'stats.views');
     * ```
     */
    public static function forgetMetaTransient(int $postId, string $metaKey): void
    {
        // Generate the transient key
        $transientKey = 'post_meta_' . $postId . '_' . md5($metaKey);

        // Delete the transient
        delete_transient($transientKey);
    }

    /**
     * Bulk clear cached post meta transients for posts filtered by another meta key/value.
     *
     * Supports nested meta keys via dot notation.
     *
     * @param string $filterMetaKey Meta key to filter posts
     * @param mixed $filterValue Value to match for filtering
     * @param string $targetMetaKey Meta key whose transient cache will be cleared
     * @param string $compare Comparison operator for filtering (default '=')
     * @param string $postType Post type to query (default 'post')
     * @param string|array $status Post status(es) to filter (default 'publish')
     * @param int $limit Number of posts to process (default 10)
     *
     * @return int Number of posts whose transients were cleared
     *
     * @example
     * ```php
     * $count = Post::forgetMetaTransientByMeta('stats.views', '>=', 'stats.priority', '>=', 'post', 'publish', 20);
     * ```
     */
    public static function forgetMetaTransientByMeta(
        string $filterMetaKey,
        mixed $filterValue,
        string $targetMetaKey,
        string $compare = '=',
        string $postType = 'post',
        string|array $status = 'publish',
        int $limit = 10
    ): int {
        $clearedCount = 0;

        // Get posts filtered by meta
        $posts = self::getByMeta($filterMetaKey, $filterValue, $compare, $postType, $status, $limit);

        // Loop through each post and clear transient
        foreach ($posts as $post) {
            $postId = $post->ID;

            self::forgetMetaTransient($postId, $targetMetaKey);
            $clearedCount++;
        }

        return $clearedCount;
    }

    /**
     * Fetch posts by type with transient caching.
     *
     * Caches the entire query result in a transient for performance.
     *
     * @param string $postType Post type to query
     * @param string|array $status Post status(es) to filter (default 'publish')
     * @param int $limit Number of posts to retrieve (default 10)
     * @param int $expiration Transient expiration in seconds (default 3600 = 1 hour)
     *
     * @return WP_Post[] Array of WP_Post objects
     *
     * @example
     * ```php
     * $posts = Post::rememberPostsByType('post', 'publish', 20, 600);
     * ```
     */
    public static function rememberPostsByType(string $postType, string|array $status = 'publish', int $limit = 10, int $expiration = 3600): array
    {
        // Generate a unique transient key
        $transientKey = 'posts_by_type_' . $postType . '_' . md5(is_array($status) ? implode(',', $status) : $status) . '_' . $limit;

        // Attempt to get cached posts from transient
        $cached = get_transient($transientKey);
        if ($cached !== false) {
            return $cached;
        }

        // Fetch posts from WordPress
        $posts = self::getByType($postType, $status, $limit);

        // Cache the posts array in transient
        set_transient($transientKey, $posts, $expiration);

        return $posts;
    }

    /**
     * Fetch posts by meta key/value with transient caching.
     *
     * Supports nested meta keys via dot notation.
     *
     * @param string $metaKey Meta key to filter posts
     * @param mixed $metaValue Value to match
     * @param string $compare Comparison operator (default '=')
     * @param string $postType Post type to query (default 'post')
     * @param string|array $status Post status(es) to filter (default 'publish')
     * @param int $limit Number of posts to retrieve (default 10)
     * @param int $expiration Transient expiration in seconds (default 3600 = 1 hour)
     *
     * @return WP_Post[] Array of WP_Post objects
     *
     * @example
     * ```php
     * $posts = Post::rememberPostsByMeta('stats.views', '>=', 100, 'post', 'publish', 20, 600);
     * ```
     */
    public static function rememberPostsByMeta(
        string $metaKey,
        mixed $metaValue,
        string $compare = '=',
        string $postType = 'post',
        string|array $status = 'publish',
        int $limit = 10,
        int $expiration = 3600
    ): array {
        // Generate a unique transient key based on filter parameters
        $transientKey = 'posts_by_meta_' . md5($metaKey . serialize($metaValue) . $compare . $postType . (is_array($status) ? implode(',', $status) : $status) . $limit);

        // Attempt to get cached posts from transient
        $cached = get_transient($transientKey);
        if ($cached !== false) {
            return $cached;
        }

        // Fetch posts using existing helper
        $posts = self::getByMeta($metaKey, $metaValue, $compare, $postType, $status, $limit);

        // Cache the posts array in transient
        set_transient($transientKey, $posts, $expiration);

        return $posts;
    }

    /**
     * Clear cached posts transient by post type.
     *
     * Removes the cached query results for posts of a specific type.
     *
     * @param string $postType Post type whose cached queries should be cleared
     *
     * @return void
     *
     * @example
     * ```php
     * Post::forgetPostsCacheByType('post');
     * ```
     */
    public static function forgetPostsCacheByType(string $postType): void
    {
        global $wpdb;

        // Build a transient name pattern for this post type
        $pattern = '_transient_posts_by_type_' . $postType . '%';

        // Query all transient names matching the pattern
        $keys = $wpdb->get_col($wpdb->prepare(
            "SELECT option_name FROM $wpdb->options WHERE option_name LIKE %s",
            $pattern
        ));

        // Delete each matching transient
        foreach ($keys as $key) {
            // Remove '_transient_' prefix to get actual transient key
            $transientKey = str_replace('_transient_', '', $key);
            delete_transient($transientKey);
        }
    }

    /**
     * Clear cached posts transient by meta filter.
     *
     * Removes cached query results for posts filtered by a meta key/value.
     *
     * @param string $metaKey Meta key used in the cached query
     * @param mixed $metaValue Value used in the cached query
     *
     * @return void
     *
     * @example
     * ```php
     * Post::forgetPostsCacheByMeta('stats.views', 100);
     * ```
     */
    public static function forgetPostsCacheByMeta(string $metaKey, mixed $metaValue): void
    {
        global $wpdb;

        // Generate the transient key pattern used in rememberPostsByMeta
        $pattern = '_transient_posts_by_meta_' . md5($metaKey . serialize($metaValue) . '%');

        // Query all transient names matching the pattern
        $keys = $wpdb->get_col($wpdb->prepare(
            "SELECT option_name FROM $wpdb->options WHERE option_name LIKE %s",
            $pattern
        ));

        // Delete each matching transient
        foreach ($keys as $key) {
            // Remove '_transient_' prefix to get actual transient key
            $transientKey = str_replace('_transient_', '', $key);
            delete_transient($transientKey);
        }
    }
}
