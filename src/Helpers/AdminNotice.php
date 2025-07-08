<?php

namespace MDMasudSikdar\WpKits\Helpers;

/**
 * Class AdminNotice
 *
 * Utility class to display styled admin notices in the WordPress dashboard.
 *
 * ✅ Supports notice types: 'success', 'error', 'warning', 'info'
 * ✅ Automatically hooks into `admin_notices`
 * ✅ Optionally dismissible
 *
 * ### Usage:
 * ```php
 * use MDMasudSikdar\WpKits\Helpers\AdminNotice;
 *
 * AdminNotice::add('Settings saved successfully.', 'success');
 * AdminNotice::add('Something went wrong.', 'error', false);
 * AdminNotice::add('This is an info message.', 'info');
 * AdminNotice::add('Please double-check your input.', 'warning');
 * ```
 *
 * @package MDMasudSikdar\WpKits\Helpers
 */
class AdminNotice
{
  /**
   * Display an admin notice in the WordPress dashboard.
   *
   * @param string $message       The message to display.
   * @param string $type          The type of message: 'success', 'error', 'warning', 'info'. Default 'info'.
   * @param bool   $dismissible   Whether the notice can be dismissed. Default true.
   *
   * @return void
   */
  public static function add(string $message, string $type = 'info', bool $dismissible = true): void
  {
    add_action('admin_notices', function () use ($message, $type, $dismissible) {
      $allowedTypes = ['success', 'error', 'warning', 'info'];
      $type = in_array($type, $allowedTypes, true) ? $type : 'info';

      $classes = ['notice', "notice-{$type}"];
      if ($dismissible) {
        $classes[] = 'is-dismissible';
      }

      printf(
        '<div class="%s"><p>%s</p></div>',
        esc_attr(implode(' ', $classes)),
        wp_kses_post($message)
      );
    });
  }
}
