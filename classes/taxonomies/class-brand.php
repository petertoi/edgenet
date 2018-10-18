<?php
/**
 * Filename class-doc-type.php
 *
 * @package ussc
 * @author  Peter Toi <peter@petertoi.com>
 */

namespace USSC_Edgenet\Taxonomies;

use USSC_Edgenet\Edgenet;

/**
 * Class Doc_Type
 *
 * Summary
 *
 * @package USSC_Edgenet\Taxonomies
 * @author  Peter Toi <peter@petertoi.com>
 * @version 1.0.0
 */
class Brand {

	/**
	 * Taxonomy slug.
	 */
	const TAXONOMY = 'brand';

	/**
	 * Rewrite slug.
	 */
	const REWRITE = 'brand';

	/**
	 * Doc_Type constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_document_type_taxonomy' ] );
		add_filter( 'manage_product_posts_columns', [ $this, 'filter_posts_columns' ] );

		add_action( 'manage_product_posts_custom_column',[ $this,  'column_content' ], 9999, 2);
	}

	/**
	 * Register Doc_Type and link to Product.
	 */
	public function register_document_type_taxonomy() {
		register_taxonomy(
			self::TAXONOMY,
			'product',
			[
				'label'        => __( 'Brand', 'ussc' ),
				'rewrite'      => [ 'slug' => self::REWRITE ],
				'hierarchical' => false,
			]
		);
	}

	public function filter_posts_columns( $columns ) {
		$columns['brand'] = __( 'Brand' );
		$date = $columns['date'];
		unset( $columns['date'] );
		$columns['date'] = $date;

		return $columns;
	}


	function column_content( $column, $post_id ) {
		// Image column
		if ( 'brand' === $column ) {
			$terms = wp_get_post_terms( $post_id, self::TAXONOMY );
			foreach ( $terms as $term ){
				echo $term->name . '<br />';
			}
		}
	}
}