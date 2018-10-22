<?php
/**
 * Filename class-doc-type.php
 *
 * @package ussc
 * @author  Peter Toi <peter@petertoi.com>
 */

namespace USSC_Edgenet\Taxonomies;

use USSC_Edgenet\Post_Types\Document;

/**
 * Class Doc_Type
 *
 * Summary
 *
 * @package USSC_Edgenet\Taxonomies
 * @author  Peter Toi <peter@petertoi.com>
 * @version 1.0.0
 */
class Doc_Type {

	/**
	 * Taxonomy slug.
	 */
	const TAXONOMY = 'doc_type';

	/**
	 * Rewrite slug.
	 */
	const REWRITE = 'type';

	/**
	 * Doc_Type constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_document_type_taxonomy' ] );

		add_filter( 'manage_' . Document::POST_TYPE . '_posts_columns', [ $this, 'filter_posts_columns' ] );

		add_action( 'manage_' . Document::POST_TYPE . '_posts_custom_column',[ $this,  'column_content' ], 10, 2);
	}

	/**
	 * Register Doc_Type and link to Product.
	 */
	public function register_document_type_taxonomy() {
		register_taxonomy(
			self::TAXONOMY,
			Document::POST_TYPE,
			[
				'label'        => __( 'Doc Types', 'ussc' ),
				'rewrite'      => [ 'slug' => self::REWRITE ],
				'hierarchical' => false,
			]
		);
	}

	public function filter_posts_columns( $columns ) {
		$columns['type'] = __( 'Type' );
		$date = $columns['date'];
		unset( $columns['date'] );
		$columns['date'] = $date;

		return $columns;
	}


	function column_content( $column, $post_id ) {
		// Image column
		if ( 'type' === $column ) {
			$terms = wp_get_post_terms( $post_id, self::TAXONOMY );
			foreach ( $terms as $term ){
				echo $term->name . '<br />';
			}
		}
	}

}