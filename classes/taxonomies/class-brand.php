<?php
/**
 * Filename class-brand.php
 *
 * @package edgenet
 * @author  Peter Toi <peter@petertoi.com>
 */

namespace Edgenet\Taxonomies;

/**
 * Class Brand
 *
 * Summary
 *
 * @package Edgenet\Taxonomies
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
	 * Brand constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_brand_taxonomy' ] );
		add_filter( 'manage_product_posts_columns', [ $this, 'filter_posts_columns' ] );
		add_action( 'manage_product_posts_custom_column', [ $this, 'column_content' ], 9999, 2 );
	}

	/**
	 * Register Brand and link to Product.
	 */
	public function register_brand_taxonomy() {
		register_taxonomy(
			self::TAXONOMY,
			'product',
			[
				'label'        => __( 'Brands', 'edgenet' ),
				'rewrite'      => [ 'slug' => self::REWRITE ],
				'hierarchical' => false,
			]
		);
	}

	/**
	 * Add Brand column to product.
	 *
	 * @param array $columns The standard columns.
	 *
	 * @return mixed Revised columns.
	 */
	public function filter_posts_columns( $columns ) {
		$columns['brand'] = __( 'Brand', 'edgenet' );

		return $columns;
	}

	/**
	 * Display Brand term in brand column.
	 *
	 * @param string $column  The column key.
	 * @param int    $post_id The post ID.
	 */
	public function column_content( $column, $post_id ) {
		if ( 'brand' === $column ) {
			$terms = wp_get_post_terms( $post_id, self::TAXONOMY );
			if ( ! empty( $terms ) ) {
				$term_names = array_map( function ( $term ) {
					return $term->name;
				}, $terms );

				printf( '%s',
					join( '<br>', $term_names ) // phpcs:ignore
				);
			}
		}
	}
}
