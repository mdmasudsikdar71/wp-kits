<?php

namespace MDMasudSikdar\WpKits\Traits;

use MDMasudSikdar\WpKits\Helpers\ApiClient;
use RuntimeException;

/**
 * Trait ApiClientAwareTrait
 *
 * Provides a robust way for service providers to create, store, and reuse ApiClient instances.
 *
 * Features:
 * ✅ Fluent ApiClient initialization
 * ✅ Multiple named clients support
 * ✅ Automatic internal storage for reuse
 * ✅ Optional logging and error handling
 *
 * Example usage:
 * ```php
 * class TwilioProvider {
 *     use ApiClientAwareTrait;
 *
 *     public function sendSms(array $settings, array $payload): void
 *     {
 *         // Initialize the client the first time with an initializer
 *         $api = $this->client('twilio', function() use ($settings) {
 *             return $this->initClient(
 *                 'https://api.twilio.com/2010-04-01',
 *                 [
 *                     'Authorization' => 'Basic ' . base64_encode("{$settings['account_sid']}:{$settings['auth_token']}"),
 *                     'Content-Type'  => 'application/x-www-form-urlencoded',
 *                 ]
 *             );
 *         });
 *
 *         // Reuse stored client for subsequent calls
 *         $response = $this->client('twilio')->post("Accounts/{$settings['account_sid']}/Messages.json", [
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
     * Internal storage of ApiClient instances keyed by name.
     *
     * @var array<string, ApiClient>
     */
    protected array $clients = [];

    /**
     * Initialize or retrieve a named ApiClient.
     *
     * If the client with the given name exists, it is returned.
     * If not, the initializer callback is called to create and store the client.
     *
     * @param string $name Unique client name
     * @param callable|null $initializer Callback returning ApiClient (required first time)
     * @return ApiClient
     *
     * @throws RuntimeException If client does not exist and initializer is not provided
     *
     * @example
     * ```php
     * // Initialize first time
     * $client = $this->client('twilio', fn() => $this->initClient('https://api.example.com', ['Authorization'=>'Bearer TOKEN']));
     *
     * // Reuse client later without initializer
     * $client = $this->client('twilio');
     * ```
     */
    protected function client(string $name, ?callable $initializer = null): ApiClient
    {
        // Check if client is already stored
        if (isset($this->clients[$name])) {
            return $this->clients[$name];
        }

        // If not stored, initializer must be provided
        if (!$initializer) {
            throw new RuntimeException("ApiClient '{$name}' is not initialized. Provide an initializer callback.");
        }

        // Call the initializer and store the ApiClient
        $this->clients[$name] = $initializer();

        return $this->clients[$name];
    }

    /**
     * Initialize a new ApiClient instance.
     *
     * This method handles setting the base URL, default headers, and optional configuration.
     *
     * @param string $baseUrl Base URL for API requests
     * @param array $headers Optional default headers
     * @param array $config Optional configuration options:
     *                      - timeout (int): seconds for request timeout
     *                      - max_retries (int): retry attempts if request fails
     *                      - retry_delay (int): delay in milliseconds between retries
     *                      - throw_on_error (bool): whether to throw exceptions on request failure
     * @return ApiClient Fully-configured ApiClient instance
     *
     * @example
     * ```php
     * $api = $this->initClient(
     *     'https://api.example.com',
     *     ['Authorization'=>'Bearer TOKEN'],
     *     ['timeout'=>10, 'max_retries'=>3]
     * );
     * ```
     */
    protected function initClient(string $baseUrl, array $headers = [], array $config = []): ApiClient
    {
        // Merge headers into configuration array
        $clientConfig = array_merge(['headers' => $headers], $config);

        // Create and return the ApiClient instance
        return new ApiClient($baseUrl, $clientConfig);
    }

    /**
     * Retrieve a previously stored client by name without initializing.
     *
     * Returns null if the client does not exist.
     *
     * @param string $name Client name
     * @return ApiClient|null
     *
     * @example
     * ```php
     * $api = $this->getClient('twilio');
     * if($api) { $api->get('Users'); }
     * ```
     */
    protected function getClient(string $name): ?ApiClient
    {
        // Return stored client or null
        return $this->clients[$name] ?? null;
    }

    /**
     * Remove a stored client by name.
     *
     * @param string $name Client name
     * @return void
     *
     * @example
     * ```php
     * $this->removeClient('twilio');
     * ```
     */
    protected function removeClient(string $name): void
    {
        // Remove client from storage
        unset($this->clients[$name]);
    }

    /**
     * Clear all stored ApiClient instances.
     *
     * @return void
     *
     * @example
     * ```php
     * $this->clearClients();
     * ```
     */
    protected function clearClients(): void
    {
        // Reset clients array
        $this->clients = [];
    }
}
