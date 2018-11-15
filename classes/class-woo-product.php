<?php
/**
 * Filename class-woo-product.php
 *
 * @package edgenet
 * @author  Peter Toi <peter@petertoi.com>
 */

namespace Edgenet;

/**
 * Class Woo_Product
 *
 * Summary
 *
 * @package Edgenet
 * @author  Peter Toi <peter@petertoi.com>
 * @version 1.0.0
 */
class Woo_Product {

	/**
	 * Woo_Product constructor.
	 */
	public function __construct() {
		add_filter( 'woocommerce_product_data_tabs', [ $this, 'add_product_data_tabs' ] );
		add_filter( 'woocommerce_product_data_panels', [ $this, 'add_product_data_panel' ] );
	}

	/**
	 * Add a tab for viewing Edgenet Meta on the Product Edit screen.
	 *
	 * @param array $tabs .
	 *
	 * @return mixed
	 */
	public function add_product_data_tabs( $tabs ) {

		$tabs[] = [
			'label'    => __( 'Edgenet' ),
			'target'   => 'edgenet',
			'class'    => [],
			'priority' => 100,
		];

		return $tabs;
	}

	/**
	 * Render the Edgenet Meta tab panel.
	 */
	public function add_product_data_panel() {

		global $post;

		$data = [];

		$data['core'] = [
			[
				'label' => __( 'Model #' ),
				'value' => get_post_meta( $post->ID, '_model_no', true ),
			],
			[
				'label' => __( 'GTIN' ),
				'value' => get_post_meta( $post->ID, '_gtin', true ),
			],
			[
				'label' => __( 'Edgenet ID' ),
				'value' => sprintf(
					'%1s<br><a href="https://platform.edgenet.com/products/detail/%1$s">%2s</a>',
					esc_attr( get_post_meta( $post->ID, '_edgenet_id', true ) ),
					esc_html__( 'View on Edgenet', 'edgenet' )
				),
			],
			[
				'label' => __( 'Last Verified Date Time' ),
				'value' => get_post_meta( $post->ID, '_last_verified_date_time', true ),
			],
			[
				'label' => __( 'Is Verified' ),
				'value' => get_post_meta( $post->ID, '_is_verified', true ) ? __( 'True', 'edgenet' ) : __( 'False', 'edgenet' ),
			],
			[
				'label' => __( 'Archived' ),
				'value' => get_post_meta( $post->ID, '_archived', true ) ? __( 'True', 'edgenet' ) : __( 'False', 'edgenet' ),
			],
			[
				'label' => __( 'Archived Metadata' ),
				'value' => maybe_serialize( get_post_meta( $post->ID, '_archived_metadata', true ) ),
			],
			[
				'label' => __( 'Record Date' ),
				'value' => get_post_meta( $post->ID, '_record_date', true ),
			],
		];

		$data['attribute_group']['features']            = get_post_meta( $post->ID, '_features', true );
		$data['attribute_group']['category_attributes'] = get_post_meta( $post->ID, '_category_attributes', true );
		$data['attribute_group']['dimensions']          = get_post_meta( $post->ID, '_dimensions', true );
		$data['attribute_group']['other']               = get_post_meta( $post->ID, '_other', true );
		$data['attribute_group']['regulatory']          = get_post_meta( $post->ID, '_regulatory', true );

		Template::load( 'admin/product-data-tab-panel-edgenet', $data );

	}
}