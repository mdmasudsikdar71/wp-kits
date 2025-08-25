<?php

namespace MDMasudSikdar\WpKits\Helpers;

/**
 * Class User
 *
 * Advanced helper for WordPress users.
 *
 * Features:
 * ✅ Safe get/set user meta with default fallback
 * ✅ Bulk meta operations
 * ✅ Retrieve user roles and capabilities
 * ✅ Add or remove capabilities safely
 * ✅ Query users by role or custom args
 * ✅ Fully static and reusable across plugins
 *
 * Example usage:
 * ```php
 * use MDMasudSikdar\WpKits\Helpers\User;
 *
 * $meta = User::getMeta(123, 'my_key', 'default');
 * User::setMeta(123, 'my_key', 'value');
 * User::bulkSetMeta([123, 124], 'key', 'value');
 * $roles = User::getRoles(123);
 * User::addCapability(123, 'edit_posts');
 * User::removeCapability(123, 'delete_posts');
 * $admins = User::getByRole('administrator');
 * ```
 *
 * @package MDMasudSikdar\WpKits\Helpers
 */
class User
{
    /**
     * Get user meta safely with default fallback.
     *
     * @param int $userId
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     *
     * @example
     * ```php
     * $value = User::getMeta(123, 'my_key', 'default');
     * ```
     */
    public static function getMeta(int $userId, string $key, mixed $default = null): mixed
    {
        // Retrieve user meta
        $value = get_user_meta($userId, $key, true);

        // Return default if no value exists
        return $value !== '' ? $value : $default;
    }

    /**
     * Set user meta safely.
     *
     * @param int $userId
     * @param string $key
     * @param mixed $value
     *
     * @return void
     *
     * @example
     * ```php
     * User::setMeta(123, 'my_key', 'value');
     * ```
     */
    public static function setMeta(int $userId, string $key, mixed $value): void
    {
        // Update meta for user
        update_user_meta($userId, $key, $value);
    }

    /**
     * Get multiple meta keys at once for a user.
     *
     * @param int $userId
     * @param string[] $keys Array of meta keys
     * @param mixed $default Default value if key not found
     *
     * @return array Associative array of key => value
     *
     * @example
     * ```php
     * $meta = User::getMultipleMeta(123, ['key1', 'key2'], 'default');
     * ```
     */
    public static function getMultipleMeta(int $userId, array $keys, mixed $default = null): array
    {
        $results = [];

        // Loop through each key
        foreach ($keys as $key) {
            $results[$key] = self::getMeta($userId, $key, $default);
        }

        return $results;
    }

    /**
     * Set multiple meta keys at once for a user.
     *
     * @param int $userId
     * @param array $data Associative array of key => value
     *
     * @return void
     *
     * @example
     * ```php
     * User::setMultipleMeta(123, ['key1' => 'val1', 'key2' => 'val2']);
     * ```
     */
    public static function setMultipleMeta(int $userId, array $data): void
    {
        // Loop through each key-value pair
        foreach ($data as $key => $value) {
            self::setMeta($userId, $key, $value);
        }
    }

    /**
     * Add a capability to a user.
     *
     * @param int $userId
     * @param string $capability
     *
     * @return void
     *
     * @example
     * ```php
     * User::addCapability(123, 'edit_posts');
     * ```
     */
    public static function addCapability(int $userId, string $capability): void
    {
        // Get WP_User object
        $user = get_userdata($userId);

        // Add capability if user exists
        if ($user) {
            $user->add_cap($capability);
        }
    }

    /**
     * Remove a capability from a user.
     *
     * @param int $userId
     * @param string $capability
     *
     * @return void
     *
     * @example
     * ```php
     * User::removeCapability(123, 'delete_posts');
     * ```
     */
    public static function removeCapability(int $userId, string $capability): void
    {
        // Get WP_User object
        $user = get_userdata($userId);

        // Remove capability if user exists
        if ($user) {
            $user->remove_cap($capability);
        }
    }

    /**
     * Get all roles of a user.
     *
     * @param int $userId
     *
     * @return string[] Array of role slugs
     *
     * @example
     * ```php
     * $roles = User::getRoles(123);
     * ```
     */
    public static function getRoles(int $userId): array
    {
        // Retrieve WP_User object
        $user = get_userdata($userId);

        // Return roles if user exists, else empty array
        return $user ? $user->roles : [];
    }

    /**
     * Get users by role.
     *
     * @param string $role
     *
     * @return \WP_User[]
     *
     * @example
     * ```php
     * $admins = User::getByRole('administrator');
     * ```
     */
    public static function getByRole(string $role): array
    {
        // Return users with specific role
        return get_users(['role' => $role]);
    }

    /**
     * Bulk set meta for multiple users.
     *
     * @param int[] $userIds Array of user IDs
     * @param string $key Meta key
     * @param mixed $value Meta value
     *
     * @return void
     *
     * @example
     * ```php
     * User::bulkSetMeta([123, 124], 'key', 'value');
     * ```
     */
    public static function bulkSetMeta(array $userIds, string $key, mixed $value): void
    {
        // Loop through each user ID
        foreach ($userIds as $userId) {
            self::setMeta($userId, $key, $value);
        }
    }
}
