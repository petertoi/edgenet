<?php
/**
 * Filename class-api.php
 *
 * @package edgenet
 * @author  Peter Toi <peter@petertoi.com>
 */

namespace Edgenet;

/**
 * Class API
 *
 * Handles Edgenet API calls.
 *
 * @package Edgenet
 * @author  Peter Toi <peter@petertoi.com>
 * @version 1.0.0
 */
class API {

	const METHOD_GET  = 'GET';
	const METHOD_POST = 'POST';
	const METHOD_HEAD = 'HEAD';

	const HTTP_VERSION_1_1 = '1.1';

	const CONTENT_TYPE_JSON = 'application/json';

	const BASE_URL = 'https://api.edgenet.com:443/';

	/**
	 * API constructor.
	 */
	public function __construct() {
	}

	/**
	 * Send Edgenet API request.
	 *
	 * @param string $endpoint API endpoint path (will be appended to self::BASE_URL).
	 * @param string $method   HTTP Method, supported: GET, POST, HEAD.
	 * @param array  $headers  Request headers.
	 * @param array  $data     Data to send to endpoint in request body. Will be JSON encoded.
	 *
	 * @return \WP_Error|array The response or WP_Error on failure.
	 */
	public function call( $endpoint, $method = self::METHOD_GET, $headers = [], $data = null ) {

		$default_headers = [
			'Content-Type' => self::CONTENT_TYPE_JSON,
			'Accept'       => self::CONTENT_TYPE_JSON,
		];

		$args = [
			'method'      => $method,
			'httpversion' => self::HTTP_VERSION_1_1,
			'headers'     => wp_parse_args( $headers, $default_headers ),
		];

		if ( ! empty( $data ) ) {
			$args['body'] = wp_json_encode( $data );
		}

		$url = self::BASE_URL . $endpoint;

		$raw_response = wp_remote_request( $url, $args );

		if ( 400 <= wp_remote_retrieve_response_code( $raw_response ) ) {
			$response = new \WP_Error(
				'edgenet-error-' . wp_remote_retrieve_response_code( $raw_response ),
				esc_html( wp_remote_retrieve_response_message( $raw_response ) ),
				$raw_response
			);
		} else {
			$response = $raw_response;
		}

		return $response;
	}
}