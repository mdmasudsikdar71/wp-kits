<?php

namespace MDMasudSikdar\WpKits\Contracts;

/**
 * Interface ServiceProviderInterface
 *
 * Contract for all service providers used in the plugin.
 * Each provider must handle its own registration and optional boot logic.
 *
 * Implementations should bind hooks, filters, or services
 * in `register()`, and perform post-registration logic in `boot()`.
 *
 * Usage:
 * ```php
 * use MDMasudSikdar\WpKits\Contracts\ServiceProviderInterface;
 *
 * class CustomServiceProvider implements ServiceProviderInterface {
 *     public function register(): void {
 *         add_action('init', function () {
 *             // Register custom post types or scripts
 *         });
 *     }
 *
 *     public function boot(): void {
 *         // Load assets, REST controllers, etc.
 *     }
 * }
 * ```
 *
 * @since 1.0.0
 * @package MDMasudSikdar\WpKits\Contracts
 */
interface ServiceProviderInterface
{
  /**
   * Register hooks, filters, or bindings.
   *
   * This method should only contain registration logic.
   * Avoid calling WordPress functions that depend on actions like 'init' unless hooked.
   *
   * @return void
   */
  public function register(): void;

  /**
   * Execute logic after all services are registered.
   *
   * Useful for deferred actions, post-registration initialization, or late bootstrapping.
   *
   * @return void
   */
  public function boot(): void;
}
