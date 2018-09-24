<?php
/**
 * Filename class-api-adapter.php
 *
 * @package ussc
 * @author  Peter Toi <peter@petertoi.com>
 */

namespace USSC_Edgenet;

use USSC_Edgenet\Item\Product;
use USSC_Edgenet\Item\Requirement_Set;
use USSC_Edgenet\Item\Attribute;

/**
 * Class API_Adapter
 *
 * Adapter for \USSC_Edgenet\API that handles Authorization/Re-authorization
 * along with wrapping a number of API calls with easy to use functions.
 *
 * @package USSC_Edgenet
 * @author  Peter Toi <peter@petertoi.com>
 * @version 1.0.0
 */
class API_Adapter {

	const MAX_TRIES = 3;

	public $api;

	private $username;

	private $secret;

	private $auth_token;

	private $data_owner;

	private $tries = 0;

	/**
	 * API_Adapter constructor.
	 *
	 * @param string $username Edgenet API username.
	 * @param string $secret   Edgenet API secret.
	 * @param API    $api      Edgenet API instance.
	 */
	public function __construct( $username, $secret, $data_owner, $api ) {
		$this->username   = $username;
		$this->secret     = $secret;
		$this->data_owner = $data_owner;
		$this->api        = $api;

	}

	/**
	 * Makes the API call, automatically handling Auth if required.
	 *
	 * @param string $endpoint Endpoint path.
	 * @param string $method   HTTP Method, supported: GET, POST, HEAD.
	 * @param array  $data     Data to send to endpoint. Will be JSON encoded.
	 *
	 * @return \WP_Error|array
	 */
	private function call( $endpoint, $method, $data = null ) {

		if ( empty( $this->auth_token ) ) {
			$response = $this->auth();
			if ( ! is_wp_error( $response ) ) {
				// Only set upon successful response.
				$this->auth_token = $response;
			}
		}

		$this->tries = 0;

		// Assume the token has been set via __constructor or previous call.
		$headers = [
			'Authorization' => 'EN ' . $this->auth_token,
		];

		// Try the first call.
		$response = $this->api->call( $endpoint, $method, $headers, $data );

		// If it fails, drop into the loop to try again until success or self::MAX_TRIES.
		while ( is_wp_error( $response ) && ++ $this->tries < self::MAX_TRIES ) {

			// Get token.
			$response = $this->auth();

			if ( ! is_wp_error( $response ) ) {

				$this->auth_token = $response;

				$headers = [
					'Authorization' => 'EN ' . $this->auth_token,
				];

				$response = $this->api->call( $endpoint, $method, $headers, $data );
			}
		}

		return $response;

	}

	/**
	 * Send a GET request.
	 *
	 * @param string     $endpoint   Endpoint path.
	 * @param array      $query_args Query args.
	 * @param null|array $data       Request body.
	 *
	 * @return \WP_Error|array
	 */
	private function get( $endpoint, $query_args = [], $data = null ) {
		$endpoint = add_query_arg(
			$query_args,
			$endpoint
		);

		return $this->call( $endpoint, API::METHOD_GET, $data );
	}

	/**
	 * Send a POST request.
	 *
	 * @param string     $endpoint Endpoint path.
	 * @param null|array $data     Request body.
	 *
	 * @return \WP_Error|array
	 */
	private function post( $endpoint, $data = null ) {
		return $this->call( $endpoint, API::METHOD_POST, $data );
	}

	/**
	 * Send a HEAD request.
	 *
	 * @param string $endpoint Endpoint path.
	 *
	 * @return \WP_Error|array
	 */
	private function head( $endpoint ) {
		return $this->call( $endpoint, API::METHOD_HEAD );
	}

	/**
	 * Auth with Username and Secret.
	 *
	 * @return string|\WP_Error The token if successful or WP_Error.
	 */
	public function auth() {
		$path = add_query_arg(
			[
				'username' => $this->username,
				'secret'   => rawurlencode( $this->secret ),
			],
			'api/auth/'
		);

		// Call API directly, bypassing adapter auth wrapper.
		$raw_response = $this->api->call( $path );

		if ( is_wp_error( $raw_response ) ) {
			$response = new \WP_Error(
				'ussc-edgenet-auth-error',
				__( 'Auth error.', 'ussc' ),
				$raw_response
			);
		} else {
			$body = wp_remote_retrieve_body( $raw_response );
			$data = json_decode( $body );
			if ( empty( $data->Value ) ) {  // phpcs:ignore
				$response = new \WP_Error(
					'ussc-edgenet-auth-value-is-empty',
					__( 'Auth value is empty.', 'ussc' ),
					$raw_response
				);
			} else {
				$response = $data->Value;  // phpcs:ignore
			}
		}

		return $response;
	}

	/**
	 * Retrieve a Requirement Set
	 *
	 * @param string $uid UID of the Requirement Set. Try: c726fa92-7119-2e37-30fe-304a1a3e579d
	 *
	 * @return Requirement_Set|\WP_Error
	 */
	public function requirementset( $uid ) {

		$path = 'api/distribute/requirementset/';

		$data = [ $uid ];

		$response = $this->post( $path, $data );

		if ( is_wp_error( $response ) ) {
			$set = $response;
		} else {
			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body, true );
			$set  = new Requirement_Set( $data[0] );
		}

		return $set;

	}

	/**
	 * Retrieve Attribute information
	 *
	 * @param array $attributes Attribute UIDs.
	 *
	 * @return array|\WP_Error
	 */
	public function attribute( $attributes = [] ) {

		$path = add_query_arg(
			[
				'vendorId' => $this->data_owner,
			],
			'api/attribute/'
		);

		$data = $attributes;

		$response = $this->post( $path, $data );

		if ( is_wp_error( $response ) ) {
			$attributes = $response;
		} else {
			$body       = wp_remote_retrieve_body( $response );
			$data       = json_decode( $body, true );
			$attributes = [];
			foreach ( $data as $attribute_params ) {
				$attributes[] = new Attribute( $attribute_params );
			}
		}

		return $attributes;
	}

	/**
	 * @param string $product_search_filter
	 * @param int    $skip
	 * @param int    $take
	 *
	 * @return array|mixed|object|\WP_Error
	 */
	public function productsearch( $product_search_filter = '', $skip = 0, $take = 100 ) {

		$path = add_query_arg(
			[
				'skip' => $skip,
				'take' => $take,
			],
			'api/search/productsearch/'
		);

		$data = [
			'DataOwner'           => Edgenet::DATA_OWNER,
			'ProductSearchFilter' => $product_search_filter,
		];

		$response = $this->post( $path, $data );

		if ( is_wp_error( $response ) ) {
			$results = $response;
		} else {
			$body    = wp_remote_retrieve_body( $response );
			$results = json_decode( $body, true );
		}

		return $results;
	}

	/**
	 * @param $product_uid
	 *
	 * @return array|Product|\WP_Error
	 */
	public function product( $product_uid ) {

		$path = 'api/product/' . rawurlencode( $product_uid );

		$response = $this->get( $path );

		if ( is_wp_error( $response ) ) {
			$product = $response;
		} else {
			$body          = wp_remote_retrieve_body( $response );
			$product_array = json_decode( $body, true );
			if ( isset( $product_array['Type'] ) && Product::TYPE === $product_array['Type'] ) {
				$product = new Product( $product_array );
			} else {
				$product = new \WP_Error(
					'ussc-edgenet-product-error',
					__( 'Product Type missing from endpoint response.', 'ussc' ),
					$product_array
				);
			}
		}

		return $product;
	}

}