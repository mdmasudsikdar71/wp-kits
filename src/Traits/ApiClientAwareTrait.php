<?php

namespace MDMasudSikdar\WpKits\Traits;

use MDMasudSikdar\WpKits\Helpers\ApiClient;

/**
 * Trait ApiClientAwareTrait
 *
 * Provides a simple way for any service provider to create and use an ApiClient.
 *
 * Usage:
 * ```php
 * class TwilioProvider {
 *     use ApiClientAwareTrait;
 *
 *     public function sendSms(array $settings, array $payload): void
 *     {
 *         $api = $this->initClient(
 *             'https://api.twilio.com/2010-04-01',
 *             [
 *                 'Authorization' => 'Basic ' . base64_encode("{$settings['account_sid']}:{$settings['auth_token']}"),
 *                 'Content-Type'  => 'application/x-www-form-urlencoded',
 *             ]
 *         );
 *
 *         $response = $api->post("Accounts/{$settings['account_sid']}/Messages.json", [
 *             'To'   => $payload['to'],
 *             'From' => $settings['from'],
 *             'Body' => $payload['message'],
 *         ]);
 *
 *         if (!$response['success']) {
 *             error_log("SMS sending failed: " . ($response['error'] ?? 'Unknown error'));
 *         }
 *     }
 * }
 * ```
 *
 * @package MDMasudSikdar\WpKits\Traits
 */
trait ApiClientAwareTrait
{
  /**
   * The initialized ApiClient instance (if assigned).
   *
   * @var ApiClient
   */
  protected ApiClient $client;

  /**
   * Initialize a new ApiClient instance.
   *
   * @param string $baseUrl The base URL of the API.
   * @param array $headers Optional default headers for all requests.
   * @param array $config Optional configuration: timeout, max_retries, retry_delay, etc.
   * @return ApiClient Returns a fully-configured ApiClient instance.
   */
  protected function initClient(string $baseUrl, array $headers = [], array $config = []): ApiClient
  {
    $clientConfig = array_merge([
      'headers' => $headers
    ], $config);

    return new ApiClient($baseUrl, $clientConfig);
  }
}
