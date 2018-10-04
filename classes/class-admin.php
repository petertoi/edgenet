<?php
/**
 * Filename class-admin.php
 *
 * @package ussc
 * @author  Peter Toi <peter@petertoi.com>
 */

namespace USSC_Edgenet;

/**
 * Class Admin
 *
 * Summary
 *
 * @package USSC_Edgenet
 * @author  Peter Toi <peter@petertoi.com>
 * @version 1.0.0
 */
class Admin {

	public function __construct() {
		add_action( 'init', [ $this, 'save_edgenet_settings' ] );
		add_action( 'admin_menu', [ $this, 'edgenet_settings_page_menu' ] );

	}

	public function edgenet_settings_page_menu() {
		add_submenu_page(
			'woocommerce',
			'Edgenet API Settings',
			'Edgenet API',
			'manage_woocommerce',
			'ussc-edgenet',
			[ $this, 'edgenet_settings_page_callback' ]
		);
	}

	public function edgenet_settings_page_callback() {
		Template::load( 'admin/settings' );
	}

	public function save_edgenet_settings() {
		if ( ! is_admin() ) {
			return false;
		}
		if ( ! isset( $_REQUEST['page'] ) || 'ussc-edgenet' !== $_REQUEST['page'] ) { // phpcs:ignore
			return false;
		}
		if ( ! isset( $_REQUEST['action'] ) || 'update' !== $_REQUEST['action'] ) { // phpcs:ignore
			return false;
		}
		check_admin_referer( 'ussc-edgenet' );

		if( isset( $_REQUEST['sync'] ) ) {
			edgenet()->import_products();
		}

		$settings = filter_input( INPUT_POST, 'edgenet_settings', FILTER_DEFAULT, [ 'flags' => FILTER_REQUIRE_ARRAY ] );

		$api_filter = [
			'data_owner'      => [ 'filter' => FILTER_SANITIZE_STRING ],
			'username'        => [ 'filter' => FILTER_SANITIZE_STRING ],
			'secret'          => [ 'filter' => FILTER_SANITIZE_STRING ],
			'requirement_set' => [ 'filter' => FILTER_SANITIZE_STRING ],
			'import_user'     => [ 'filter' => FILTER_VALIDATE_INT ],
		];

		$api = filter_var_array( $settings['api'], $api_filter );

		edgenet()->settings->save_api( $api );

		$field_map = filter_var( $settings['field_map'], FILTER_DEFAULT, [ 'flags' => FILTER_REQUIRE_ARRAY ] );

		$post_map = filter_var( $field_map['post'], FILTER_DEFAULT, [ 'flags' => FILTER_REQUIRE_ARRAY ] );

		$post_filter = [
			'post_title'   => [ 'filter' => FILTER_SANITIZE_STRING ],
			'post_content' => [ 'filter' => FILTER_SANITIZE_STRING ],
			'post_excerpt' => [ 'filter' => FILTER_SANITIZE_STRING ],
		];

		$post = filter_var_array( $post_map, $post_filter );

		$postmeta_map = filter_var( $field_map['postmeta'], FILTER_DEFAULT, [ 'flags' => FILTER_REQUIRE_ARRAY ] );

		$postmeta_filter = [
			'_gtin'           => [ 'filter' => FILTER_SANITIZE_STRING ],
			'_sku'            => [ 'filter' => FILTER_SANITIZE_STRING ],
			'_regular_price'  => [ 'filter' => FILTER_SANITIZE_STRING ],
			'_weight'         => [ 'filter' => FILTER_SANITIZE_STRING ],
			'_length'         => [ 'filter' => FILTER_SANITIZE_STRING ],
			'_width'          => [ 'filter' => FILTER_SANITIZE_STRING ],
			'_height'         => [ 'filter' => FILTER_SANITIZE_STRING ],
			'_marketing'      => [ 'filter' => FILTER_SANITIZE_STRING ],
			'_specifications' => [ 'filter' => FILTER_SANITIZE_STRING ],
			'_primary_image'  => [ 'filter' => FILTER_SANITIZE_STRING ],
			'_digital_assets' => [ 'filter' => FILTER_SANITIZE_STRING ],
			'_documents'      => [ 'filter' => FILTER_SANITIZE_STRING ],
		];

		$postmeta = filter_var_array( $postmeta_map, $postmeta_filter );

		edgenet()->settings->save_field_map( [
			'post'     => $post,
			'postmeta' => $postmeta,
		] );
	}
}