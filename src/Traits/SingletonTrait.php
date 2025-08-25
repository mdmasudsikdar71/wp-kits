<?php

namespace MDMasudSikdar\WpKits\Traits;

use RuntimeException;

/**
 * Trait SingletonTrait
 *
 * Provides a robust, reusable singleton implementation.
 *
 * Features:
 * ✅ Global accessible instance via `init()`
 * ✅ Optional initialization parameters
 * ✅ Lazy initialization support via callback
 * ✅ Prevents cloning and unserialization
 * ✅ Throws meaningful exceptions for misuse
 *
 * Usage:
 * ```php
 * class MyClass {
 *     use SingletonTrait;
 *
 *     private function __construct(array $config = []) {
 *         // initialization logic
 *     }
 * }
 *
 * $instance = MyClass::init(['foo' => 'bar']);
 * ```
 *
 * Notes:
 * - Constructor must be private or protected to prevent external instantiation.
 * - Cloning and unserialization are disabled.
 *
 * @package MDMasudSikdar\WpKits\Traits
 */
trait SingletonTrait
{
    /**
     * Holds the singleton instance of the class.
     *
     * @var static|null
     */
    private static ?self $instance = null;

    /**
     * Returns the singleton instance of the class.
     * Creates it on the first call using optional constructor parameters.
     *
     * @param array $params Optional parameters passed to constructor on first init.
     * @param callable|null $initCallback Optional callback executed after initialization.
     * @return static
     *
     * @throws RuntimeException If init is called again with different params.
     */
    final public static function init(array $params = [], ?callable $initCallback = null): static
    {
        if (static::$instance === null) {
            static::$instance = new static(...$params);

            if ($initCallback !== null) {
                $initCallback(static::$instance);
            }
        } elseif (!empty($params)) {
            throw new RuntimeException(sprintf(
                'Singleton of class %s already initialized; cannot pass parameters again.',
                static::class
            ));
        }

        return static::$instance;
    }

    /**
     * Prevent cloning of the singleton instance.
     *
     * @return void
     */
    private function __clone(): void {}

    /**
     * Prevent unserialization of the singleton instance.
     *
     * @throws RuntimeException Always throws to prevent unserialization.
     */
    final public function __wakeup(): void
    {
        throw new RuntimeException(sprintf(
            'Cannot unserialize singleton instance of class %s.',
            static::class
        ));
    }
}
