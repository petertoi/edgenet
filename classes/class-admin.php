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

	/**
	 * Admin constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'save_edgenet_settings' ] );
		add_action( 'admin_menu', [ $this, 'edgenet_settings_page_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
	}

	/**
	 * Register Edgenet Admin Page.
	 */
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

	/**
	 * Edgenet Admin Page Callback.
	 */
	public function edgenet_settings_page_callback() {
		Template::load( 'admin/settings' );
	}

	/**
	 * Enqueue scripts.
	 *
	 * @param string $hook_suffix Page hook.
	 */
	public function admin_enqueue_scripts( $hook_suffix ) {
		if (
			'woocommerce_page_ussc-edgenet' !== $hook_suffix
			&& 'post.php' !== $hook_suffix
		) {
			return;
		}
		wp_enqueue_style( 'ussc-edgenet-admin', edgenet()->get_assets_url( 'styles/admin.css' ), [], Edgenet::VERSION );
		wp_enqueue_script( 'ussc-edgenet-admin', edgenet()->get_assets_url( 'scripts/admin.js' ), [ 'jquery', 'jquery-ui-tabs' ], Edgenet::VERSION, true );
	}

	/**
	 * Save Settings.
	 *
	 * @return bool
	 */
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

		// Actions.
		$save_api               = filter_input( INPUT_POST, 'edgenet_save_api', FILTER_SANITIZE_STRING );
		$save_field_map         = filter_input( INPUT_POST, 'edgenet_save_field_map', FILTER_SANITIZE_STRING );
		$import_requirement_set = filter_input( INPUT_POST, 'edgenet_import_requirement_set', FILTER_SANITIZE_STRING );
		$import_products        = filter_input( INPUT_POST, 'edgenet_import_products', FILTER_SANITIZE_STRING );
		$import_product_by_id   = filter_input( INPUT_POST, 'edgenet_import_product_by_id', FILTER_SANITIZE_STRING );
		$map_categories         = filter_input( INPUT_POST, 'edgenet_map_categories', FILTER_SANITIZE_STRING );

		$settings = filter_input( INPUT_POST, 'edgenet_settings', FILTER_DEFAULT, [ 'flags' => FILTER_REQUIRE_ARRAY ] );

		// The big if/elseif.
		if ( ! empty( $save_api ) ) {
			// Save API.
			$this->save_api_settings( $settings['api'] );
			if (
				edgenet()->settings->is_core_valid()
				&& ! edgenet()->settings->is_requirement_set_valid()
			) {
				// Update requirement set, if it hasn't already been set.
				edgenet()->importer->import_requirement_set( edgenet()->settings->requirement_set );
			}
		} elseif ( ! empty( $save_field_map ) ) {
			// Save Field Map.
			$this->save_field_map_settings( $settings['field_map'] );
		} elseif ( ! empty( $import_requirement_set ) ) {
			// Import Requirement Set.
			edgenet()->importer->import_requirement_set( edgenet()->settings->api['requirement_set'] );
		} elseif ( ! empty( $import_products ) ) {
			// Import Products.
			wp_schedule_single_event( time(), 'ussc_product_sync_now' );

		} elseif ( ! empty( $import_product_by_id ) ) {
			// Import Product By ID.
			$product_id = filter_input( INPUT_POST, 'edgenet_import_product_id', FILTER_SANITIZE_STRING );
			edgenet()->importer->import_products( [ $product_id ], true );
		} elseif ( ! empty( $map_categories ) ) {
			// Map Categories.
			edgenet()->importer->sync_edgenet_cat_to_product_cat();
		}
	}

	/**
	 * Save API Settings from $_POST
	 *
	 * @param array $api_settings
	 */
	private function save_api_settings( $api_settings ) {
		$api_filter = [
			'username'        => [ 'filter' => FILTER_SANITIZE_STRING ],
			'secret'          => [ 'filter' => FILTER_SANITIZE_STRING ],
			'data_owner'      => [ 'filter' => FILTER_SANITIZE_STRING ],
			'requirement_set' => [ 'filter' => FILTER_SANITIZE_STRING ],
			'taxonomy_id'     => [ 'filter' => FILTER_SANITIZE_STRING ],
			'import_user'     => [ 'filter' => FILTER_VALIDATE_INT ],
		];

		$api = filter_var_array( $api_settings, $api_filter );

		edgenet()->settings->save_api( $api );
	}

	/**
	 * Save Field Map Settings from $_POST
	 *
	 * @param array $field_map_settings
	 */
	private function save_field_map_settings( $field_map_settings ) {
		$field_map = filter_var( $field_map_settings, FILTER_DEFAULT, [ 'flags' => FILTER_REQUIRE_ARRAY ] );

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
			'_features'       => [ 'filter' => FILTER_SANITIZE_STRING ],
			'_dimensions'     => [ 'filter' => FILTER_SANITIZE_STRING ],
			'_other'          => [ 'filter' => FILTER_SANITIZE_STRING ],
			'_regulatory'     => [ 'filter' => FILTER_SANITIZE_STRING ],
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