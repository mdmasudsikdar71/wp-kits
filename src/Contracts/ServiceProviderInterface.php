<?php

namespace MDMasudSikdar\WpKits\Contracts;

/**
 * Interface ServiceProviderInterface
 *
 * Defines the contract for all service providers in the plugin.
 *
 * A service provider is responsible for registering and booting
 * a specific section of the plugin (e.g., Admin, Frontend, Shared services).
 *
 * - The `register()` method should bind hooks, filters, or services.
 * - The `boot()` method should handle post-registration logic,
 *   such as initializing services or deferred tasks.
 *
 * Inspired by Laravel's service provider pattern, adapted for WordPress.
 *
 * ## Example Implementation
 *
 * ```php
 * use MDMasudSikdar\WpKits\Contracts\ServiceProviderInterface;
 *
 * class AdminServiceProvider implements ServiceProviderInterface
 * {
 *     public function register(): void
 *     {
 *         // Bind WordPress hooks and filters
 *         add_action('init', function () {
 *             // Register custom post types or enqueue admin scripts
 *         });
 *
 *         add_filter('manage_posts_columns', function ($columns) {
 *             $columns['custom'] = __('Custom Column', 'textdomain');
 *             return $columns;
 *         });
 *     }
 *
 *     public function boot(): void
 *     {
 *         // Perform post-registration tasks
 *         // For example: load REST API controllers or initialize services
 *         add_action('admin_enqueue_scripts', function () {
 *             wp_enqueue_style('my-admin-css', plugin_dir_url(__FILE__) . 'css/admin.css');
 *         });
 *     }
 * }
 * ```
 *
 * @package MDMasudSikdar\WpKits\Contracts
 */
interface ServiceProviderInterface
{
    /**
     * Register hooks, filters, or service bindings.
     *
     * This method should only contain setup/registration logic.
     * Avoid executing logic that depends on later WordPress lifecycle
     * stages (e.g., `init`, `wp_loaded`) directly — instead, hook into them.
     *
     * Example: add_action(), add_filter(), register_post_type(), etc.
     *
     * @return void
     */
    public function register(): void;

    /**
     * Execute logic after all providers have been registered.
     *
     * Useful for:
     * - Deferred initialization
     * - Bootstrapping services
     * - Loading assets, routes, or controllers
     *
     * This method runs later than `register()` and is intended
     * for logic that depends on all providers being set up.
     *
     * @return void
     */
    public function boot(): void;
}
