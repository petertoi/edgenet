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

		global $post, $thepostid, $product_object;

		$data = [];

		$data['features']            = get_post_meta( $post->ID, '_features', true );
		$data['category_attributes'] = get_post_meta( $post->ID, '_category_attributes', true );
		$data['dimensions']          = get_post_meta( $post->ID, '_dimensions', true );
		$data['other']               = get_post_meta( $post->ID, '_other', true );
		$data['regulatory']          = get_post_meta( $post->ID, '_regulatory', true );

		Template::load( 'admin/product-data-tab-panel-edgenet', $data );

	}
}