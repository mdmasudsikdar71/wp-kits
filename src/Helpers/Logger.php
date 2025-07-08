<?php

namespace MDMasudSikdar\WpKits\Helpers;

/**
 * Class Logger
 *
 * Provides simple logging utilities to write messages to the WordPress debug log.
 * Logging only occurs if `WP_DEBUG` is enabled.
 *
 * ### Usage
 * ```php
 * use MDMasudSikdar\WpKits\Helpers\Logger;
 *
 * Logger::info('Something informative happened.');
 * Logger::debug('Useful debug details.');
 * Logger::warning('This might be a problem.');
 * Logger::error('Something went wrong.');
 * ```
 *
 * Logs are sent to the PHP error log (usually `wp-content/debug.log` if `WP_DEBUG_LOG` is true).
 *
 * @package MDMasudSikdar\WpKits\Helpers
 */
class Logger
{
  /**
   * Logs an informational message.
   *
   * @param string $message The message to log.
   * @return void
   */
  public static function info(string $message): void
  {
    self::log('INFO', $message);
  }

  /**
   * Logs a debug message. Helpful during development.
   *
   * @param string $message The message to log.
   * @return void
   */
  public static function debug(string $message): void
  {
    self::log('DEBUG', $message);
  }

  /**
   * Logs a warning message.
   *
   * @param string $message The message to log.
   * @return void
   */
  public static function warning(string $message): void
  {
    self::log('WARNING', $message);
  }

  /**
   * Logs an error message.
   *
   * @param string $message The message to log.
   * @return void
   */
  public static function error(string $message): void
  {
    self::log('ERROR', $message);
  }

  /**
   * Core logging method that writes the message to the PHP error log.
   * Will only execute if WP_DEBUG is enabled.
   *
   * @param string $level   The log level (e.g., 'INFO', 'ERROR').
   * @param string $message The message to log.
   * @return void
   */
  protected static function log(string $level, string $message): void
  {
    if (defined('WP_DEBUG') && WP_DEBUG) {
      $class = static::class;
      error_log(sprintf('[%s] [%s] %s', $level, $class, $message));
    }
  }
}
