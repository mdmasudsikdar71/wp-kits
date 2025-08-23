<?php

namespace MDMasudSikdar\WpKits\RestApi\Controllers;

use InvalidArgumentException;
use MDMasudSikdar\WpKits\RestApi\RestApi;

/**
 * Abstract Controller
 *
 * Base class for all REST API controllers in the plugin.
 * Provides:
 * - Unified response formatting
 * - Parameter validation
 *
 * Controllers should extend this class and implement their endpoints
 * using the respond() helper for consistent JSON responses.
 *
 * @package MDMasudSikdar\WpKits\Http\Controllers
 *
 * @example
 * ```php
 * class HelloController extends Controller
 * {
 *     public function index(): WP_REST_Response
 *     {
 *         return $this->respond([
 *             'message' => 'Hello World'
 *         ]);
 *     }
 *
 *     public function store(WP_REST_Request $request): WP_REST_Response
 *     {
 *         // Validate 'name' parameter from request
 *         $data = $this->validateParams($request, ['name']);
 *
 *         return $this->respond([
 *             'greeting' => "Hello, {$data['name']}!"
 *         ]);
 *     }
 * }
 * ```
 */
abstract class Controller
{
    /**
     * Unified response helper.
     *
     * Wraps any data in a standardized JSON structure.
     * Preserves WP_REST_Response headers and status if already provided.
     *
     * @param mixed $payload Data or error message to return.
     * @param bool $success Whether the request was successful.
     * @param int $status HTTP status code (default 200).
     * @param array $headers Optional headers to send.
     *
     * @return \WP_REST_Response
     *
     * @example
     * ```php
     * return $this->respond(['user' => $userData], true, 200, ['X-Custom' => 'WPBP']);
     * ```
     */
    protected function respond(
        mixed $payload,
        bool $success = true,
        int $status = 200,
        array $headers = []
    ): \WP_REST_Response {
        return RestApi::response($payload, $success, $status, $headers);
    }

    /**
     * Validate required parameters from a WP_REST_Request object.
     *
     * Iterates over the required keys, ensures they exist in the request,
     * and returns an associative array of parameter values.
     * Throws an InvalidArgumentException if a required parameter is missing.
     *
     * @param \WP_REST_Request $request The WordPress REST request object.
     * @param array $params List of required parameter keys to validate.
     * @return array Associative array of validated parameters.
     *
     * @throws InvalidArgumentException If any required parameter is missing.
     *
     * @example
     * ```php
     * $data = $this->validateParams($request, ['name', 'email']);
     * // $data = ['name' => 'John', 'email' => 'john@example.com']
     * ```
     */
    protected function validateParams(\WP_REST_Request $request, array $params): array
    {
        $data = [];

        // Loop over all required parameter keys
        foreach ($params as $param) {
            // Get the parameter value from the request
            $value = $request->get_param($param);

            // Throw exception if the parameter is missing
            if ($value === null) {
                throw new InvalidArgumentException("Missing required parameter: {$param}");
            }

            // Store the parameter in the resulting data array
            $data[$param] = $value;
        }

        // Return all validated parameters
        return $data;
    }
}
