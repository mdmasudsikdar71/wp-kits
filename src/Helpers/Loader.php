<?php

namespace MDMasudSikdar\WpKits\Helpers;

use MDMasudSikdar\WpKits\Contracts\ServiceProviderInterface;

/**
 * Class Loader
 *
 * Manages the lifecycle of all service providers in the plugin.
 * Each provider handles a section of the plugin (e.g., Admin, Frontend, Shared).
 *
 * The Loader is responsible for:
 * - Registering service providers (binding hooks, filters, etc.)
 * - Booting them after initial registration (if boot logic exists)
 *
 * Usage:
 * ```php
 * use MDMasudSikdar\WpKits\Core\Loader;
 * use MDMasudSikdar\WpKits\Admin\AdminServiceProvider;
 *
 * $loader = new Loader();
 * $loader->register(new AdminServiceProvider());
 * $loader->boot();
 * ```
 *
 * @package MDMasudSikdar\WpKits\Helpers
 */
class Loader
{
  /**
   * The list of registered service providers.
   *
   * @var ServiceProviderInterface[]
   */
  private array $providers = [];

  /**
   * Register a service provider and immediately invoke its register() method.
   *
   * The register() method is expected to hook into WordPress
   * (e.g., using add_action, add_filter).
   *
   * @param ServiceProviderInterface $provider The service provider instance.
   * @return void
   */
  public function register(ServiceProviderInterface $provider): void
  {
    $provider->register();
    $this->providers[] = $provider;
  }

  /**
   * Boot all registered providers that implement a boot() method.
   *
   * This is typically used for post-initialization logic
   * like loading routes, services, or deferred hooks.
   *
   * @return void
   */
  public function boot(): void
  {
    if (empty($this->providers)) {
      return;
    }

    foreach ($this->providers as $provider) {
      if (method_exists($provider, 'boot')) {
        $provider->boot();
      }
    }
  }
}
