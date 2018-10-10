<?php
/**
 * Filename class-documents.php
 *
 * @package ussc
 * @author  Peter Toi <peter@petertoi.com>
 *
 */

namespace USSC_Edgenet;


if ( ! defined( 'WPINC' ) ) {
	die;
}

class Documents {

	public $post_type = 'document';

	public function __construct() {
		add_action( 'init', array( $this, 'create_post_type' ) );

		add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );

		add_action( 'init', [ $this, 'create_tax' ] );
	}

	public function create_post_type() {

		/**
		 * Filter: Document Post Type Labels
		 *
		 * @since   3.0.0
		 *
		 * @param array $labels
		 */
		$labels = array(
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
			'menu_name'          => esc_html__( 'Document', 'ussc' ),
		);

		/**
		 * Filter: Document Post Type Supports
		 *
		 * @since   3.0.0
		 */
		$supports = apply_filters( 'matador_post_type_supports_application', array(
			'title',
//			'editor',
//			'custom-fields',
		) );

		/**
		 * Filter: Document Post Type Args
		 *
		 * @since   3.0.0
		 */
		$args = array(
			'description'         => esc_html__( 'Documents for the Products.', 'ussc' ),
			'labels'              => $labels,
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => 'edit.php?post_type=product',
			'query_var'           => true,
			'has_archive'         => false,
			'hierarchical'        => false,
			'menu_position'       => null,
			'supports'            => $supports,
			'can_export'          => true,
			'show_in_nav_menus'   => true,
			'show_in_admin_bar'   => false,
			// Prevent Users from Creating/Editing/Deleting Documents
//			'map_meta_cap'        => true,
			'capability_type'     => 'product',
//			'capabilities'        => array(
//				'create_posts' => 'do_not_allow',
//			),
		);


		register_post_type( $this->post_type, $args );

	}


	function add_meta_box() {
		add_meta_box(
			'ussc_marketing-ussc-marketing',
			__( 'Linked File', 'ussc' ),
			[ $this, 'meta_html' ],
			$this->post_type,
			'normal',
			'default'
		);

	}

	function meta_html( $post ) {

		$doc_id = get_post_meta( $post->ID, '_edgenet_linked_document_id', true );
		if ( empty( $doc_id ) ) {
			return;
		}
		echo wp_get_attachment_link( $doc_id, 'medium' );
		printf( '<br /><a href="%s" target="_blank">%1$s</a>', wp_get_attachment_url( $doc_id ) );
	}




	function create_tax() {
		register_taxonomy(
			'doc_type',
			$this->post_type,
			array(
				'label' => __( 'Document type' ),
				'rewrite' => array( 'slug' => 'type' ),
				'hierarchical' => true,
			)
		);
	}
}