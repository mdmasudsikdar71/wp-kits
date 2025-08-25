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

    /**
     * Check if a user has a specific role.
     *
     * @param int $userId
     * @param string $role Role slug to check
     *
     * @return bool
     *
     * @example
     * ```php
     * if (User::hasRole(123, 'editor')) {
     *     // Do something for editors
     * }
     * ```
     */
    public static function hasRole(int $userId, string $role): bool
    {
        // Retrieve user object
        $user = get_userdata($userId);

        // Check role existence
        return $user && in_array($role, (array) $user->roles, true);
    }

    /**
     * Check if a user has a specific capability.
     *
     * @param int $userId
     * @param string $capability Capability name to check
     *
     * @return bool
     *
     * @example
     * ```php
     * if (User::hasCapability(123, 'edit_posts')) {
     *     // Grant access
     * }
     * ```
     */
    public static function hasCapability(int $userId, string $capability): bool
    {
        // Retrieve user object
        $user = get_userdata($userId);

        // Check capability
        return $user ? $user->has_cap($capability) : false;
    }

    /**
     * Delete a specific user meta key.
     *
     * @param int $userId
     * @param string $key Meta key to delete
     *
     * @return void
     *
     * @example
     * ```php
     * User::deleteMeta(123, 'my_key');
     * ```
     */
    public static function deleteMeta(int $userId, string $key): void
    {
        // Delete user meta key
        delete_user_meta($userId, $key);
    }

    /**
     * Delete a specific meta key from multiple users.
     *
     * @param int[] $userIds Array of user IDs
     * @param string $key Meta key to delete
     *
     * @return void
     *
     * @example
     * ```php
     * User::bulkDeleteMeta([123, 124], 'my_key');
     * ```
     */
    public static function bulkDeleteMeta(array $userIds, string $key): void
    {
        // Loop through each user ID
        foreach ($userIds as $userId) {
            self::deleteMeta($userId, $key);
        }
    }

    /**
     * Check if a user exists.
     *
     * @param int $userId
     *
     * @return bool
     *
     * @example
     * ```php
     * if (User::exists(123)) {
     *     // Proceed with operation
     * }
     * ```
     */
    public static function exists(int $userId): bool
    {
        // Check if user exists
        return get_userdata($userId) !== false;
    }

    /**
     * Get users by a specific meta key and value.
     *
     * @param string $key Meta key to match
     * @param mixed $value Meta value to match
     * @param array $args Optional additional WP_User_Query arguments
     *
     * @return \WP_User[] Array of WP_User objects
     *
     * @example
     * ```php
     * $users = User::getByMeta('newsletter_opt_in', true);
     * ```
     */
    public static function getByMeta(string $key, mixed $value, array $args = []): array
    {
        // Merge default query args with user-provided ones
        $queryArgs = array_merge([
            'meta_key'   => $key,
            'meta_value' => $value,
            'number'     => -1,
        ], $args);

        // Query users
        return get_users($queryArgs);
    }

    /**
     * Assign a role to multiple users.
     *
     * @param int[] $userIds Array of user IDs
     * @param string $role Role slug to assign
     *
     * @return void
     *
     * @example
     * ```php
     * User::bulkAssignRole([123, 124], 'subscriber');
     * ```
     */
    public static function bulkAssignRole(array $userIds, string $role): void
    {
        foreach ($userIds as $userId) {
            $user = get_userdata($userId);

            if ($user) {
                $user->set_role($role);
            }
        }
    }

    /**
     * Add a role to multiple users without removing existing roles.
     *
     * @param int[] $userIds Array of user IDs
     * @param string $role Role slug to add
     *
     * @return void
     *
     * @example
     * ```php
     * User::bulkAddRole([123, 124], 'contributor');
     * ```
     */
    public static function bulkAddRole(array $userIds, string $role): void
    {
        foreach ($userIds as $userId) {
            $user = get_userdata($userId);

            if ($user && !$user->has_role($role)) {
                $user->add_role($role);
            }
        }
    }

    /**
     * Remove a role from multiple users without affecting other roles.
     *
     * @param int[] $userIds Array of user IDs
     * @param string $role Role slug to remove
     *
     * @return void
     *
     * @example
     * ```php
     * User::bulkRemoveRole([123, 124], 'subscriber');
     * ```
     */
    public static function bulkRemoveRole(array $userIds, string $role): void
    {
        foreach ($userIds as $userId) {
            $user = get_userdata($userId);

            if ($user) {
                $user->remove_role($role);
            }
        }
    }

    /**
     * Get the display name of a user.
     *
     * @param int $userId
     *
     * @return string|null
     *
     * @example
     * ```php
     * $name = User::getDisplayName(123);
     * ```
     */
    public static function getDisplayName(int $userId): ?string
    {
        $user = get_userdata($userId);

        return $user ? $user->display_name : null;
    }

    /**
     * Get the email address of a user.
     *
     * @param int $userId
     *
     * @return string|null
     *
     * @example
     * ```php
     * $email = User::getEmail(123);
     * ```
     */
    public static function getEmail(int $userId): ?string
    {
        $user = get_userdata($userId);

        return $user ? $user->user_email : null;
    }

    /**
     * Get all users with optional filters.
     *
     * @param array $args Additional WP_User_Query arguments
     *
     * @return \WP_User[]
     *
     * @example
     * ```php
     * $users = User::getAll(['number' => 50]);
     * ```
     */
    public static function getAll(array $args = []): array
    {
        $defaults = [
            'number' => -1, // All users
        ];

        return get_users(array_merge($defaults, $args));
    }

    /**
     * Get user ID by email address.
     *
     * @param string $email
     *
     * @return int|null
     *
     * @example
     * ```php
     * $userId = User::getIdByEmail('user@example.com');
     * ```
     */
    public static function getIdByEmail(string $email): ?int
    {
        $user = get_user_by('email', $email);

        return $user ? $user->ID : null;
    }

    /**
     * Delete all user meta for a given user.
     * ⚠️ Use with caution — this will wipe all user meta.
     *
     * @param int $userId
     *
     * @return void
     *
     * @example
     * ```php
     * User::resetAllMeta(123);
     * ```
     */
    public static function resetAllMeta(int $userId): void
    {
        global $wpdb;

        $wpdb->delete($wpdb->usermeta, ['user_id' => $userId]);
    }

    /**
     * Remove all roles from a user.
     *
     * @param int $userId
     *
     * @return void
     *
     * @example
     * ```php
     * User::removeAllRoles(123);
     * ```
     */
    public static function removeAllRoles(int $userId): void
    {
        $user = get_userdata($userId);

        if ($user) {
            foreach ($user->roles as $role) {
                $user->remove_role($role);
            }
        }
    }

    /**
     * Get user registration date.
     *
     * @param int $userId
     *
     * @return string|null Date in 'Y-m-d H:i:s' format or null if not found
     *
     * @example
     * ```php
     * $regDate = User::getRegistrationDate(123);
     * ```
     */
    public static function getRegistrationDate(int $userId): ?string
    {
        $user = get_userdata($userId);

        return $user ? $user->user_registered : null;
    }

    /**
     * Update the user’s display name.
     *
     * @param int $userId
     * @param string $displayName
     *
     * @return bool True on success, false on failure
     *
     * @example
     * ```php
     * User::updateDisplayName(123, 'New Name');
     * ```
     */
    public static function updateDisplayName(int $userId, string $displayName): bool
    {
        return wp_update_user([
                'ID' => $userId,
                'display_name' => $displayName,
            ]) !== 0;
    }

    /**
     * Get user by login name.
     *
     * @param string $login
     *
     * @return \WP_User|null
     *
     * @example
     * ```php
     * $user = User::getByLogin('john_doe');
     * ```
     */
    public static function getByLogin(string $login): ?\WP_User
    {
        return get_user_by('login', $login) ?: null;
    }

    /**
     * Set user password safely.
     *
     * @param int $userId
     * @param string $password Plain text new password
     *
     * @return bool True on success, false on failure
     *
     * @example
     * ```php
     * User::setPassword(123, 'new_password');
     * ```
     */
    public static function setPassword(int $userId, string $password): bool
    {
        return wp_set_password($password, $userId) === null;
    }

    /**
     * Check if a user has any of the given roles.
     *
     * @param int $userId
     * @param string[] $roles Array of role slugs to check
     *
     * @return bool
     *
     * @example
     * ```php
     * if (User::hasAnyRole(123, ['editor', 'author'])) {
     *     // User has at least one of the roles
     * }
     * ```
     */
    public static function hasAnyRole(int $userId, array $roles): bool
    {
        // Get the WP_User object for the given user ID
        $user = get_userdata($userId);

        // Return false immediately if user does not exist
        if (!$user) {
            return false;
        }

        // Loop through each role to check if the user has it
        foreach ($roles as $role) {
            // If user has this role, return true immediately
            if (in_array($role, (array) $user->roles, true)) {
                return true;
            }
        }

        // User does not have any of the roles
        return false;
    }

    /**
     * Check if a user has all of the given roles.
     *
     * @param int $userId
     * @param string[] $roles Array of role slugs to check
     *
     * @return bool
     *
     * @example
     * ```php
     * if (User::hasAllRoles(123, ['editor', 'author'])) {
     *     // User has all roles listed
     * }
     * ```
     */
    public static function hasAllRoles(int $userId, array $roles): bool
    {
        // Get WP_User object for the user ID
        $user = get_userdata($userId);

        // Return false if user does not exist
        if (!$user) {
            return false;
        }

        // Check if any roles are missing from user's current roles
        // If none are missing, return true
        return empty(array_diff($roles, (array) $user->roles));
    }

    /**
     * Retrieve a list of user IDs by role.
     *
     * @param string $role
     *
     * @return int[]
     *
     * @example
     * ```php
     * $userIds = User::getIdsByRole('subscriber');
     * ```
     */
    public static function getIdsByRole(string $role): array
    {
        // Query users with the specified role, return only IDs
        $users = get_users([
            'role'   => $role,
            'fields' => 'ID',
        ]);

        // Return array of user IDs
        return $users;
    }

    /**
     * Set multiple meta keys for multiple users.
     *
     * @param int[] $userIds Array of user IDs
     * @param array $data Associative array of key => value meta pairs
     *
     * @return void
     *
     * @example
     * ```php
     * User::bulkSetMultipleMeta([123, 124], ['key1' => 'val1', 'key2' => 'val2']);
     * ```
     */
    public static function bulkSetMultipleMeta(array $userIds, array $data): void
    {
        // Loop over each user ID
        foreach ($userIds as $userId) {
            // Set multiple meta keys for this user using existing method
            self::setMultipleMeta($userId, $data);
        }
    }

    /**
     * Get multiple meta keys for multiple users.
     *
     * @param int[] $userIds Array of user IDs
     * @param string[] $keys Array of meta keys
     * @param mixed $default Default value if key not found
     *
     * @return array Associative array of userId => (key => value) pairs
     *
     * @example
     * ```php
     * $result = User::bulkGetMultipleMeta([123, 124], ['key1', 'key2'], 'default');
     * ```
     */
    public static function bulkGetMultipleMeta(array $userIds, array $keys, mixed $default = null): array
    {
        // Initialize result array
        $results = [];

        // Loop through each user ID
        foreach ($userIds as $userId) {
            // Get multiple meta keys for this user, store in results keyed by userId
            $results[$userId] = self::getMultipleMeta($userId, $keys, $default);
        }

        // Return the combined results
        return $results;
    }

    /**
     * Get the URL of the user’s avatar.
     *
     * @param int $userId
     * @param int $size Size in pixels of the avatar image (default 96)
     *
     * @return string Avatar URL or empty string if none
     *
     * @example
     * ```php
     * $avatarUrl = User::getAvatarUrl(123, 64);
     * ```
     */
    public static function getAvatarUrl(int $userId, int $size = 96): string
    {
        // Get the avatar HTML img tag
        $avatarHtml = get_avatar($userId, $size, '', '', ['force_display' => false]);

        // Use regex to extract the src attribute URL from the img tag
        if (preg_match('/src=["\']([^"\']+)["\']/', $avatarHtml, $matches)) {
            return $matches[1];
        }

        // Return empty string if no URL found
        return '';
    }

    /**
     * Delete multiple meta keys for a user.
     *
     * @param int $userId
     * @param string[] $keys Array of meta keys to delete
     *
     * @return void
     *
     * @example
     * ```php
     * User::deleteMultipleMeta(123, ['key1', 'key2']);
     * ```
     */
    public static function deleteMultipleMeta(int $userId, array $keys): void
    {
        // Loop through each key and delete meta
        foreach ($keys as $key) {
            delete_user_meta($userId, $key);
        }
    }

    /**
     * Get the user's first name.
     *
     * @param int $userId
     *
     * @return string|null First name or null if not set
     *
     * @example
     * ```php
     * $firstName = User::getFirstName(123);
     * ```
     */
    public static function getFirstName(int $userId): ?string
    {
        // Use getMeta to safely retrieve 'first_name' meta key
        return self::getMeta($userId, 'first_name', null);
    }

    /**
     * Get the user's last name.
     *
     * @param int $userId
     *
     * @return string|null Last name or null if not set
     *
     * @example
     * ```php
     * $lastName = User::getLastName(123);
     * ```
     */
    public static function getLastName(int $userId): ?string
    {
        // Use getMeta to safely retrieve 'last_name' meta key
        return self::getMeta($userId, 'last_name', null);
    }

    /**
     * Set the user's first name.
     *
     * @param int $userId
     * @param string $firstName
     *
     * @return void
     *
     * @example
     * ```php
     * User::setFirstName(123, 'John');
     * ```
     */
    public static function setFirstName(int $userId, string $firstName): void
    {
        // Use setMeta to safely update 'first_name' meta key
        self::setMeta($userId, 'first_name', $firstName);
    }

    /**
     * Set the user's last name.
     *
     * @param int $userId
     * @param string $lastName
     *
     * @return void
     *
     * @example
     * ```php
     * User::setLastName(123, 'Doe');
     * ```
     */
    public static function setLastName(int $userId, string $lastName): void
    {
        // Use setMeta to safely update 'last_name' meta key
        self::setMeta($userId, 'last_name', $lastName);
    }

    /**
     * Get the full name of a user by combining first and last names.
     *
     * @param int $userId
     *
     * @return string|null Full name or null if neither first nor last name set
     *
     * @example
     * ```php
     * $fullName = User::getFullName(123);
     * ```
     */
    public static function getFullName(int $userId): ?string
    {
        // Retrieve first and last name using our helper methods
        $firstName = self::getFirstName($userId);
        $lastName = self::getLastName($userId);

        // If neither name is set, return null
        if (!$firstName && !$lastName) {
            return null;
        }

        // Combine with space, trimming to avoid extra spaces
        return trim("$firstName $lastName");
    }

    /**
     * Set the full name of a user by splitting into first and last names.
     *
     * @param int $userId
     * @param string $fullName Full name string, e.g. "John Doe"
     *
     * @return void
     *
     * @example
     * ```php
     * User::setFullName(123, 'John Doe');
     * ```
     */
    public static function setFullName(int $userId, string $fullName): void
    {
        // Split full name by spaces into parts
        $parts = explode(' ', trim($fullName), 2);

        // Set first name to first part
        $firstName = $parts[0] ?? '';

        // Set last name to second part if exists, otherwise empty
        $lastName = $parts[1] ?? '';

        // Update user meta for first and last name
        self::setFirstName($userId, $firstName);
        self::setLastName($userId, $lastName);
    }

    /**
     * Add a role to a user.
     *
     * @param int $userId
     * @param string $role Role slug to add
     *
     * @return void
     *
     * @example
     * ```php
     * User::addRole(123, 'editor');
     * ```
     */
    public static function addRole(int $userId, string $role): void
    {
        // Get WP_User object
        $user = get_userdata($userId);

        // Add role if user exists
        if ($user) {
            $user->add_role($role);
        }
    }

    /**
     * Remove a role from a user.
     *
     * @param int $userId
     * @param string $role Role slug to remove
     *
     * @return void
     *
     * @example
     * ```php
     * User::removeRole(123, 'subscriber');
     * ```
     */
    public static function removeRole(int $userId, string $role): void
    {
        // Get WP_User object
        $user = get_userdata($userId);

        // Remove role if user exists
        if ($user) {
            $user->remove_role($role);
        }
    }

    /**
     * Get count of users by role.
     *
     * @param string $role Role slug
     *
     * @return int Number of users with the role
     *
     * @example
     * ```php
     * $count = User::countByRole('subscriber');
     * ```
     */
    public static function countByRole(string $role): int
    {
        // Get users with role and count
        return count(get_users(['role' => $role, 'fields' => 'ID']));
    }

    /**
     * Reset user password and send reset email.
     *
     * @param int $userId
     *
     * @return bool True if reset email sent, false on failure
     *
     * @example
     * ```php
     * User::sendPasswordResetEmail(123);
     * ```
     */
    public static function sendPasswordResetEmail(int $userId): bool
    {
        $user = get_userdata($userId);

        if (!$user) {
            return false;
        }

        // Use WordPress function to send password reset email
        return (bool) retrieve_password($user->user_login);
    }

    /**
     * Update user email address.
     *
     * @param int $userId
     * @param string $email New email address
     *
     * @return bool True on success, false on failure
     *
     * @example
     * ```php
     * User::setEmail(123, 'new@example.com');
     * ```
     */
    public static function setEmail(int $userId, string $email): bool
    {
        // Update user email via wp_update_user
        $result = wp_update_user([
            'ID'         => $userId,
            'user_email' => $email,
        ]);

        // wp_update_user returns user ID on success or WP_Error on failure
        return !is_wp_error($result);
    }

    /**
     * Get user login name.
     *
     * @param int $userId
     *
     * @return string|null Login name or null if user not found
     *
     * @example
     * ```php
     * $login = User::getLogin(123);
     * ```
     */
    public static function getLogin(int $userId): ?string
    {
        $user = get_userdata($userId);

        return $user ? $user->user_login : null;
    }

    /**
     * Get user registered IP address.
     *
     * @param int $userId
     *
     * @return string|null IP address stored in user meta or null if none
     *
     * @example
     * ```php
     * $ip = User::getRegisteredIp(123);
     * ```
     */
    public static function getRegisteredIp(int $userId): ?string
    {
        // Custom meta key 'registration_ip' assumed to hold IP address
        return self::getMeta($userId, 'registration_ip', null);
    }

    /**
     * Get all capabilities of a user, including those inherited from roles.
     *
     * @param int $userId
     *
     * @return array<string, bool> Associative array of capability => true
     *
     * @example
     * ```php
     * $caps = User::getAllCapabilities(123);
     * if (!empty($caps['edit_posts'])) {
     *     // User can edit posts
     * }
     * ```
     */
    public static function getAllCapabilities(int $userId): array
    {
        // Get WP_User object for the user
        $user = get_userdata($userId);

        // Return empty array if user doesn't exist
        if (!$user) {
            return [];
        }

        // Return all capabilities (role + direct caps)
        return $user->allcaps;
    }

    /**
     * Check if a user has a specific meta key set (regardless of its value).
     *
     * @param int $userId
     * @param string $key Meta key to check
     *
     * @return bool True if meta exists and is not empty, false otherwise
     *
     * @example
     * ```php
     * if (User::hasMeta(123, 'my_custom_key')) {
     *     // Meta key exists and has value
     * }
     * ```
     */
    public static function hasMeta(int $userId, string $key): bool
    {
        // Retrieve meta value, empty string means not set
        $value = get_user_meta($userId, $key, true);

        // Return true if value is not empty string or null
        return $value !== '' && $value !== null;
    }

    /**
     * Safely update multiple capabilities for a user at once.
     *
     * @param int $userId
     * @param array<string, bool> $capabilities Key-value pairs of capability => true/false
     *
     * @return void
     *
     * @example
     * ```php
     * User::setCapabilities(123, ['edit_posts' => true, 'delete_posts' => false]);
     * ```
     */
    public static function setCapabilities(int $userId, array $capabilities): void
    {
        // Get WP_User object
        $user = get_userdata($userId);

        if (!$user) {
            return;
        }

        // Loop through each capability and add or remove accordingly
        foreach ($capabilities as $cap => $grant) {
            if ($grant) {
                $user->add_cap($cap);
            } else {
                $user->remove_cap($cap);
            }
        }
    }

    /**
     * Update user display name.
     *
     * @param int $userId
     * @param string $displayName New display name
     *
     * @return bool True on success, false on failure
     *
     * @example
     * ```php
     * User::setDisplayName(123, 'John D.');
     * ```
     */
    public static function setDisplayName(int $userId, string $displayName): bool
    {
        // Update display_name field via wp_update_user
        $result = wp_update_user([
            'ID'           => $userId,
            'display_name' => $displayName,
        ]);

        // Return true if no error occurred
        return !is_wp_error($result);
    }

    /**
     * Update user's password securely.
     *
     * @param int $userId
     * @param string $newPassword
     *
     * @return bool True if password was updated, false otherwise
     *
     * @example
     * ```php
     * User::updatePassword(123, 'NewPass123!');
     * ```
     */
    public static function updatePassword(int $userId, string $newPassword): bool
    {
        // Use wp_set_password which updates and invalidates sessions
        wp_set_password($newPassword, $userId);

        // Confirm password updated by checking if user exists after update
        return self::exists($userId);
    }

    /**
     * Get user’s roles as a comma-separated string.
     *
     * @param int $userId
     *
     * @return string Roles joined by comma, or empty string if none
     *
     * @example
     * ```php
     * $roles = User::getRolesString(123); // e.g. "administrator,editor"
     * ```
     */
    public static function getRolesString(int $userId): string
    {
        $roles = self::getRoles($userId);

        return $roles ? implode(',', $roles) : '';
    }

    /**
     * Bulk add a capability to multiple users.
     *
     * @param int[] $userIds Array of user IDs
     * @param string $capability Capability to add
     *
     * @return void
     *
     * @example
     * ```php
     * User::bulkAddCapability([123, 124], 'manage_options');
     * ```
     */
    public static function bulkAddCapability(array $userIds, string $capability): void
    {
        foreach ($userIds as $userId) {
            self::addCapability($userId, $capability);
        }
    }

    /**
     * Bulk remove a capability from multiple users.
     *
     * @param int[] $userIds Array of user IDs
     * @param string $capability Capability to remove
     *
     * @return void
     *
     * @example
     * ```php
     * User::bulkRemoveCapability([123, 124], 'edit_posts');
     * ```
     */
    public static function bulkRemoveCapability(array $userIds, string $capability): void
    {
        foreach ($userIds as $userId) {
            self::removeCapability($userId, $capability);
        }
    }

    /**
     * Get user ID by login name.
     *
     * @param string $login Login username to search
     *
     * @return int|null User ID if found, null otherwise
     *
     * @example
     * ```php
     * $userId = User::getIdByLogin('john_doe');
     * ```
     */
    public static function getIdByLogin(string $login): ?int
    {
        $user = get_user_by('login', $login);

        return $user ? $user->ID : null;
    }

    /**
     * Retrieve user meta keys (all keys for a user).
     *
     * @param int $userId
     *
     * @return string[] Array of meta keys or empty array if none
     *
     * @example
     * ```php
     * $keys = User::getMetaKeys(123);
     * ```
     */
    public static function getMetaKeys(int $userId): array
    {
        // Get all user meta for user (returns associative array of key => values)
        $allMeta = get_user_meta($userId);

        // Return only keys as array
        return is_array($allMeta) ? array_keys($allMeta) : [];
    }

    /**
     * Increment a numeric user meta value safely.
     *
     * @param int $userId
     * @param string $key Meta key
     * @param int|float $increment Amount to increment (default 1)
     *
     * @return float|int New meta value after increment
     *
     * @example
     * ```php
     * $newCount = User::incrementMeta(123, 'login_count', 1);
     * ```
     */
    public static function incrementMeta(int $userId, string $key, int|float $increment = 1): int|float
    {
        // Get current meta value, fallback to 0 if not set or non-numeric
        $current = self::getMeta($userId, $key, 0);

        if (!is_numeric($current)) {
            $current = 0;
        }

        // Calculate new value
        $newValue = $current + $increment;

        // Update user meta with new value
        self::setMeta($userId, $key, $newValue);

        return $newValue;
    }

    /**
     * Get user’s nicename (URL-friendly username).
     *
     * @param int $userId
     *
     * @return string|null Nicename or null if user not found
     *
     * @example
     * ```php
     * $nicename = User::getNicename(123);
     * ```
     */
    public static function getNicename(int $userId): ?string
    {
        $user = get_userdata($userId);

        return $user ? $user->user_nicename : null;
    }

    /**
     * Update user nicename.
     *
     * @param int $userId
     * @param string $nicename New nicename (slug)
     *
     * @return bool True on success, false on failure
     *
     * @example
     * ```php
     * User::setNicename(123, 'john-doe');
     * ```
     */
    public static function setNicename(int $userId, string $nicename): bool
    {
        $result = wp_update_user([
            'ID'            => $userId,
            'user_nicename' => $nicename,
        ]);

        return !is_wp_error($result);
    }

    /**
     * Get user’s display capabilities grouped by roles.
     *
     * @param int $userId
     *
     * @return array<string, bool> Array of role_slug => has_role (true/false)
     *
     * @example
     * ```php
     * $roles = User::getRoleMap(123);
     * if ($roles['administrator']) {
     *     // User is administrator
     * }
     * ```
     */
    public static function getRoleMap(int $userId): array
    {
        // Get all registered roles from WP_Roles global
        global $wp_roles;

        $roleMap = [];
        $userRoles = self::getRoles($userId);

        if (!$wp_roles) {
            return $roleMap;
        }

        // Map each role slug to whether the user has it
        foreach ($wp_roles->roles as $roleSlug => $roleInfo) {
            $roleMap[$roleSlug] = in_array($roleSlug, $userRoles, true);
        }

        return $roleMap;
    }

    /**
     * Remove all user meta keys matching a prefix.
     *
     * @param int $userId
     * @param string $prefix Prefix string to match meta keys
     *
     * @return void
     *
     * @example
     * ```php
     * User::removeMetaByPrefix(123, 'temp_');
     * ```
     */
    public static function removeMetaByPrefix(int $userId, string $prefix): void
    {
        // Get all user meta keys
        $keys = self::getMetaKeys($userId);

        // Loop and delete keys matching prefix
        foreach ($keys as $key) {
            if (str_starts_with($key, $prefix)) {
                delete_user_meta($userId, $key);
            }
        }
    }

    /**
     * Get all users whose meta key matches a value pattern (LIKE query).
     *
     * @param string $key Meta key
     * @param string $pattern SQL LIKE pattern (e.g. '%test%')
     *
     * @return \WP_User[] Array of user objects
     *
     * @example
     * ```php
     * $users = User::getByMetaLike('favorite_color', '%blue%');
     * ```
     */
    public static function getByMetaLike(string $key, string $pattern): array
    {
        global $wpdb;

        // Prepare the SQL query to get user IDs where meta_value LIKE pattern
        $sql = $wpdb->prepare(
            "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value LIKE %s",
            $key,
            $pattern
        );

        // Get user IDs matching the query
        $userIds = $wpdb->get_col($sql);

        if (empty($userIds)) {
            return [];
        }

        // Fetch WP_User objects for those IDs
        return get_users(['include' => $userIds]);
    }

    /**
     * Delete all meta for a user (use cautiously).
     *
     * @param int $userId
     *
     * @return void
     *
     * @example
     * ```php
     * User::deleteAllMeta(123);
     * ```
     */
    public static function deleteAllMeta(int $userId): void
    {
        // Get all meta keys for user
        $keys = self::getMetaKeys($userId);

        // Delete each meta key
        foreach ($keys as $key) {
            delete_user_meta($userId, $key);
        }
    }

    /**
     * Count users by meta key existence (users who have a meta key set).
     *
     * @param string $key Meta key to check
     *
     * @return int Number of users with the meta key set
     *
     * @example
     * ```php
     * $count = User::countByMetaKey('special_flag');
     * ```
     */
    public static function countByMetaKey(string $key): int
    {
        global $wpdb;

        // Count distinct user_ids who have the meta key
        $sql = $wpdb->prepare(
            "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->usermeta} WHERE meta_key = %s",
            $key
        );

        return (int) $wpdb->get_var($sql);
    }

    /**
     * Get user IP addresses recorded in meta keys within a date range.
     *
     * @param int $userId
     * @param string $metaKey Meta key where IPs are stored
     * @param \DateTime|null $startDate Optional start date filter
     * @param \DateTime|null $endDate Optional end date filter
     *
     * @return string[] Array of IP addresses found
     *
     * @example
     * ```php
     * $ips = User::getIpsByDateRange(123, 'login_ip', new DateTime('2025-01-01'), new DateTime());
     * ```
     */
    public static function getIpsByDateRange(int $userId, string $metaKey, ?\DateTime $startDate = null, ?\DateTime $endDate = null): array
    {
        global $wpdb;

        $query = "SELECT meta_value, meta_id FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key = %s";
        $params = [$userId, $metaKey];

        // Fetch all matching meta values
        $results = $wpdb->get_results($wpdb->prepare($query, ...$params));

        $ips = [];

        foreach ($results as $row) {
            // Assuming meta_value stores IP or IP with date info (custom structure)
            // If stored as JSON or serialized, decode and filter by date
            $metaValue = maybe_unserialize($row->meta_value);

            // Example: if metaValue is an array with 'ip' and 'date' keys
            if (is_array($metaValue) && isset($metaValue['ip'], $metaValue['date'])) {
                $date = new \DateTime($metaValue['date']);

                if (($startDate === null || $date >= $startDate) && ($endDate === null || $date <= $endDate)) {
                    $ips[] = $metaValue['ip'];
                }
            } elseif (is_string($metaValue)) {
                // If just IP string stored, no date filter applies, just add
                if ($startDate === null && $endDate === null) {
                    $ips[] = $metaValue;
                }
            }
        }

        return $ips;
    }

    /**
     * Set user locale (language) preference.
     *
     * @param int $userId
     * @param string $locale Locale string like 'en_US'
     *
     * @return bool True on success, false on failure
     *
     * @example
     * ```php
     * User::setLocale(123, 'fr_FR');
     * ```
     */
    public static function setLocale(int $userId, string $locale): bool
    {
        // Update user meta for locale preference
        return update_user_meta($userId, 'locale', $locale);
    }

    /**
     * Get user locale preference.
     *
     * @param int $userId
     *
     * @return string|null Locale string or null if not set
     *
     * @example
     * ```php
     * $locale = User::getLocale(123);
     * ```
     */
    public static function getLocale(int $userId): ?string
    {
        return self::getMeta($userId, 'locale', null);
    }

    /**
     * Retrieve a user's primary role (first assigned role).
     *
     * @param int $userId
     *
     * @return string|null Role slug or null if none
     *
     * @example
     * ```php
     * $primaryRole = User::getPrimaryRole(123);
     * ```
     */
    public static function getPrimaryRole(int $userId): ?string
    {
        $roles = self::getRoles($userId);
        return $roles[0] ?? null;
    }

    /**
     * Assign a role to a user (overwrites existing roles).
     *
     * @param int $userId
     * @param string $role Role slug to assign
     *
     * @return void
     *
     * @example
     * ```php
     * User::setRole(123, 'editor');
     * ```
     */
    public static function setRole(int $userId, string $role): void
    {
        $user = get_userdata($userId);
        if (!$user) {
            return;
        }

        // Remove all current roles
        foreach ($user->roles as $existingRole) {
            $user->remove_role($existingRole);
        }

        // Add new role
        $user->add_role($role);
    }

    /**
     * Safely delete a user and optionally reassign their content to another user.
     *
     * @param int $userId ID of the user to delete
     * @param int|null $reassignUserId Optional user ID to reassign posts and content to
     *
     * @return bool True on success, false on failure
     *
     * @example
     * ```php
     * User::deleteUser(123, 1); // Deletes user 123 and reassigns content to user 1
     * ```
     */
    public static function deleteUser(int $userId, ?int $reassignUserId = null): bool
    {
        // If reassignment user ID is invalid or same as deleting user, nullify
        if ($reassignUserId === $userId || ($reassignUserId !== null && !self::exists($reassignUserId))) {
            $reassignUserId = null;
        }

        // Attempt to delete the user using WP function
        $result = wp_delete_user($userId, $reassignUserId);

        // wp_delete_user returns false on failure or WP_User object on success
        return $result !== false;
    }

    /**
     * Check if a user has 2-factor authentication enabled.
     * (Assuming a meta key 'two_factor_enabled' is used for storing this flag)
     *
     * @param int $userId
     *
     * @return bool True if 2FA is enabled, false otherwise
     *
     * @example
     * ```php
     * if (User::hasTwoFactorEnabled(123)) {
     *     // Prompt for 2FA verification
     * }
     * ```
     */
    public static function hasTwoFactorEnabled(int $userId): bool
    {
        // Retrieve 2FA flag stored as meta, default to false
        return (bool) self::getMeta($userId, 'two_factor_enabled', false);
    }

    /**
     * Enable two-factor authentication for a user.
     *
     * @param int $userId
     *
     * @return void
     *
     * @example
     * ```php
     * User::enableTwoFactor(123);
     * ```
     */
    public static function enableTwoFactor(int $userId): void
    {
        // Set meta to true
        self::setMeta($userId, 'two_factor_enabled', true);
    }

    /**
     * Disable two-factor authentication for a user.
     *
     * @param int $userId
     *
     * @return void
     *
     * @example
     * ```php
     * User::disableTwoFactor(123);
     * ```
     */
    public static function disableTwoFactor(int $userId): void
    {
        // Set meta to false
        self::setMeta($userId, 'two_factor_enabled', false);
    }

    /**
     * Get all users with a specific meta key and optional value.
     *
     * @param string $metaKey Meta key to query
     * @param mixed|null $metaValue Optional meta value to match (null means any value)
     *
     * @return \WP_User[] Array of WP_User objects
     *
     * @example
     * ```php
     * $users = User::getUsersByMeta('subscription_status', 'active');
     * ```
     */
    public static function getUsersByMeta(string $metaKey, mixed $metaValue = null): array
    {
        // Prepare meta query array
        $metaQuery = [
            'key' => $metaKey,
        ];

        if ($metaValue !== null) {
            $metaQuery['value'] = $metaValue;
            $metaQuery['compare'] = '=';
        }

        // Query users by meta
        return get_users(['meta_query' => [$metaQuery]]);
    }

    /**
     * Reset user password and send notification email.
     *
     * @param int $userId
     *
     * @return bool True on success, false on failure
     *
     * @example
     * ```php
     * User::resetPasswordWithNotification(123);
     * ```
     */
    public static function resetPasswordWithNotification(int $userId): bool
    {
        $user = get_userdata($userId);
        if (!$user) {
            return false;
        }

        // Generate a random secure password
        $newPassword = wp_generate_password(12, true, true);

        // Update password securely
        if (!self::updatePassword($userId, $newPassword)) {
            return false;
        }

        // Send notification email
        wp_mail(
            $user->user_email,
            'Your password has been reset',
            "Hello {$user->display_name},\n\nYour new password is: {$newPassword}\nPlease change it after logging in."
        );

        return true;
    }

    /**
     * Get users registered within a specific date range.
     *
     * @param string $startDate Start date in 'Y-m-d' format
     * @param string|null $endDate End date in 'Y-m-d' format (defaults to today)
     *
     * @return \WP_User[] Array of WP_User objects
     *
     * @example
     * ```php
     * $newUsers = User::getUsersByRegistrationDate('2025-01-01', '2025-07-01');
     * ```
     */
    public static function getUsersByRegistrationDate(string $startDate, ?string $endDate = null): array
    {
        $endDate = $endDate ?? date('Y-m-d');

        // Query users by registration date range
        return get_users([
            'date_query' => [
                [
                    'after'     => $startDate,
                    'before'    => $endDate,
                    'inclusive' => true,
                    'column'    => 'user_registered',
                ],
            ],
        ]);
    }

    /**
     * Check if a user’s email is verified (assuming a 'email_verified' user meta flag).
     *
     * @param int $userId
     *
     * @return bool True if email is verified, false otherwise
     *
     * @example
     * ```php
     * if (User::isEmailVerified(123)) {
     *     // Allow some feature
     * }
     * ```
     */
    public static function isEmailVerified(int $userId): bool
    {
        return (bool) self::getMeta($userId, 'email_verified', false);
    }

    /**
     * Mark a user’s email as verified.
     *
     * @param int $userId
     *
     * @return void
     *
     * @example
     * ```php
     * User::markEmailVerified(123);
     * ```
     */
    public static function markEmailVerified(int $userId): void
    {
        self::setMeta($userId, 'email_verified', true);
    }

    /**
     * Set a custom user meta flag indicating the user is banned.
     *
     * @param int $userId
     *
     * @return void
     *
     * @example
     * ```php
     * User::banUser(123);
     * ```
     */
    public static function banUser(int $userId): void
    {
        // Set meta key 'is_banned' to true
        self::setMeta($userId, 'is_banned', true);
    }

    /**
     * Remove the banned flag from a user.
     *
     * @param int $userId
     *
     * @return void
     *
     * @example
     * ```php
     * User::unbanUser(123);
     * ```
     */
    public static function unbanUser(int $userId): void
    {
        // Set meta key 'is_banned' to false
        self::setMeta($userId, 'is_banned', false);
    }

    /**
     * Check if a user is banned.
     *
     * @param int $userId
     *
     * @return bool True if banned, false otherwise
     *
     * @example
     * ```php
     * if (User::isBanned(123)) {
     *     // Prevent login or access
     * }
     * ```
     */
    public static function isBanned(int $userId): bool
    {
        // Retrieve banned status from meta, default false
        return (bool) self::getMeta($userId, 'is_banned', false);
    }

    /**
     * Get users banned status filtered.
     *
     * @param bool $banned True for banned users, false for not banned
     *
     * @return \WP_User[] Array of WP_User objects
     *
     * @example
     * ```php
     * $bannedUsers = User::getUsersByBanStatus(true);
     * ```
     */
    public static function getUsersByBanStatus(bool $banned = true): array
    {
        // Query users by meta key 'is_banned' with value 1 or 0
        return get_users([
            'meta_key'   => 'is_banned',
            'meta_value' => $banned ? '1' : '0',
        ]);
    }

    /**
     * Retrieve the user's last login timestamp.
     * Assumes last login timestamp is stored in user meta 'last_login'.
     *
     * @param int $userId
     *
     * @return int|null Unix timestamp of last login or null if not set
     *
     * @example
     * ```php
     * $lastLogin = User::getLastLogin(123);
     * ```
     */
    public static function getLastLogin(int $userId): ?int
    {
        // Get last login time from user meta
        $timestamp = self::getMeta($userId, 'last_login', null);

        // Return timestamp or null if empty
        return $timestamp !== null ? (int)$timestamp : null;
    }

    /**
     * Update the user's last login timestamp to current time.
     *
     * @param int $userId
     *
     * @return void
     *
     * @example
     * ```php
     * User::updateLastLogin(123);
     * ```
     */
    public static function updateLastLogin(int $userId): void
    {
        // Store current Unix timestamp as last login time
        self::setMeta($userId, 'last_login', time());
    }

    /**
     * Retrieve users inactive for more than specified days.
     *
     * @param int $days Number of days of inactivity
     *
     * @return \WP_User[] Array of inactive WP_User objects
     *
     * @example
     * ```php
     * $inactiveUsers = User::getInactiveUsers(30);
     * ```
     */
    public static function getInactiveUsers(int $days): array
    {
        $cutoff = time() - ($days * 86400);

        // Query users with last_login meta less than cutoff OR no last_login meta
        return get_users([
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key'     => 'last_login',
                    'value'   => $cutoff,
                    'compare' => '<',
                    'type'    => 'NUMERIC',
                ],
                [
                    'key'     => 'last_login',
                    'compare' => 'NOT EXISTS',
                ],
            ],
        ]);
    }

    /**
     * Add a custom capability to all users with a specific role.
     *
     * @param string $role Role slug
     * @param string $capability Capability to add
     *
     * @return void
     *
     * @example
     * ```php
     * User::addCapabilityToRole('editor', 'manage_options');
     * ```
     */
    public static function addCapabilityToRole(string $role, string $capability): void
    {
        // Get users with the role
        $users = self::getByRole($role);

        // Loop and add capability
        foreach ($users as $user) {
            $user->add_cap($capability);
        }
    }

    /**
     * Remove a custom capability from all users with a specific role.
     *
     * @param string $role Role slug
     * @param string $capability Capability to remove
     *
     * @return void
     *
     * @example
     * ```php
     * User::removeCapabilityFromRole('author', 'upload_files');
     * ```
     */
    public static function removeCapabilityFromRole(string $role, string $capability): void
    {
        // Get users with the role
        $users = self::getByRole($role);

        // Loop and remove capability
        foreach ($users as $user) {
            $user->remove_cap($capability);
        }
    }

    /**
     * Get the user's IP address from login metadata.
     * Assumes last login IP is stored in user meta 'last_login_ip'.
     *
     * @param int $userId
     *
     * @return string|null IP address or null if not set
     *
     * @example
     * ```php
     * $ip = User::getLastLoginIP(123);
     * ```
     */
    public static function getLastLoginIP(int $userId): ?string
    {
        return self::getMeta($userId, 'last_login_ip', null);
    }

    /**
     * Set the user's last login IP address.
     *
     * @param int $userId
     * @param string $ip IP address to set
     *
     * @return void
     *
     * @example
     * ```php
     * User::setLastLoginIP(123, '192.168.1.1');
     * ```
     */
    public static function setLastLoginIP(int $userId, string $ip): void
    {
        self::setMeta($userId, 'last_login_ip', $ip);
    }

    /**
     * Get all users with a specific user meta key matching a partial value.
     * Uses LIKE comparison for flexible meta search.
     *
     * @param string $metaKey Meta key to search
     * @param string $partialValue Partial value to match (SQL LIKE wildcards allowed)
     *
     * @return \WP_User[] Array of matched users
     *
     * @example
     * ```php
     * $users = User::getUsersByMetaPartial('billing_address', '%New York%');
     * ```
     */
    public static function getUsersByMetaPartial(string $metaKey, string $partialValue): array
    {
        // Meta query with LIKE operator for partial match
        return get_users([
            'meta_query' => [
                [
                    'key'     => $metaKey,
                    'value'   => $partialValue,
                    'compare' => 'LIKE',
                ],
            ],
        ]);
    }

    /**
     * Add a user to multiple roles, preserving existing roles.
     *
     * @param int $userId
     * @param string[] $roles Array of role slugs to add
     *
     * @return void
     *
     * @example
     * ```php
     * User::addRoles(123, ['editor', 'subscriber']);
     * ```
     */
    public static function addRoles(int $userId, array $roles): void
    {
        // Get user object
        $user = get_userdata($userId);
        if (!$user) {
            return;
        }

        // Add each role if not already assigned
        foreach ($roles as $role) {
            if (!in_array($role, $user->roles, true)) {
                $user->add_role($role);
            }
        }
    }

    /**
     * Remove multiple roles from a user.
     *
     * @param int $userId
     * @param string[] $roles Array of role slugs to remove
     *
     * @return void
     *
     * @example
     * ```php
     * User::removeRoles(123, ['subscriber', 'contributor']);
     * ```
     */
    public static function removeRoles(int $userId, array $roles): void
    {
        // Get user object
        $user = get_userdata($userId);
        if (!$user) {
            return;
        }

        // Remove each specified role if user has it
        foreach ($roles as $role) {
            if (in_array($role, $user->roles, true)) {
                $user->remove_role($role);
            }
        }
    }

    /**
     * Fully replace user's roles with specified roles.
     *
     * @param int $userId
     * @param string[] $roles Array of new roles to assign
     *
     * @return void
     *
     * @example
     * ```php
     * User::setRoles(123, ['editor']);
     * ```
     */
    public static function setRoles(int $userId, array $roles): void
    {
        // Get user object
        $user = get_userdata($userId);
        if (!$user) {
            return;
        }

        // Remove all current roles
        foreach ($user->roles as $role) {
            $user->remove_role($role);
        }

        // Add new roles
        foreach ($roles as $role) {
            $user->add_role($role);
        }
    }

    /**
     * Get all users with multiple specified roles.
     *
     * @param string[] $roles Array of role slugs
     *
     * @return \WP_User[] Array of users having at least one of the specified roles
     *
     * @example
     * ```php
     * $users = User::getUsersByRoles(['editor', 'author']);
     * ```
     */
    public static function getUsersByRoles(array $roles): array
    {
        // Query users by multiple roles
        return get_users([
            'role__in' => $roles,
        ]);
    }

    /**
     * Get all users excluding those with specified roles.
     *
     * @param string[] $roles Array of role slugs to exclude
     *
     * @return \WP_User[] Array of users excluding those with the roles
     *
     * @example
     * ```php
     * $users = User::getUsersExcludingRoles(['administrator']);
     * ```
     */
    public static function getUsersExcludingRoles(array $roles): array
    {
        // Query users excluding roles
        return get_users([
            'role__not_in' => $roles,
        ]);
    }

    /**
     * Retrieve user IDs by a custom meta key and value.
     *
     * @param string $metaKey Meta key to query
     * @param mixed $metaValue Meta value to match
     *
     * @return int[] Array of user IDs
     *
     * @example
     * ```php
     * $userIds = User::getUserIdsByMeta('subscription_status', 'active');
     * ```
     */
    public static function getUserIdsByMeta(string $metaKey, mixed $metaValue): array
    {
        // Use WP_User_Query to get IDs only
        $query = new \WP_User_Query([
            'meta_key' => $metaKey,
            'meta_value' => $metaValue,
            'fields' => 'ID',
        ]);

        return $query->get_results();
    }

    /**
     * Add a user to a role only if they don’t already have it.
     *
     * @param int $userId
     * @param string $role Role slug
     *
     * @return void
     *
     * @example
     * ```php
     * User::addRoleIfNotExists(123, 'subscriber');
     * ```
     */
    public static function addRoleIfNotExists(int $userId, string $role): void
    {
        // Get user object
        $user = get_userdata($userId);
        if (!$user) {
            return;
        }

        // Add role if not present
        if (!in_array($role, $user->roles, true)) {
            $user->add_role($role);
        }
    }

    /**
     * Remove all custom capabilities from a user.
     * Only preserves the default role capabilities.
     *
     * @param int $userId
     *
     * @return void
     *
     * @example
     * ```php
     * User::clearCustomCapabilities(123);
     * ```
     */
    public static function clearCustomCapabilities(int $userId): void
    {
        // Get user object
        $user = get_userdata($userId);
        if (!$user) {
            return;
        }

        // Get all capabilities currently assigned to user
        $caps = $user->allcaps;

        // Get capabilities from user's roles (default caps)
        $roleCaps = [];
        foreach ($user->roles as $role) {
            $roleObj = get_role($role);
            if ($roleObj) {
                $roleCaps = array_merge($roleCaps, array_keys($roleObj->capabilities));
            }
        }
        $roleCaps = array_unique($roleCaps);

        // Remove all caps not in roleCaps
        foreach ($caps as $cap => $grant) {
            if (!in_array($cap, $roleCaps, true)) {
                $user->remove_cap($cap);
            }
        }
    }

    /**
     * Safely update a user's email address.
     *
     * @param int $userId
     * @param string $newEmail New email address
     *
     * @return bool True on success, false if invalid email or update fails
     *
     * @example
     * ```php
     * $success = User::updateEmail(123, 'newemail@example.com');
     * ```
     */
    public static function updateEmail(int $userId, string $newEmail): bool
    {
        // Validate email format
        if (!is_email($newEmail)) {
            return false;
        }

        // Update user email
        $result = wp_update_user([
            'ID' => $userId,
            'user_email' => $newEmail,
        ]);

        // Return false if error occurred
        return !is_wp_error($result);
    }

    /**
     * Retrieve all meta keys for a given user.
     *
     * @param int $userId
     *
     * @return string[] Array of meta keys or empty array if none
     *
     * @example
     * ```php
     * $keys = User::getAllMetaKeys(123);
     * ```
     */
    public static function getAllMetaKeys(int $userId): array
    {
        global $wpdb;

        // Query distinct meta keys for the user
        $query = $wpdb->prepare(
            "SELECT DISTINCT meta_key FROM $wpdb->usermeta WHERE user_id = %d",
            $userId
        );

        // Return array of keys or empty array
        return $wpdb->get_col($query) ?: [];
    }

    /**
     * Get user by email address.
     *
     * @param string $email
     *
     * @return \WP_User|null WP_User object or null if not found
     *
     * @example
     * ```php
     * $user = User::getByEmail('user@example.com');
     * ```
     */
    public static function getByEmail(string $email): ?\WP_User
    {
        // Get user by email
        $user = get_user_by('email', $email);

        // Return WP_User or null
        return $user ?: null;
    }

    /**
     * Generate a secure random password.
     *
     * @param int $length Length of the password (default 12)
     *
     * @return string Secure random password
     *
     * @example
     * ```php
     * $password = User::generateRandomPassword(16);
     * ```
     */
    public static function generateRandomPassword(int $length = 12): string
    {
        // Use wp_generate_password to create secure password
        return wp_generate_password($length, true, true);
    }

    /**
     * Reset a user's password and optionally email them the new password.
     *
     * @param int $userId
     * @param bool $notifyEmail Whether to notify user by email (default: true)
     *
     * @return string|null The new password if successful, null on failure
     *
     * @example
     * ```php
     * $newPass = User::resetPassword(123);
     * ```
     */
    public static function resetPassword(int $userId, bool $notifyEmail = true): ?string
    {
        // Get user object
        $user = get_userdata($userId);
        if (!$user) {
            return null;
        }

        // Generate a new secure password
        $newPassword = self::generateRandomPassword();

        // Reset password
        wp_set_password($newPassword, $userId);

        // Notify user by email if requested
        if ($notifyEmail) {
            wp_new_user_notification($user, null, 'password_reset');
        }

        // Return new password
        return $newPassword;
    }

    /**
     * Add or update a user meta with array merging.
     * If meta exists and is an array, merges new data with existing.
     *
     * @param int $userId
     * @param string $key Meta key
     * @param array $newData New array data to merge
     *
     * @return void
     *
     * @example
     * ```php
     * User::mergeMetaArray(123, 'preferences', ['color' => 'blue']);
     * ```
     */
    public static function mergeMetaArray(int $userId, string $key, array $newData): void
    {
        // Get current meta
        $currentData = self::getMeta($userId, $key, []);

        // Ensure current data is array
        if (!is_array($currentData)) {
            $currentData = [];
        }

        // Merge arrays
        $merged = array_merge($currentData, $newData);

        // Update meta
        self::setMeta($userId, $key, $merged);
    }

    /**
     * Count total number of users in the system.
     *
     * @return int Total user count
     *
     * @example
     * ```php
     * $totalUsers = User::countAllUsers();
     * ```
     */
    public static function countAllUsers(): int
    {
        // Use count_users function
        $counts = count_users();

        // Return total users count
        return $counts['total_users'] ?? 0;
    }

    /**
     * Get all meta data for a user.
     *
     * @param int $userId
     *
     * @return array Associative array of all meta key => value pairs
     *
     * @example
     * ```php
     * $allMeta = User::getAllMeta(123);
     * ```
     */
    public static function getAllMeta(int $userId): array
    {
        // Retrieve all meta for user
        $meta = get_user_meta($userId);

        // Since get_user_meta returns array of arrays, simplify to single value or array
        $result = [];
        foreach ($meta as $key => $values) {
            // Return single value if only one, else array
            $result[$key] = count($values) === 1 ? $values[0] : $values;
        }

        return $result;
    }

    /**
     * Get a list of all users sorted by registration date.
     *
     * @param string $order 'ASC' or 'DESC' (default: 'ASC')
     * @param int|null $limit Number of users to return, null for all
     *
     * @return \WP_User[] Array of WP_User objects
     *
     * @example
     * ```php
     * $users = User::getUsersSortedByRegistrationDate('DESC', 10);
     * ```
     */
    public static function getUsersSortedByRegistrationDate(string $order = 'ASC', ?int $limit = null): array
    {
        // Prepare query args
        $args = [
            'orderby' => 'registered',
            'order' => strtoupper($order) === 'DESC' ? 'DESC' : 'ASC',
        ];

        // Add limit if specified
        if ($limit !== null) {
            $args['number'] = $limit;
        }

        // Return users
        return get_users($args);
    }

    /**
     * Create a new user with given data.
     *
     * @param string $username Username (login)
     * @param string $email User email address
     * @param string|null $password Password, if null will be randomly generated
     * @param array $additionalData Additional user data (first_name, last_name, role, etc)
     *
     * @return int|WP_Error User ID on success, WP_Error on failure
     *
     * @example
     * ```php
     * $userId = User::createUser('newuser', 'newuser@example.com', null, ['role' => 'subscriber']);
     * ```
     */
    public static function createUser(string $username, string $email, ?string $password = null, array $additionalData = []): int|\WP_Error
    {
        // Generate random password if not provided
        if ($password === null) {
            $password = self::generateRandomPassword();
        }

        // Prepare user data array
        $userdata = array_merge([
            'user_login' => $username,
            'user_email' => $email,
            'user_pass' => $password,
        ], $additionalData);

        // Create user and return result
        return wp_insert_user($userdata);
    }

    /**
     * Check if an email is already registered.
     *
     * @param string $email
     *
     * @return bool True if email exists, false otherwise
     *
     * @example
     * ```php
     * if (User::emailExists('test@example.com')) {
     *     // Email exists
     * }
     * ```
     */
    public static function emailExists(string $email): bool
    {
        // Use WordPress function to check email existence
        return email_exists($email) !== false;
    }

    /**
     * Get the user's nicename (slug).
     *
     * @param int $userId
     *
     * @return string|null Nicename or null if user not found
     *
     * @example
     * ```php
     * $nicename = User::getUserNicename(123);
     * ```
     */
    public static function getUserNicename(int $userId): ?string
    {
        // Get user object
        $user = get_userdata($userId);

        // Return user_nicename or null
        return $user ? $user->user_nicename : null;
    }

    /**
     * Retrieve users by multiple roles.
     *
     * @param string[] $roles Array of role slugs to filter by
     *
     * @return \WP_User[] Array of WP_User objects matching any of the roles
     *
     * @example
     * ```php
     * $editorsAndAuthors = User::getByRoles(['editor', 'author']);
     * ```
     */
    public static function getByRoles(array $roles): array
    {
        // Return users who have any of the specified roles
        return get_users(['role__in' => $roles]);
    }

    /**
     * Check if a user has any capability from a given set.
     *
     * @param int $userId
     * @param string[] $capabilities Array of capabilities to check
     *
     * @return bool True if user has at least one of the capabilities, false otherwise
     *
     * @example
     * ```php
     * if (User::hasAnyCapability(123, ['edit_posts', 'manage_options'])) {
     *     // Allowed
     * }
     * ```
     */
    public static function hasAnyCapability(int $userId, array $capabilities): bool
    {
        // Get user object
        $user = get_userdata($userId);
        if (!$user) {
            return false;
        }

        // Check each capability, return true if any matches
        foreach ($capabilities as $cap) {
            if ($user->has_cap($cap)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a user has all capabilities from a given set.
     *
     * @param int $userId
     * @param string[] $capabilities Array of capabilities to check
     *
     * @return bool True if user has all capabilities, false otherwise
     *
     * @example
     * ```php
     * if (User::hasAllCapabilities(123, ['edit_posts', 'publish_posts'])) {
     *     // Allowed
     * }
     * ```
     */
    public static function hasAllCapabilities(int $userId, array $capabilities): bool
    {
        // Get user object
        $user = get_userdata($userId);
        if (!$user) {
            return false;
        }

        // Check each capability, return false if any is missing
        foreach ($capabilities as $cap) {
            if (!$user->has_cap($cap)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get users who registered between two dates.
     *
     * @param string $startDate Date string in Y-m-d format or any strtotime-compatible format
     * @param string $endDate Date string in Y-m-d format or any strtotime-compatible format
     *
     * @return \WP_User[] Array of WP_User objects registered within the date range
     *
     * @example
     * ```php
     * $recentUsers = User::getUsersRegisteredBetween('2024-01-01', '2024-06-30');
     * ```
     */
    public static function getUsersRegisteredBetween(string $startDate, string $endDate): array
    {
        // Convert to timestamps for querying
        $start = strtotime($startDate);
        $end = strtotime($endDate);

        // WP_User_Query with date query for user_registered field
        $query = new \WP_User_Query([
            'date_query' => [
                [
                    'after' => date('Y-m-d H:i:s', $start),
                    'before' => date('Y-m-d H:i:s', $end),
                    'inclusive' => true,
                ],
            ],
        ]);

        // Return results as WP_User objects array
        return $query->get_results();
    }

    /**
     * Get total number of users in the system.
     *
     * @return int Total user count
     *
     * @example
     * ```php
     * $totalUsers = User::getTotalUserCount();
     * ```
     */
    public static function getTotalUserCount(): int
    {
        // Use WP_User_Query count feature
        $query = new \WP_User_Query([
            'fields' => 'ID',
        ]);

        // Return total count
        return $query->get_total();
    }

    /**
     * Bulk remove multiple capabilities from a user.
     *
     * @param int $userId
     * @param string[] $capabilities Array of capabilities to remove
     *
     * @return void
     *
     * @example
     * ```php
     * User::removeCapabilities(123, ['edit_posts', 'delete_posts']);
     * ```
     */
    public static function removeCapabilities(int $userId, array $capabilities): void
    {
        // Get user object
        $user = get_userdata($userId);
        if (!$user) {
            return;
        }

        // Remove each capability
        foreach ($capabilities as $cap) {
            $user->remove_cap($cap);
        }
    }

    /**
     * Reset all user capabilities (removes custom caps and resets to role defaults).
     *
     * @param int $userId
     *
     * @return bool True on success, false on failure
     *
     * @example
     * ```php
     * User::resetCapabilities(123);
     * ```
     */
    public static function resetCapabilities(int $userId): bool
    {
        // Get user object
        $user = get_userdata($userId);
        if (!$user) {
            return false;
        }

        // Remove all custom capabilities
        foreach ($user->allcaps as $cap => $granted) {
            $user->remove_cap($cap);
        }

        // Re-add capabilities from roles automatically (WP does this on next capability check)
        // No explicit code needed because WP recalculates caps based on roles

        return true;
    }

    /**
     * Check if a user is logged in (based on ID).
     *
     * @param int $userId
     *
     * @return bool True if the current logged-in user matches given ID
     *
     * @example
     * ```php
     * if (User::isLoggedInUser(123)) {
     *     // Current user is 123
     * }
     * ```
     */
    public static function isLoggedInUser(int $userId): bool
    {
        // Get current logged-in user ID
        $currentUserId = get_current_user_id();

        // Compare with provided user ID
        return $currentUserId === $userId;
    }

    /**
     * Get all users ordered by a user field.
     *
     * @param string $field User field to order by (e.g. 'user_registered', 'display_name')
     * @param string $order Sort order: 'ASC' or 'DESC' (default 'ASC')
     * @param int|null $limit Optional limit on number of users returned
     *
     * @return \WP_User[] Array of WP_User objects
     *
     * @example
     * ```php
     * $users = User::getAllOrderedBy('user_registered', 'DESC', 50);
     * ```
     */
    public static function getAllOrderedBy(string $field, string $order = 'ASC', ?int $limit = null): array
    {
        // Prepare args for WP_User_Query
        $args = [
            'orderby' => $field,
            'order' => $order,
            'number' => $limit,
        ];

        // Query users
        $query = new \WP_User_Query($args);

        // Return result
        return $query->get_results();
    }

    /**
     * Get the total number of users registered within the last N days.
     *
     * @param int $days Number of days to look back
     *
     * @return int Number of users registered in last N days
     *
     * @example
     * ```php
     * $recentUserCount = User::countRegisteredLastDays(30);
     * ```
     */
    public static function countRegisteredLastDays(int $days): int
    {
        // Calculate start date
        $startDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Query users registered after start date
        $query = new \WP_User_Query([
            'date_query' => [
                [
                    'after' => $startDate,
                    'inclusive' => true,
                ],
            ],
            'fields' => 'ID',
        ]);

        // Return total count
        return $query->get_total();
    }
}
