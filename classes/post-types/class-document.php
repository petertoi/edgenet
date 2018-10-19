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
	 * WP Attachment ID.
	 */
	const META_ATTACHMENT_ID = '_edgenet_wp_attachment_id';

	/**
	 * Document constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_document_post_type' ] );

		add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );

		add_action( 'save_post_'. self::POST_TYPE, [ $this, 'save_post' ] );
	}

	/**
	 * Register the Document post type.
	 */
	public function register_document_post_type() {
		include_once(ABSPATH.'wp-admin/includes/plugin.php');
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
			'show_in_menu'        => \is_plugin_active( 'woocommerce/woocommerce.php' ) ? 'edit.php?post_type=product' : true,
			'query_var'           => true,
			'has_archive'         => false,
			'hierarchical'        => false,
			'menu_position'       => null,
			'supports'            => $supports,
			'can_export'          => true,
			'show_in_nav_menus'   => true,
			'show_in_admin_bar'   => false,
			// Prevent Users from Creating/Editing/Deleting Documents
			 'map_meta_cap'        => true,
			'capability_type'     => 'post',
			'capabilities'        => array(
			//	'create_posts' => 'do_not_allow',
			),
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

		add_meta_box(
			'ussc-document-documrts',
			__( 'Product', 'ussc' ),
			[ $this, 'product_meta_html' ],
			self::POST_TYPE,
			'side',
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

		$data['id'] = get_post_meta( $post->ID, '_attachment_id', true );
		$data['link'] = ( $data['id'] )
			? wp_get_attachment_link( $data['id'], 'medium' )
			: '';

		$data['url'] = ( $data['id'] )
			? wp_get_attachment_url( $data['id'] )
			: '';


		if( ! empty( $attachment_id ) ) {

			Template::load( 'admin/edgenet-document-meta-box', $data );

		} else {

			wp_enqueue_media();
			Template::load( 'admin/local-document-meta-box', $data );
		}
	}

	public function product_meta_html( $post ){

		$pages = 		get_posts( [
			'post_type' => 'product',

		] );
		$output = '';
		$args = [];
		if ( ! empty( $pages ) ) {

			$output = "<select name='" . esc_attr(  'meta-product-id') . "' id='" . esc_attr(  'meta-product-id' ) . "' required=\"required\">\n";
//			if ( $r['show_option_no_change'] ) {
				$output .= "\t<option value=\"\">" . esc_html__('Select Linked Product', 'ussc') . "</option>\n";
//			}
//			if ( $r['show_option_none'] ) {
//				$output .= "\t<option value=\"" . esc_attr( $r['option_none_value'] ) . '">' . $r['show_option_none'] . "</option>\n";
//			}
			$args['selected'] = get_post_meta ( $post->ID, Document::META_ATTACHMENT_ID, true);
			$output .= walk_page_dropdown_tree( $pages, 0, $args );
			$output .= "</select>\n";
		}

		echo $output;
	}

	public function save_post( $post_id ) {
		if( self::POST_TYPE !== get_post_type( $post_id ) ) {

			return;
		}


		if ( isset( $_POST['meta-file-id'] ) ) {
			update_post_meta ( $post_id, '_attachment_id', absint( $_POST['meta-file-id'] ) );
		}
		if ( isset( $_POST['meta-product-id'] ) ) {
			update_post_meta ( $post_id, Document::META_ATTACHMENT_ID, absint( $_POST['meta-product-id'] ) );
		}

	}


}



