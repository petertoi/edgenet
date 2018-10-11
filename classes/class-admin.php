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

		add_action( 'add_meta_boxes', [ $this, 'marketing_add_meta_box' ] );
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
		if ( isset( $_REQUEST['sync'] ) ) {
			if ( 'Import Products Manually' === $_REQUEST['sync'] ) {
				if ( false === get_transient( Edgenet::ACTIVE_CRON_KEY ) ) {
					wp_schedule_single_event( time(), 'edgenet_sync_now' );
				}
			} else {
				edgenet()->importer->import_products( [ $_REQUEST['sync'] ], true );
			}
		}
		if ( ! isset( $_REQUEST['page'] ) || 'ussc-edgenet' !== $_REQUEST['page'] ) { // phpcs:ignore
			return false;
		}
		if ( ! isset( $_REQUEST['action'] ) || 'update' !== $_REQUEST['action'] ) { // phpcs:ignore
			return false;
		}
		check_admin_referer( 'ussc-edgenet' );


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

		if ( isset( $settings['requirements_not_set'] ) ) {
			// TODO: Ensure we have API credentials, and requirement set chosen, and field map empty rather than hidden input.
			edgenet()->importer->import_requirement_set( Edgenet::REQUIREMENT_SET );
		}

		// check we have data to save
		if ( isset( $settings['field_map'] ) ) {


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
				'_model_no'       => [ 'filter' => FILTER_SANITIZE_STRING ],
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

	function ussc_marketing_get_meta( $value ) {
		global $post;

		$field = get_post_meta( $post->ID, $value, true );
		if ( ! empty( $field ) ) {
			return is_array( $field ) ? stripslashes_deep( $field ) : stripslashes( wp_kses_decode_entities( $field ) );
		} else {
			return false;
		}
	}

	function marketing_add_meta_box() {
		add_meta_box(
			'ussc_marketing-ussc-marketing',
			__( 'Marketing Meta', 'ussc' ),
			[ $this, 'ussc_marketing_html' ],
			'product',
			'normal',
			'default'
		);
		add_meta_box(
			'ussc_ussc_specifications_html-ussc-marketing',
			__( 'Specifications Meta', 'ussc' ),
			[ $this, 'ussc_specifications_html' ],
			'product',
			'normal',
			'default'
		);
	}

	function ussc_marketing_html( $post ) {

		$marketing_attributes = get_post_meta( get_the_ID(), '_marketing', true );
		if ( empty( $marketing_attributes ) ) {
			return;
		}
		echo '<ul>';
		foreach ( $marketing_attributes as $marketing_attribute ) {
			printf( '<li><strong>%s:</strong> %s</li>', $marketing_attribute['attribute']->description, $marketing_attribute['value'] );
		}
		echo '</ul>';
	}

	function ussc_specifications_html( $post ) {

		$specifications_attributes = get_post_meta( get_the_ID(), '_specifications', true );

		if ( empty( $specifications_attributes ) ) {
			return;
		}
		echo '<ul>';
		foreach ( $specifications_attributes as $specifications_attribute ) {
			printf( '<li><strong>%s:</strong> %s</li>', $specifications_attribute['attribute']->description, $specifications_attribute['value'] );
		}
		echo '</ul>';
	}


}