<?php

namespace MDMasudSikdar\WpKits\RestApi\Controllers;

use InvalidArgumentException;
use MDMasudSikdar\WpKits\RestApi\RestApi;

/**
 * Abstract Controller
 *
 * Base class for all REST API controllers in the plugin.
 *
 * Features:
 * ✅ Unified JSON response formatting via `respond()`
 * ✅ Parameter validation via `validateParams()`
 * ✅ Consistent HTTP status codes and headers
 * ✅ Supports WP_REST_Request and WP_REST_Response
 *
 * Controllers should extend this class to implement plugin-specific endpoints
 * and always use the provided helpers to ensure consistent responses.
 *
 * @package MDMasudSikdar\WpKits\RestApi\Controllers
 */
abstract class Controller
{
    /**
     * Return a standardized REST API response.
     *
     * Wraps data or error messages in a consistent JSON structure.
     * Preserves WP_REST_Response headers and status if already provided.
     *
     * @param mixed $payload The data or error message to return.
     * @param bool $success Whether the request is successful (default: true).
     * @param int $status HTTP status code (default: 200).
     * @param array $headers Optional headers to include in the response.
     * @return \WP_REST_Response Standardized REST response object.
     *
     * @example
     * ```php
     * return $this->respond(['user' => $userData], true, 200);
     * return $this->respond('Not Found', false, 404);
     * return $this->respond(['message'=>'Hello'], true, 200, ['X-Custom' => 'WPBP']);
     * ```
     */
    protected function respond(
        mixed $payload,
        bool $success = true,
        int $status = 200,
        array $headers = []
    ): \WP_REST_Response {
        // Delegate the response creation to RestApi helper
        return RestApi::response($payload, $success, $status, $headers);
    }

    /**
     * Validate required parameters from a WP_REST_Request.
     *
     * Checks each key in $params exists in the request.
     * Returns an associative array of parameter values.
     * Throws an InvalidArgumentException if a required parameter is missing.
     *
     * @param \WP_REST_Request $request The WordPress REST request object.
     * @param array $params List of required parameter keys.
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
        // Initialize resulting data array
        $data = [];

        // Loop over each required parameter
        foreach ($params as $param) {
            // Retrieve the parameter value from the request
            $value = $request->get_param($param);

            // Throw exception if the parameter is missing
            if ($value === null) {
                throw new InvalidArgumentException("Missing required parameter: {$param}");
            }

            // Add the validated parameter to the result
            $data[$param] = $value;
        }

        // Return all validated parameters as an associative array
        return $data;
    }
}
