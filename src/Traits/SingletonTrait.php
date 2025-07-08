<?php

namespace MDMasudSikdar\WpKits\Traits;

use RuntimeException;

/**
 * Trait Singleton
 *
 * Provides a reusable implementation of the Singleton design pattern.
 * Classes using this trait will have a globally accessible single instance
 * via the static `init()` method.
 *
 * Usage:
 * ```php
 * class MyClass {
 *     use Singleton;
 *
 *     private function __construct() {
 *         // Initialization logic here
 *     }
 * }
 *
 * $instance = MyClass::init();
 * ```
 *
 * Notes:
 * - The constructor must be declared `private` or `protected` in the using class
 *   to prevent external instantiation.
 * - Cloning and unserialization are explicitly disabled to maintain singleton integrity.
 *
 * @package MDMasudSikdar\WpKits\Traits
 */
trait SingletonTrait
{
  /**
   * Holds the singleton instance of the class.
   */
  private static ?self $instance = null;

  /**
   * Returns the singleton instance of the class.
   * Creates the instance if it doesn't exist.
   *
   * @return static
   */
  public static function init(): static
  {
    if (static::$instance === null) {
      static::$instance = new static();
    }

    return static::$instance;
  }

  /**
   * Disable cloning of the instance.
   *
   * @return void
   */
  private function __clone() {}

  /**
   * Disable unserialization of the instance.
   *
   * @throws RuntimeException Always throws to prevent unserialization.
   */
  final public function __wakeup(): void
  {
    throw new RuntimeException('Cannot unserialize singleton.');
  }
}
