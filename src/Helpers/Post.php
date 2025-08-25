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
}
