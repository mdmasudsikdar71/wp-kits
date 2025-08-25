<?php

namespace MDMasudSikdar\WpKits\Contracts;

/**
 * Interface ServiceProviderInterface
 *
 * Defines the contract for a service provider in a WordPress plugin.
 *
 * Features:
 * ✅ Standardized plugin service registration pattern
 * ✅ Clear separation of registration and post-registration logic
 * ✅ Supports Admin, Frontend, and shared service areas
 * ✅ Inspired by Laravel's service provider pattern for maintainable WordPress code
 *
 * Responsibilities:
 * 1. `register()` - Bind hooks, filters, custom post types, taxonomies, or services.
 * 2. `boot()` - Execute logic after all providers are registered, including deferred tasks.
 *
 * @package MDMasudSikdar\WpKits\Contracts
 */
interface ServiceProviderInterface
{
    /**
     * Register hooks, filters, or service bindings.
     *
     * This method should only handle setup/registration logic.
     * Avoid running tasks that depend on other providers at this stage —
     * instead, use WordPress hooks like `add_action()` or `add_filter()`.
     *
     * Example usage:
     * ```php
     * add_action('init', function () {
     *     // Register a custom post type
     *     register_post_type('my_custom_type', ['public' => true]);
     * });
     *
     * add_filter('manage_posts_columns', function ($columns) {
     *     // Add a custom admin column
     *     $columns['custom'] = __('Custom Column', 'textdomain');
     *     return $columns;
     * });
     * ```
     *
     * @return void
     */
    public function register(): void;

    /**
     * Execute logic after all providers have been registered.
     *
     * Use this method for:
     * - Deferred initialization that depends on other providers
     * - Enqueueing scripts, styles, or registering REST API routes
     * - Bootstrapping services that rely on fully initialized providers
     *
     * Example usage:
     * ```php
     * add_action('admin_enqueue_scripts', function () {
     *     // Enqueue admin CSS after all providers are registered
     *     wp_enqueue_style('my-admin-css', plugin_dir_url(__FILE__) . 'css/admin.css');
     * });
     * ```
     *
     * @return void
     */
    public function boot(): void;
}
