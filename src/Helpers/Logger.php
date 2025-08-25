<?php

namespace MDMasudSikdar\WpKits\Helpers;

/**
 * Class Logger
 *
 * Advanced logging utility for WordPress.
 * Logs messages with timestamps, severity levels, context data, backtrace information,
 * and supports writing to both PHP error log and a custom log file.
 *
 * @package MDMasudSikdar\WpKits\Helpers
 */
class Logger
{
    /**
     * Supported log levels.
     *
     * @var array<string>
     */
    protected static array $levels = ['DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL'];

    /**
     * Logs an informational message.
     *
     * @param string $message The message to log.
     * @param array $context Optional array of additional context information.
     *
     * @return void
     *
     * @example
     * ```php
     * Logger::info('User logged in', ['user_id' => 123]);
     * ```
     */
    public static function info(string $message, array $context = []): void
    {
        self::log('INFO', $message, $context);
    }

    /**
     * Logs a debug message.
     *
     * @param string $message The message to log.
     * @param array $context Optional array of additional context information.
     *
     * @return void
     *
     * @example
     * ```php
     * Logger::debug('Fetching user data', ['user_id' => 123, 'query_time' => 0.23]);
     * ```
     */
    public static function debug(string $message, array $context = []): void
    {
        self::log('DEBUG', $message, $context);
    }

    /**
     * Logs a warning message.
     *
     * @param string $message The message to log.
     * @param array $context Optional array of additional context information.
     *
     * @return void
     *
     * @example
     * ```php
     * Logger::warning('Payment gateway response delayed', ['order_id' => 456]);
     * ```
     */
    public static function warning(string $message, array $context = []): void
    {
        self::log('WARNING', $message, $context);
    }

    /**
     * Logs an error message.
     *
     * @param string $message The message to log.
     * @param array $context Optional array of additional context information.
     *
     * @return void
     *
     * @example
     * ```php
     * Logger::error('Failed to send email', ['email' => 'user@example.com']);
     * ```
     */
    public static function error(string $message, array $context = []): void
    {
        self::log('ERROR', $message, $context);
    }

    /**
     * Logs a critical message.
     *
     * @param string $message The message to log.
     * @param array $context Optional array of additional context information.
     *
     * @return void
     *
     * @example
     * ```php
     * Logger::critical('Database connection lost', ['host' => 'localhost', 'db' => 'mydb']);
     * ```
     */
    public static function critical(string $message, array $context = []): void
    {
        self::log('CRITICAL', $message, $context);
    }

    /**
     * Core logging method used internally by all public methods.
     *
     * @param string $level The log level (DEBUG, INFO, WARNING, ERROR, CRITICAL)
     * @param string $message The log message
     * @param array $context Optional context array
     *
     * @return void
     *
     * @internal
     */
    protected static function log(string $level, string $message, array $context = []): void
    {
        // Only log if WP_DEBUG is defined and true
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        // Ensure the provided log level is valid
        if (!in_array($level, self::$levels, true)) {
            $level = 'INFO';
        }

        // Current timestamp
        $timestamp = date('Y-m-d H:i:s');

        // Current class name
        $class = static::class;

        // Prepare context data as pretty JSON if provided
        $contextStr = '';
        if (!empty($context)) {
            $contextStr = ' | Context: ' . json_encode($context, JSON_PRETTY_PRINT);
        }

        // Capture backtrace to determine where the log was triggered
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $location = '';
        if (isset($trace[1])) {
            $location = sprintf(' [%s:%d]', $trace[1]['file'], $trace[1]['line']);
        }

        // Build the final log message
        $logMessage = sprintf("[%s] [%s] [%s]%s %s", $timestamp, $level, $class, $location, $message . $contextStr);

        // Write to PHP error log (wp-content/debug.log if WP_DEBUG_LOG is true)
        error_log($logMessage);

        // Write to a custom log file in wp-content
        $logFile = WP_CONTENT_DIR . '/advanced-debug.log';
        @file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
