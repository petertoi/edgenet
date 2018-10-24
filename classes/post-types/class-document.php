<?php
/**
 * Filename class-documents.php
 *
 * @package ussc
 * @author  Peter Toi <peter@petertoi.com>
 *
 */

namespace USSC_Edgenet\Post_Types;


use USSC_Edgenet\Template;

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Document {

	/**
	 * Post type slug.
	 */
	const POST_TYPE = 'document';

	/**
	 * Rewrite slug.
	 */
	const REWRITE = 'document';

	/**
	 * Meta key for storing the Attachment (PDF).
	 */
	const META_ATTACHMENT_ID = '_attachment_id';

	/**
	 * Meta key for storing the related Product ID.
	 */
	const META_PRODUCT_ID = '_product_id';

	/**
	 * Document constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_document_post_type' ] );

		add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );

		add_action( 'save_post_' . self::POST_TYPE, [ $this, 'save_post' ] );
	}

	/**
	 * Register the Document post type.
	 */
	public function register_document_post_type() {
		// TODO: Remove this include when Paul sorts out his environment.
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
		/**
		 * Document post type labels.
		 */
		$labels = [
			'name'               => esc_html_x( 'Documents', 'Documents Post Type General Name', 'ussc' ),
			'singular_name'      => esc_html_x( 'Document', 'Documents Post Type Singular Name', 'ussc' ),
			'add_new'            => esc_html__( 'Add New', 'ussc' ),
			'add_new_item'       => esc_html__( 'Add New Document', 'ussc' ),
			'edit_item'          => esc_html__( 'Edit Document', 'ussc' ),
			'new_item'           => esc_html__( 'New Document', 'ussc' ),
			'view_item'          => esc_html__( 'View Document', 'ussc' ),
			'search_items'       => esc_html__( 'Search Documents', 'ussc' ),
			'not_found'          => esc_html__( 'No Documents found', 'ussc' ),
			'not_found_in_trash' => esc_html__( 'No Documents found in Trash', 'ussc' ),
			'parent_item_colon'  => '',
			'all_items'          => esc_html__( 'Documents', 'ussc' ),
			'menu_name'          => esc_html__( 'Documents', 'ussc' ),
		];

		/**
		 * Document post type supports
		 */
		$supports = [ 'title' ];

		/**
		 * Document post type args
		 */
		$args = [
			'description'         => esc_html__( 'Product documents imported from Edgenet.', 'ussc' ),
			'labels'              => $labels,
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => is_plugin_active( 'woocommerce/woocommerce.php' ) ? 'edit.php?post_type=product' : true,
			'query_var'           => true,
			'has_archive'         => false,
			'hierarchical'        => false,
			'menu_position'       => null,
			'supports'            => $supports,
			'can_export'          => true,
			'show_in_nav_menus'   => true,
			'show_in_admin_bar'   => false,
		];

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Register the Meta Box
	 */
	public function add_meta_box() {
		add_meta_box(
			'ussc-document-file',
			__( 'File', 'ussc' ),
			[ $this, 'meta_html' ],
			self::POST_TYPE,
			'normal',
			'default'
		);
	}

	/**
	 * Meta Box callback.
	 *
	 * @param \WP_Post $post The current post.
	 */
	public function meta_html( $post ) {
		$data = [];

		$data['attachment_id'] = get_post_meta( $post->ID, self::META_ATTACHMENT_ID, true );
		$data['edgenet_id']    = get_post_meta( $post->ID, '_edgenet_id', true );

		$data['attachment_link'] = ( $data['attachment_id'] )
			? wp_get_attachment_link( $data['attachment_id'], 'medium' )
			: '';

		$data['attachment_url'] = ( $data['attachment_id'] )
			? wp_get_attachment_url( $data['attachment_id'] )
			: '';

		if ( empty( $data['edgenet_id'] ) ) {
			wp_enqueue_media();
		}

		Template::load( 'admin/document-meta-box', $data );
	}

	/**
	 * Save Document attachment.
	 *
	 * Checks for presence of ussc_action in $_POST before attempting save or delete of attachment.
	 *
	 * @param int $post_id The Post ID.
	 */
	public function save_post( $post_id ) {
		if ( self::POST_TYPE !== get_post_type( $post_id ) ) {
			return;
		}

		$ussc_action = filter_input( INPUT_POST, 'ussc_action', FILTER_SANITIZE_STRING );

		if ( 'edit_document' !== $ussc_action ) {
			return;
		}

		$attachment_id = filter_input( INPUT_POST, 'ussc_attachment_id', FILTER_SANITIZE_NUMBER_INT );

		if ( $attachment_id ) {
			update_post_meta( $post_id, self::META_ATTACHMENT_ID, $attachment_id );
		} else {
			delete_post_meta( $post_id, self::META_ATTACHMENT_ID );
		}
	}


}



