<?php
/**
 * Filename class-admin.php
 *
 * @package edgenet
 * @author  Peter Toi <peter@petertoi.com>
 */

namespace Edgenet;

use Edgenet\Post_Types\Document;

/**
 * Class Admin
 *
 * Summary
 *
 * @package Edgenet
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
			'Edgenet',
			'manage_woocommerce',
			'edgenet',
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
			'woocommerce_page_edgenet' !== $hook_suffix
			&& 'post.php' !== $hook_suffix
		) {
			return;
		}
		wp_enqueue_style( 'edgenet-admin', edgenet()->get_assets_url( 'styles/admin.css' ), [], Edgenet::VERSION );
		wp_enqueue_script( 'edgenet-admin', edgenet()->get_assets_url( 'scripts/admin.js' ), [ 'jquery', 'jquery-ui-tabs' ], Edgenet::VERSION, true );
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

		if ( ! isset( $_REQUEST['page'] ) || 'edgenet' !== $_REQUEST['page'] ) { // phpcs:ignore
			return false;
		}

		if ( ! isset( $_REQUEST['action'] ) || 'update' !== $_REQUEST['action'] ) { // phpcs:ignore
			return false;
		}

		check_admin_referer( 'edgenet' );

		// Find out which action was submitted.
		$actions = filter_input( INPUT_POST, 'edgenet_action', FILTER_DEFAULT, [ 'flags' => FILTER_REQUIRE_ARRAY ] );
		$action  = key( $actions );

		$settings = filter_input( INPUT_POST, 'edgenet_settings', FILTER_DEFAULT, [ 'flags' => FILTER_REQUIRE_ARRAY ] );

		switch ( $action ) {
			case 'save_api':
				$this->save_api_settings( $settings );
				if (
					edgenet()->settings->is_core_valid()
					&& ! edgenet()->settings->is_requirement_set_valid()
				) {
					// API settings valid? Requirement set empty? Let's update it and save a step. Bonus!
					edgenet()->importer->import_requirement_set( edgenet()->settings->get_api( 'requirement_set' ) );
				}

				break;
			case 'save_field_map':
				$this->save_field_map_settings( $settings );

				break;
			case 'save_import':
				$this->save_import_settings( $settings );
				break;
			case 'import_requirement_set':
				edgenet()->importer->import_requirement_set( edgenet()->settings->get_api( 'requirement_set' ) );

				break;
			case 'import_products':
				wp_schedule_single_event( time(), 'edgenet_forced_product_sync', [ 'force' => true ] );
				wp_cron();
				break;
			case 'import_product_by_id':
				$product_id = filter_input( INPUT_POST, 'edgenet_import_product_id', FILTER_SANITIZE_STRING );
				edgenet()->importer->import_products( [ $product_id ], true );

				break;
			case 'map_categories':
				edgenet()->importer->sync_edgenet_cat_to_product_cat();

				break;
			case 'delete_images':
				$this->delete_edgenet_content( 'images' );

				break;
			case 'delete_products':
				$this->delete_edgenet_content( 'products' );

				break;
			case 'delete_docs':
				$this->delete_edgenet_content( 'docs' );

				break;
			case 'delete_all':
				$this->delete_edgenet_content( 'all' );

				break;
			default:
				break;
		}
	}

	/**
	 * Save API Settings from $_POST
	 *
	 * @param array $settings Edgenet Settings from $_POST.
	 */
	private function save_api_settings( $settings ) {
		$api_settings = filter_var( $settings['api'], FILTER_DEFAULT, [ 'flags' => FILTER_REQUIRE_ARRAY ] );

		$api_filter = [
			'username'        => [ 'filter' => FILTER_SANITIZE_STRING ],
			'secret'          => [ 'filter' => FILTER_SANITIZE_STRING ],
			'data_owner'      => [ 'filter' => FILTER_SANITIZE_STRING ],
			'recipient'       => [ 'filter' => FILTER_SANITIZE_STRING ],
			'requirement_set' => [ 'filter' => FILTER_SANITIZE_STRING ],
			'taxonomy_id'     => [ 'filter' => FILTER_SANITIZE_STRING ],
		];

		$api = filter_var_array( $api_settings, $api_filter );

		edgenet()->settings->save_api( $api );
	}

	/**
	 * Save Field Map Settings from $_POST
	 *
	 * @param array $settings Edgenet Settings from $_POST.
	 */
	private function save_field_map_settings( $settings ) {
		$field_map_settings = filter_var( $settings['field_map'], FILTER_DEFAULT, [ 'flags' => FILTER_REQUIRE_ARRAY ] );

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
			'_brand'          => [ 'filter' => FILTER_SANITIZE_STRING ],
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

	/**
	 * Save Import Settings from $_POST
	 *
	 * @param array $settings Edgenet Settings from $_POST.
	 */
	private function save_import_settings( $settings ) {
		$import_settings = filter_var( $settings['import'], FILTER_DEFAULT, [ 'flags' => FILTER_REQUIRE_ARRAY ] );

		$import_filter = [
			'user'            => [ 'filter' => FILTER_VALIDATE_INT ],
			'is_cron_enabled' => [ 'filter' => FILTER_SANITIZE_STRING ],
		];

		$import = filter_var_array( $import_settings, $import_filter );

		edgenet()->settings->save_import( $import );
	}

	/**
	 * Wrapper that handles all content deletions.
	 *
	 * // TODO: Check permissions before deleting?
	 *
	 * @param string $content_type The type of content to delete.
	 */
	private function delete_edgenet_content( $content_type ) {
		switch ( $content_type ) {
			case 'docs':
				$this->delete_docs();
				break;
			case 'images':
				$this->delete_images();
				break;
			case 'products':
				$this->delete_products();
				break;
			case 'all':
				$this->delete_products();
				$this->delete_docs();
				break;
			default:
				break;
		}

	}

	/**
	 * Delete all documents downloaded from Edgenet.
	 */
	private function delete_docs() {
		$posts = get_posts( [
			'post_type'   => Post_Types\Document::POST_TYPE,
			'numberposts' => - 1,
			'meta_query'  => [ // phpcs:ignore
				[
					'key'     => '_edgenet_id',
					'compare' => 'EXISTS',
				],
			],
		] );
		foreach ( $posts as $post ) {
			$attachment_id = get_post_meta( $post->ID, '_edgenet_wp_attachment_id', true );

			// Delete the document from the media library.
			wp_delete_attachment( $attachment_id, true );

			// Delete the Document post.
			wp_delete_post( $post->ID, true );
		}
	}

	/**
	 * Delete all images downloaded from Edgenet.
	 */
	private function delete_images() {

	}

	/**
	 * Delete all Product posts and associated images
	 */
	private function delete_products() {
		$posts = get_posts( [
			'post_type'   => 'product',
			'numberposts' => - 1,
			'meta_query'  => [ // phpcs:ignore
				[
					'key'     => '_edgenet_id',
					'compare' => 'EXISTS',
				],
			],
		] );
		foreach ( $posts as $post ) {
			$thumbnail_id = get_post_meta( $post->ID, '_thumbnail_id', true );
			$f            = wp_delete_attachment( $thumbnail_id, true );

			$gallery_ids = explode( ',', get_post_meta( $post->ID, '_product_image_gallery', true ) );
			foreach ( $gallery_ids as $id ) {

				$f = wp_delete_attachment( $id, true );
			}
			// Delete's each post.
			$r = wp_delete_post( $post->ID, true );
		}
	}
}
