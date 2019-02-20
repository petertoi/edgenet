<?php
/**
 * Filename class-fits-stove-type.php
 *
 * @package edgenet
 * @author  Peter Toi <peter@petertoi.com>
 */

namespace Edgenet\Taxonomies;

/**
 * Class Fits_Stove_Type
 *
 * Summary
 *
 * @package Edgenet\Taxonomies
 * @author  Peter Toi <peter@petertoi.com>
 * @version 1.0.0
 */
class Fits_Stove_Type {

	/**
	 * Taxonomy slug.
	 */
	const TAXONOMY = 'fits-stove-type';

	/**
	 * Rewrite slug.
	 */
	const REWRITE = 'fits-stove-type';

	/**
	 * Brand constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_fits_stove_type_taxonomy' ] );
	}

	/**
	 * Register Brand and link to Product.
	 */
	public function register_fits_stove_type_taxonomy() {
		register_taxonomy(
			self::TAXONOMY,
			'product',
			[
				'label'        => __( 'Fits Stove Type', 'edgenet' ),
				'rewrite'      => [ 'slug' => self::REWRITE ],
				'hierarchical' => false,
			]
		);
	}

}
