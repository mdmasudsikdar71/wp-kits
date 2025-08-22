<?php

namespace MDMasudSikdar\WpKits\Providers;

use MDMasudSikdar\WpKits\Contracts\ServiceProviderInterface;

/**
 * Class ServiceProvider
 *
 * Manages the lifecycle of all service providers in the plugin.
 *
 * Each service provider encapsulates a section of the plugin
 * (e.g., Admin functionality, Frontend functionality, Shared services).
 * The loader is responsible for:
 *
 * - Registering service providers (`register()` method).
 * - Booting them after registration (`boot()` method).
 *
 * Inspired by Laravel's service provider system, adapted for WordPress.
 *
 * ## Example Usage
 *
 * ```php
 * use MDMasudSikdar\WpKits\Providers\ServiceProvider;
 * use MDMasudSikdar\WpKits\Admin\AdminServiceProvider;
 * use MDMasudSikdar\WpKits\Frontend\FrontendServiceProvider;
 *
 * $loader = new ServiceProvider();
 *
 * // Register service providers
 * $loader->register(new AdminServiceProvider());
 * $loader->register(new FrontendServiceProvider());
 *
 * // Boot them (typically after WordPress is loaded)
 * add_action('plugins_loaded', fn() => $loader->boot());
 * ```
 *
 * @package MDMasudSikdar\WpKits\Providers
 */
class ServiceProvider
{
    /**
     * The list of registered service providers.
     *
     * @var ServiceProviderInterface[]
     */
    private array $providers = [];

    /**
     * Register a service provider and immediately call its `register()` method.
     *
     * The `register()` method should contain logic to bind WordPress hooks,
     * filters, or services. This happens early in the plugin lifecycle.
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
     * Boot all registered service providers.
     *
     * The `boot()` method is intended for post-registration logic,
     * such as initializing services, loading routes, or late hook bindings.
     *
     * @return void
     */
    public function boot(): void
    {
        foreach ($this->providers as $provider) {
            $provider->boot();
        }
    }
}
